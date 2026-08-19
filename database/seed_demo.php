<?php
/**
 * database/seed_demo.php
 *
 * One-off demo data seeder for the capstone defense — NOT a migration,
 * does not run automatically and is not wired into database/migrations/.
 * Run manually from the CLI:
 *
 *   php database/seed_demo.php
 *
 * Wipes every table in the local `lvms` database (config/secrets.php ->
 * DB_NAME) and reloads it with a self-consistent demo dataset: 3 companies,
 * 18 users across all 5 roles, a 12-vehicle shared fleet, and reservations/
 * gatepasses/trips spanning every status in the workflow (pending, rejected
 * both ways, cancelled both ways, gatepass_pending, approved/pending_start,
 * in_progress, an open incident, and a batch of completed history) so every
 * major screen has something real to show.
 *
 * Re-runnable at any time — all "recent" timestamps (the in-progress trips,
 * the open incident, the still-pending requests) are computed relative to
 * the moment this script runs, not hard-coded dates. Run it again shortly
 * before the defense to refresh the Live Map GPS freshness.
 *
 * Every seeded user's password is "password" (bcrypt-hashed below),
 * matching this repo's existing test-account convention.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// This repo runs with no .htaccess (see CLAUDE.md) — every file under the
// web root, this one included, is directly reachable by URL on Hostinger.
// CLI/cron execution (PHP_SAPI === 'cli') is always allowed. A browser hit
// must present ?token=<SEED_DEMO_TOKEN>, matching the gitignored constant
// in config/secrets.php — never hardcoded here, since this file is committed.
// No SEED_DEMO_TOKEN defined = browser access blocked outright.
if (PHP_SAPI !== 'cli') {
    if (!defined('SEED_DEMO_TOKEN') || ($_GET['token'] ?? '') !== SEED_DEMO_TOKEN) {
        http_response_code(403);
        exit('Forbidden.');
    }
    header('Content-Type: text/plain');
}

$pdo = Database::getInstance();

echo "== MoveOps demo seed ==\n";

// ── Helpers ──────────────────────────────────────────────────────
function insert(PDO $pdo, string $table, array $data): int
{
    $cols = array_keys($data);
    $sql  = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES ('
          . implode(',', array_map(fn($c) => ':' . $c, $cols)) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return (int) $pdo->lastInsertId();
}

$NOW = new DateTime('now');

// $days: offset from now (negative = past). $hm: 'H:i' time of day.
function ts(DateTime $base, float $days, string $hm = '08:00'): string
{
    $d = clone $base;
    $seconds = (int) round($days * 86400);
    $d->modify(($seconds >= 0 ? '+' : '') . $seconds . ' seconds');
    [$h, $m] = explode(':', $hm);
    $d->setTime((int) $h, (int) $m, 0);
    return $d->format('Y-m-d H:i:s');
}

// Minutes-ago timestamp, for GPS breadcrumb points near "now".
function minsAgo(DateTime $base, int $mins): string
{
    $d = clone $base;
    $d->modify('-' . $mins . ' minutes');
    return $d->format('Y-m-d H:i:s');
}

$PASSWORD_HASH = password_hash('password', PASSWORD_BCRYPT);

// ── 1. Wipe existing data ────────────────────────────────────────
echo "Truncating tables...\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ([
    'audit_logs', 'notifications', 'gps_tracking_logs', 'trip_incidents',
    'ai_recommendation_logs', 'trips', 'gatepasses', 'reservations',
    'projects', 'vehicle_maintenance', 'vehicles', 'vehicle_categories',
    'trip_purposes', 'driver_profiles', 'users', 'departments', 'companies',
] as $t) {
    $pdo->exec("TRUNCATE TABLE `$t`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// ── 2. Companies ─────────────────────────────────────────────────
echo "Companies, departments...\n";
$companies = [
    ['company_name' => 'Remix Construction and Trading Corporation', 'company_code' => 'REMIX',
     'address' => '88 Congressional Avenue, Barangay Bahay Toro, Quezon City, Metro Manila',
     'contact_number' => '(02) 8921-4455'],
    ['company_name' => 'Ideal Home', 'company_code' => 'IDEAL',
     'address' => 'Km 21 Sumulong Highway, Barangay San Roque, Antipolo City, Rizal',
     'contact_number' => '(02) 8654-2210'],
    ['company_name' => 'TenBuild', 'company_code' => 'TNBLD',
     'address' => 'National Highway, Barangay Luciano, Trece Martires City, Cavite',
     'contact_number' => '(046) 419-3387'],
];
$companyId = [];
foreach ($companies as $i => $c) {
    $companyId[$i + 1] = insert($pdo, 'companies', $c + ['is_active' => 1]);
}

// ── 3. Departments ───────────────────────────────────────────────
$departments = [
    [$companyId[1], 'Human Resources', 'Manages the worker life cycle, including hiring, pay, and labor rules.'],
    [$companyId[1], 'Engineering', 'Design, technical documentation, and site engineering support.'],
    [$companyId[1], 'Logistics', 'Materials handling, warehousing, and delivery coordination.'],
    [$companyId[2], 'Human Resources', 'Manages the worker life cycle, including hiring, pay, and labor rules.'],
    [$companyId[2], 'Sales & Marketing', 'Client acquisition, property viewings, and account management.'],
    [$companyId[2], 'Engineering', 'Design, technical documentation, and site engineering support.'],
    [$companyId[3], 'Human Resources', 'Manages the worker life cycle, including hiring, pay, and labor rules.'],
    [$companyId[3], 'Accounting', "Manages the company's cash flow, records financial transactions, and prepares financial reports."],
    [$companyId[3], 'Site Operations', 'Day-to-day construction site management and field coordination.'],
];
$deptId = [];
foreach ($departments as $i => [$cid, $name, $desc]) {
    $deptId[$i + 1] = insert($pdo, 'departments', [
        'company_id' => $cid, 'department_name' => $name, 'description' => $desc, 'is_active' => 1,
    ]);
}

// ── 4. Users ─────────────────────────────────────────────────────
echo "Users...\n";
// [role, company#, dept# or null, first, last, email, phone]
$userDefs = [
    [ROLE_SUPER_ADMIN, 1, null, 'Super', 'Admin', 'superadmin@lvms.test', '0917-100-0001'],
    [ROLE_FLEET_ADMIN, 1, null, 'Fleet', 'Admin', 'fleetadmin@lvms.test', '0917-100-0002'],
    [ROLE_ADMIN, 1, 1, 'Remix', 'Admin', 'admin.remix@lvms.test', '0917-100-0003'],
    [ROLE_ADMIN, 2, 4, 'Ideal', 'Admin', 'admin.ideal@lvms.test', '0917-100-0004'],
    [ROLE_ADMIN, 3, 7, 'TenBuild', 'Admin', 'admin.tenbuild@lvms.test', '0917-100-0005'],
    [ROLE_EMPLOYEE, 1, 2, 'Juan', 'Dela Cruz', 'juan.delacruz@lvms.test', '0917-200-0006'],
    [ROLE_EMPLOYEE, 1, 3, 'Maria', 'Santos', 'maria.santos@lvms.test', '0917-200-0007'],
    [ROLE_EMPLOYEE, 2, 5, 'Andrea', 'Reyes', 'andrea.reyes@lvms.test', '0917-200-0008'],
    [ROLE_EMPLOYEE, 2, 6, 'Carlo', 'Bautista', 'carlo.bautista@lvms.test', '0917-200-0009'],
    [ROLE_EMPLOYEE, 3, 8, 'Liza', 'Fernandez', 'liza.fernandez@lvms.test', '0917-200-0010'],
    [ROLE_EMPLOYEE, 3, 9, 'Ramon', 'Aquino', 'ramon.aquino@lvms.test', '0917-200-0011'],
    [ROLE_DRIVER, 1, null, 'Josefino', 'Martinez', 'josefino.martinez@lvms.test', '0917-300-0012'],
    [ROLE_DRIVER, 1, null, 'Ricardo', 'Cruz', 'ricardo.cruz@lvms.test', '0917-300-0013'],
    [ROLE_DRIVER, 2, null, 'Alberto', 'Villanueva', 'alberto.villanueva@lvms.test', '0917-300-0014'],
    [ROLE_DRIVER, 2, null, 'Danilo', 'Ramos', 'danilo.ramos@lvms.test', '0917-300-0015'],
    [ROLE_DRIVER, 3, null, 'Eduardo', 'Garcia', 'eduardo.garcia@lvms.test', '0917-300-0016'],
    [ROLE_DRIVER, 1, null, 'Roberto', 'Villareal', 'roberto.villareal@lvms.test', '0917-300-0017'],
    [ROLE_DRIVER, 2, null, 'Fernando', 'Torres', 'fernando.torres@lvms.test', '0917-300-0018'],
];
$userId = [];
foreach ($userDefs as $i => [$role, $co, $dept, $fn, $ln, $email, $phone]) {
    $n = $i + 1;
    $userId[$n] = insert($pdo, 'users', [
        'company_id'    => $companyId[$co],
        'department_id' => $dept !== null ? $deptId[$dept] : null,
        'role'          => $role,
        'employee_id'   => 'EMP-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'first_name'    => $fn,
        'last_name'     => $ln,
        'email'         => $email,
        'password_hash' => $PASSWORD_HASH,
        'phone_number'  => $phone,
        'is_active'     => 1,
        'last_login_at' => $role === ROLE_DRIVER ? null : ts($NOW, -0.3, '08:1' . random_int(0, 5)),
    ]);
}

// Driver profiles — status matches each driver's role in the trip scenarios below.
$driverProfiles = [
    // userDefsIndex => [license_number, license_type, restrictions, status]
    12 => ['N01-23-456789', 'Professional', 'B, B1, B2, C', DRV_ON_TRIP],
    13 => ['N02-34-567890', 'Professional', 'B, B1, B2', DRV_AVAILABLE],
    14 => ['N03-45-678901', 'Professional', 'B, B1, B2, C', DRV_AVAILABLE],
    15 => ['N04-56-789012', 'Professional', 'B, B1, B2, C', DRV_ON_TRIP],
    16 => ['N05-67-890123', 'Professional', 'B, B1, B2', DRV_AVAILABLE],
    17 => ['N06-78-901234', 'Professional', 'B, B1, B2', DRV_OFF_DUTY],
    18 => ['N07-89-012345', 'Professional', 'B, B1, B2, C', DRV_ON_LEAVE],
];
foreach ($driverProfiles as $n => [$lic, $type, $restr, $status]) {
    insert($pdo, 'driver_profiles', [
        'user_id'           => $userId[$n],
        'license_number'    => $lic,
        'license_type'      => $type,
        'license_expiry'    => ts($NOW, 365 * 3, '00:00'),
        'restriction_codes' => $restr,
        'status'            => $status,
    ]);
}

// ── 5. Vehicle categories ────────────────────────────────────────
echo "Vehicle categories, vehicles, maintenance...\n";
$catDefs = [
    ['Van', 'Passenger vans for staff transport and light cargo.', 15, 1200],
    ['SUV/Utility Vehicle', 'Utility vehicles for site visits and client meetings.', 9, 250],
    ['Sedan', 'Sedans for client meetings and executive transport.', 5, 50],
    ['Light Truck', 'Light trucks for material and equipment delivery.', 3, 6000],
    ['Heavy Truck', 'Heavy trucks for bulk equipment and construction material hauling.', 3, 12000],
];
$catId = [];
foreach ($catDefs as $i => [$name, $desc, $pax, $cargo]) {
    $catId[$i + 1] = insert($pdo, 'vehicle_categories', [
        'category_name' => $name, 'description' => $desc,
        'max_passengers' => $pax, 'max_cargo_kg' => $cargo,
    ]);
}

// ── 6. Vehicles ───────────────────────────────────────────────────
// [cat#, plate, brand, model, year, color, fuel, pax, cargo_kg, odometer, gvwr, status, remarks, purpose_ids]
$vehicleDefs = [
    [1, 'NGP 1234', 'Toyota', 'HiAce', 2021, 'White', 'diesel', 15, 1200, 21760, 3500, VEH_AVAILABLE, null, '1,2,5'],
    [1, 'NGV 5678', 'Nissan', 'Urvan', 2019, 'White', 'diesel', 15, 1100, 45080, 3400, VEH_AVAILABLE, null, '1,2,5'],
    [1, 'WOM 4301', 'Mitsubishi', 'L300', 2017, 'White', 'diesel', 17, 1200, 78120, 2600, VEH_RESERVED, null, '1,2,5'],
    [2, 'NBM 2210', 'Toyota', 'Innova', 2020, 'Silver', 'gasoline', 7, 200, 30790, 2200, VEH_RESERVED, null, '4,1'],
    [2, 'NAX 7745', 'Isuzu', 'Crosswind', 2016, 'Silver', 'diesel', 9, 250, 92300, 2500, VEH_ON_TRIP, null, '4,1'],
    [3, 'NCK 3391', 'Toyota', 'Vios', 2022, 'Red', 'gasoline', 5, 50, 15400, 1500, VEH_AVAILABLE, null, '4,5'],
    [3, 'NDR 8802', 'Honda', 'City', 2018, 'Black', 'gasoline', 5, 50, 60800, 1500, VEH_MAINTENANCE, 'Scheduled for brake inspection.', '4,5'],
    [4, 'NEL 1150', 'Isuzu', 'Elf', 2017, 'White', 'diesel', 3, 3500, 101990, 4200, VEH_AVAILABLE, null, '2,3'],
    [4, 'NFH 6634', 'Hyundai', 'H100', 2020, 'Gray', 'diesel', 3, 1000, 40250, 3490, VEH_ON_TRIP, null, '2,3'],
    [5, 'NGT 9021', 'Isuzu', 'NPR85', 2021, 'White', 'diesel', 3, 8000, 18700, 8500, VEH_AVAILABLE, 'Heavy delivery and installation use.', '3'],
    [5, 'NHC 4478', 'Fuso', 'Canter', 2015, 'White', 'diesel', 3, 5000, 18410, 6800, VEH_RETIRED, 'Retired from active service — kept for records only.', null],
    [1, 'NIS 5567', 'Hyundai', 'Starex', 2019, 'Silver', 'diesel', 10, 800, 55600, 2900, VEH_RESERVED, null, '1,2,5'],
];
$vehId = [];
foreach ($vehicleDefs as $i => [$cat, $plate, $brand, $model, $year, $color, $fuel, $pax, $cargo, $odo, $gvwr, $status, $remarks, $purposeIds]) {
    $vehId[$i + 1] = insert($pdo, 'vehicles', [
        'category_id' => $catId[$cat], 'plate_number' => $plate, 'brand' => $brand, 'model' => $model,
        'year_model' => $year, 'color' => $color, 'fuel_type' => $fuel,
        'passenger_capacity' => $pax, 'cargo_capacity_kg' => $cargo,
        'current_odometer_km' => $odo, 'gross_weight_kg' => $gvwr, 'status' => $status,
        'remarks' => $remarks, 'preferred_purpose_ids' => $purposeIds,
    ]);
}

// ── 7. Vehicle maintenance history ──────────────────────────────
// [vehicle#, type, days_ago, odometer_at_service, cost, shop]  — next_service_km = +5000
$maintDefs = [
    [1, 'Oil Change', -60, 16700, 1800, 'QC Motor Works'],
    [1, 'Preventive Maintenance', -12, 21600, 3200, 'QC Motor Works'], // next_service_km ~26600, still far
    [2, 'Oil Change', -70, 39900, 1800, 'AutoCare PH'],
    [2, 'Oil Change', -18, 44700, 1800, 'AutoCare PH'], // next_service_km 49700, current 45080 — fine
    [3, 'Preventive Maintenance', -40, 73000, 4500, 'Fleet Maintenance Bay'],
    [4, 'Oil Change', -35, 29800, 1900, 'AutoCare PH'],
    [5, 'Brake Inspection', -25, 90100, 2600, 'QC Motor Works'],
    [6, 'Oil Change', -20, 14900, 1700, 'AutoCare PH'],
    [7, 'Tire Inspection', -50, 58200, 1200, 'Fleet Maintenance Bay'],
    [7, 'Brake Inspection', -3, 60800, 2100, 'Fleet Maintenance Bay'], // reason it's under_maintenance now
    [8, 'Preventive Maintenance', -45, 96500, 5200, 'QC Motor Works'],
    [8, 'Oil Change', -8, 101990, 2000, 'QC Motor Works'], // next_service_km 106990
    [9, 'Oil Change', -30, 36000, 1900, 'AutoCare PH'],
    [10, 'Preventive Maintenance', -55, 12000, 6800, 'Fleet Maintenance Bay'],
    [10, 'Engine Check', -10, 18500, 3400, 'Fleet Maintenance Bay'],
    [12, 'Oil Change', -22, 52400, 1800, 'AutoCare PH'],
];
foreach ($maintDefs as [$veh, $type, $daysAgo, $odo, $cost, $shop]) {
    insert($pdo, 'vehicle_maintenance', [
        'vehicle_id' => $vehId[$veh], 'recorded_by' => $userId[2], 'maintenance_type' => $type,
        'odometer_at_service' => $odo, 'service_date' => ts($NOW, $daysAgo, '00:00'),
        'next_service_km' => $odo + 5000, 'cost' => $cost, 'performed_by' => $shop,
    ]);
}
// Overdue-maintenance demo case: vehicle 2's odometer (45080) already past this record's next_service_km.
insert($pdo, 'vehicle_maintenance', [
    'vehicle_id' => $vehId[2], 'recorded_by' => $userId[2], 'maintenance_type' => 'Oil Change',
    'odometer_at_service' => 39900, 'service_date' => ts($NOW, -70, '00:00'),
    'next_service_km' => 44500, 'cost' => 1800, 'performed_by' => 'AutoCare PH',
]);

// ── 8. Trip purposes ─────────────────────────────────────────────
echo "Trip purposes, projects...\n";
$purposeDefs = [
    ['Site Visit', 'Inspecting an active project site.', 1, 3],
    ['Material Delivery', 'Delivering construction or office materials.', 0, null],
    ['Equipment Transport', 'Moving heavy equipment between sites or the warehouse.', 0, null],
    ['Client Meeting', 'Off-site meeting with a client, partner, or contractor.', 1, null],
    ['Employee Transport', 'Transporting staff to a site, event, or airport.', 0, null],
];
$purposeId = [];
foreach ($purposeDefs as $i => [$name, $desc, $reqProj, $maxPerProj]) {
    $purposeId[$i + 1] = insert($pdo, 'trip_purposes', [
        'purpose_name' => $name, 'description' => $desc,
        'requires_project' => $reqProj, 'max_per_project' => $maxPerProj, 'is_active' => 1,
    ]);
}

// ── 9. Projects ───────────────────────────────────────────────────
// [company#, name, code, location, days_ago_start, status, requested_by#, created_by#, reviewed_by#, rejection_reason]
$projectDefs = [
    [1, 'Quezon City Warehouse Expansion', 'PRJ-REMIX-01', 'Congressional Ave, Quezon City', -90, PROJ_ACTIVE, 6, 3, 3, null],
    [1, 'Bulacan Housing — Site B', 'PRJ-REMIX-02', 'Norzagaray, Bulacan', -150, PROJ_COMPLETED, 7, 3, 3, null],
    [2, 'Ideal Homes Subdivision — Site C', 'PRJ-IDEAL-01', 'Antipolo City, Rizal', -100, PROJ_ACTIVE, 8, 4, 4, null],
    [3, 'TenBuild Commercial Tower', 'PRJ-TNBLD-01', 'Trece Martires City, Cavite', -5, PROJ_PENDING, 10, 10, null, null],
    [3, 'Cavite Access Road', 'PRJ-TNBLD-02', 'Imus, Cavite', -30, PROJ_REJECTED, 11, 11, 5, 'Budget not approved for this quarter — resubmit with Q4 funding.'],
    [3, 'Site Improvement — Trece Martires', 'PRJ-TNBLD-03', 'Trece Martires City, Cavite', -70, PROJ_ACTIVE, 11, 5, 5, null],
];
$projId = [];
foreach ($projectDefs as $i => [$co, $name, $code, $loc, $daysAgo, $status, $reqBy, $createdBy, $revBy, $rejReason]) {
    $projId[$i + 1] = insert($pdo, 'projects', [
        'company_id' => $companyId[$co], 'created_by' => $userId[$createdBy],
        'project_name' => $name, 'project_code' => $code, 'location' => $loc,
        'start_date' => ts($NOW, $daysAgo, '00:00'),
        'description' => 'Justification on file — approved as part of ' . $name . '.',
        'status' => $status, 'requested_by' => $userId[$reqBy],
        'reviewed_by' => $revBy !== null ? $userId[$revBy] : null,
        'reviewed_at' => $revBy !== null ? ts($NOW, $daysAgo + 1, '10:00') : null,
        'rejection_reason' => $rejReason,
    ]);
}

echo "Reservations, gatepasses, trips...\n";

// ── 10. Reservation scenarios ────────────────────────────────────
// Each row drives everything downstream (gatepass/trip/incident/ai logs).
// requester# / dept# are userDefs/departments indices from above.
$R = [];
$R[] = [
    'scenario' => 'completed', 'co' => 1, 'requester' => 6, 'dept' => 2, 'purpose' => 1, 'proj' => 1,
    'dest' => 'Barangay Commonwealth Site, Quezon City', 'pax' => 4, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -19, 'depHm' => '08:00', 'retHm' => '17:00', 'reviewer' => 3,
    'vehicle' => 1, 'driver' => 13, 'odoStart' => 21400, 'odoEnd' => 21580, 'incident' => null,
];
$R[] = [
    'scenario' => 'completed', 'co' => 1, 'requester' => 7, 'dept' => 3, 'purpose' => 2, 'proj' => null,
    'dest' => 'ABC Hardware Supply, Caloocan City', 'pax' => 2, 'cargo' => 850, 'cargoDesc' => 'Cement, rebar, hand tools',
    'daysAgo' => -18, 'depHm' => '07:00', 'retHm' => '12:00', 'reviewer' => 3,
    'vehicle' => 2, 'driver' => 12, 'odoStart' => 44700, 'odoEnd' => 44890, 'incident' => null,
];
$R[] = [
    'scenario' => 'completed', 'co' => 2, 'requester' => 8, 'dept' => 5, 'purpose' => 4, 'proj' => 3,
    'dest' => 'Ideal Homes Site C Office, Antipolo City', 'pax' => 3, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -17, 'depHm' => '09:00', 'retHm' => '15:00', 'reviewer' => 4,
    'vehicle' => 4, 'driver' => 14, 'odoStart' => 30600, 'odoEnd' => 30790, 'incident' => null,
];
$R[] = [
    'scenario' => 'completed', 'co' => 2, 'requester' => 9, 'dept' => 6, 'purpose' => 3, 'proj' => null,
    'dest' => 'Ideal Homes Site C, Antipolo City', 'pax' => 2, 'cargo' => 6200, 'cargoDesc' => 'Backhoe attachment and steel forms',
    'daysAgo' => -16, 'depHm' => '06:00', 'retHm' => '14:00', 'reviewer' => 4,
    'vehicle' => 11, 'driver' => 15, 'odoStart' => 18200, 'odoEnd' => 18410, 'incident' => null,
];
$R[] = [
    'scenario' => 'completed', 'co' => 3, 'requester' => 10, 'dept' => 8, 'purpose' => 2, 'proj' => null,
    'dest' => 'TenBuild Warehouse, Trece Martires City', 'pax' => 2, 'cargo' => 2800, 'cargoDesc' => 'Office supplies and payroll documents',
    'daysAgo' => -15, 'depHm' => '07:30', 'retHm' => '13:00', 'reviewer' => 5,
    'vehicle' => 8, 'driver' => 16, 'odoStart' => 101800, 'odoEnd' => 101990, 'incident' => null,
];
$R[] = [
    // rejected at admin level — never assigned a vehicle
    'scenario' => 'rejected_admin', 'co' => 3, 'requester' => 11, 'dept' => 9, 'purpose' => 3, 'proj' => null,
    'dest' => 'Site B, Imus, Cavite', 'pax' => 2, 'cargo' => 4000, 'cargoDesc' => 'Scaffolding sets',
    'daysAgo' => -14, 'depHm' => '08:00', 'retHm' => '16:00', 'reviewer' => 5,
    'rejectionReason' => 'No available heavy-duty vehicle matches the cargo weight for this date — please resubmit for next week.',
];
$R[] = [
    'scenario' => 'completed', 'co' => 3, 'requester' => 11, 'dept' => 9, 'purpose' => 1, 'proj' => 6,
    'dest' => 'Trece Martires Site Improvement', 'pax' => 5, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -13, 'depHm' => '08:00', 'retHm' => '16:00', 'reviewer' => 5,
    'vehicle' => 1, 'driver' => 13, 'odoStart' => 21580, 'odoEnd' => 21760,
    'incident' => ['type' => 'traffic_delay', 'desc' => 'Heavy traffic along the national highway due to a road closure; arrived over an hour late.', 'resolved' => true, 'resHm' => '13:00', 'resNotes' => 'Delay noted; no further action needed. Site visit completed as planned.'],
];
$R[] = [
    // self-cancelled while pending
    'scenario' => 'cancelled_pending', 'co' => 1, 'requester' => 7, 'dept' => 3, 'purpose' => 2, 'proj' => null,
    'dest' => 'XYZ Depot, Valenzuela City', 'pax' => 1, 'cargo' => 300, 'cargoDesc' => 'Sample materials',
    'daysAgo' => -12, 'depHm' => '09:00', 'retHm' => '13:00',
    'cancelReason' => 'Delivery rescheduled by the client to next month.',
];
$R[] = [
    'scenario' => 'completed', 'co' => 1, 'requester' => 6, 'dept' => 2, 'purpose' => 4, 'proj' => 1,
    'dest' => 'Head Office Boardroom, Makati City', 'pax' => 3, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -11, 'depHm' => '13:00', 'retHm' => '18:00', 'reviewer' => 3,
    'vehicle' => 7, 'driver' => 12, 'odoStart' => 60600, 'odoEnd' => 60800, 'incident' => null,
];
$R[] = [
    // gatepass rejected by super_admin — vehicle released back to available
    'scenario' => 'rejected_gatepass', 'co' => 2, 'requester' => 9, 'dept' => 6, 'purpose' => 2, 'proj' => null,
    'dest' => 'Ideal Homes Site C, Antipolo City', 'pax' => 2, 'cargo' => 700, 'cargoDesc' => 'Paint and fixtures',
    'daysAgo' => -10, 'depHm' => '08:00', 'retHm' => '12:00', 'reviewer' => 4,
    'vehicle' => 8, 'driver' => 16, 'gpRejectionReason' => 'Cargo weight and route conflict with a higher-priority delivery already booked on this vehicle — please rebook.',
];
$R[] = [
    'scenario' => 'completed', 'co' => 2, 'requester' => 8, 'dept' => 5, 'purpose' => 2, 'proj' => null,
    'dest' => 'Ideal Homes Site C, Antipolo City', 'pax' => 2, 'cargo' => 900, 'cargoDesc' => 'Sample kitchen fixtures',
    'daysAgo' => -9, 'depHm' => '07:00', 'retHm' => '12:00', 'reviewer' => 4,
    'vehicle' => 2, 'driver' => 14, 'odoStart' => 44890, 'odoEnd' => 45080,
    'incident' => ['type' => 'breakdown', 'desc' => 'Flat tire along Sumulong Highway.', 'resolved' => true, 'resHm' => '10:30', 'resNotes' => 'Spare tire installed by driver; trip continued without further delay.'],
];
$R[] = [
    // approved then cancelled by fleet_admin — cascades to trip cancel
    'scenario' => 'cancelled_approved', 'co' => 3, 'requester' => 10, 'dept' => 8, 'purpose' => 4, 'proj' => 6,
    'dest' => 'TenBuild HQ Conference Room, Trece Martires City', 'pax' => 3, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -7, 'depHm' => '09:00', 'retHm' => '12:00', 'reviewer' => 5,
    'vehicle' => 6, 'driver' => 12, 'cancelReason' => 'Client meeting moved to their office; vehicle no longer required.', 'cancelBy' => 2,
];
$R[] = [
    // gatepass_pending — awaiting super_admin review right now
    'scenario' => 'gatepass_pending', 'co' => 1, 'requester' => 7, 'dept' => 3, 'purpose' => 4, 'proj' => 1,
    'dest' => 'Partner Office, BGC, Taguig City', 'pax' => 3, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -2, 'depHm' => '13:00', 'retHm' => '18:00', 'reviewer' => 3,
    'vehicle' => 3, 'driver' => 13,
    'ai' => ['top' => 3, 'score' => 84, 'notes' => 'Best available match on capacity and schedule with no maintenance conflicts.'],
];
$R[] = [
    // gatepass_pending — second one in the queue
    'scenario' => 'gatepass_pending', 'co' => 3, 'requester' => 10, 'dept' => 8, 'purpose' => 2, 'proj' => null,
    'dest' => 'TenBuild Yard, Trece Martires City', 'pax' => 2, 'cargo' => 800, 'cargoDesc' => 'Tools and small equipment',
    'daysAgo' => -1, 'depHm' => '06:00', 'retHm' => '12:00', 'reviewer' => 5,
    'vehicle' => 12, 'driver' => 16,
    'ai' => ['top' => 12, 'score' => 79, 'notes' => 'Adequate capacity; nearest available van with no scheduling conflict.'],
];
$R[] = [
    // approved, trip pending_start — ready for a live "Start Trip" demo
    'scenario' => 'approved', 'co' => 2, 'requester' => 8, 'dept' => 5, 'purpose' => 1, 'proj' => 3,
    'dest' => 'Ideal Homes Site C, Antipolo City', 'pax' => 3, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -1, 'depHm' => '14:00', 'retHm' => '19:00', 'reviewer' => 4,
    'vehicle' => 4, 'driver' => 14, 'gpDaysAgo' => -0.5,
    'ai' => ['top' => 4, 'score' => 88, 'notes' => 'Strong purpose fit for site visits; comfortable seating margin for the requested party.'],
];
$R[] = [
    // in_progress — currently on the road
    'scenario' => 'in_progress', 'co' => 1, 'requester' => 6, 'dept' => 2, 'purpose' => 1, 'proj' => 1,
    'dest' => 'Project Site, Norzagaray, Bulacan', 'destLat' => 14.9110, 'destLng' => 121.0520,
    'pax' => 4, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -0.3, 'depHm' => '07:00', 'retHm' => '16:00', 'reviewer' => 3,
    'vehicle' => 5, 'driver' => 12, 'startedHoursAgo' => 3.5, 'odoStart' => 92300,
    'ai' => ['top' => 5, 'score' => 91, 'notes' => 'Top match — ideal capacity, strong purpose fit, and a clear schedule.'],
];
$R[] = [
    // incident — open, unresolved right now
    'scenario' => 'incident', 'co' => 3, 'requester' => 11, 'dept' => 9, 'purpose' => 3, 'proj' => null,
    'dest' => 'Delivery Site, Imus, Cavite', 'destLat' => 14.4297, 'destLng' => 120.9367,
    'pax' => 2, 'cargo' => 900, 'cargoDesc' => 'Electrical fittings',
    'daysAgo' => -0.35, 'depHm' => '06:30', 'retHm' => '15:00', 'reviewer' => 5,
    'vehicle' => 9, 'driver' => 15, 'startedHoursAgo' => 4, 'odoStart' => 40250,
    'incidentOccurredHoursAgo' => 1.2,
    'incidentDetail' => ['type' => 'breakdown', 'desc' => 'Engine overheating warning light on; pulled over along Aguinaldo Highway.'],
    'ai' => ['top' => 9, 'score' => 75, 'notes' => 'Only light truck free for this window; cargo within capacity.'],
];
$R[] = [
    'scenario' => 'pending', 'co' => 1, 'requester' => 6, 'dept' => 2, 'purpose' => 3, 'proj' => null,
    'dest' => 'New Site, San Jose del Monte, Bulacan', 'pax' => 2, 'cargo' => 3000, 'cargoDesc' => 'Scaffolding and generator',
    'daysAgo' => -0.1, 'depHm' => '08:00', 'retHm' => '17:00',
];
$R[] = [
    'scenario' => 'pending', 'co' => 2, 'requester' => 9, 'dept' => 6, 'purpose' => 1, 'proj' => 3,
    'dest' => 'Ideal Homes Site D, Antipolo City', 'pax' => 4, 'cargo' => 0, 'cargoDesc' => null,
    'daysAgo' => -0.08, 'depHm' => '08:00', 'retHm' => '16:00',
];
$R[] = [
    'scenario' => 'pending', 'co' => 3, 'requester' => 11, 'dept' => 9, 'purpose' => 2, 'proj' => null,
    'dest' => 'TenBuild Site, Trece Martires City', 'pax' => 2, 'cargo' => 1500, 'cargoDesc' => 'Plumbing supplies',
    'daysAgo' => -0.05, 'depHm' => '07:00', 'retHm' => '13:00',
];

// Running IDs / notification+audit accumulators
$resId = $gpId = $tripId = $incId = [];
$notifRows = [];
$auditRows = [];

function pushNotif(array &$rows, int $userId, string $title, string $msg, string $type, ?int $refId, ?string $refType, string $createdAt, bool $read = false): void
{
    $rows[] = compact('userId', 'title', 'msg', 'type', 'refId', 'refType', 'createdAt', 'read');
}
function pushAudit(array &$rows, int $userId, string $action, ?string $table, ?int $recordId, ?array $old, ?array $new, string $createdAt): void
{
    $rows[] = compact('userId', 'action', 'table', 'recordId', 'old', 'new', 'createdAt');
}

foreach ($R as $n => $r) {
    $reqUserId  = $userId[$r['requester']];
    $deptRowId  = $deptId[$r['dept']];
    $createdAt  = ts($NOW, $r['daysAgo'], $r['depHm']);
    $depAt      = ts($NOW, $r['daysAgo'], $r['depHm']);
    $retAt      = ts($NOW, $r['daysAgo'], $r['retHm']);

    $status = match ($r['scenario']) {
        'pending' => RES_PENDING,
        'rejected_admin', 'rejected_gatepass' => RES_REJECTED,
        'cancelled_pending', 'cancelled_approved' => RES_CANCELLED,
        'gatepass_pending' => RES_GATEPASS_PENDING,
        'approved' => RES_APPROVED,
        'in_progress', 'incident' => RES_IN_PROGRESS,
        'completed' => RES_COMPLETED,
    };

    $data = [
        'reservation_code' => bin2hex(random_bytes(15)),
        'requested_by' => $reqUserId, 'department_id' => $deptRowId,
        'purpose_id' => $purposeId[$r['purpose']],
        'project_id' => $r['proj'] !== null ? $projId[$r['proj']] : null,
        'destination' => $r['dest'],
        'destination_lat' => $r['destLat'] ?? null, 'destination_lng' => $r['destLng'] ?? null,
        'trip_details' => null,
        'passenger_count' => $r['pax'], 'cargo_weight_kg' => $r['cargo'],
        'cargo_description' => $r['cargoDesc'],
        'departure_datetime' => $depAt, 'return_datetime' => $retAt,
        'status' => $status,
        'created_at' => $createdAt, 'updated_at' => $createdAt,
    ];

    if (isset($r['vehicle'])) {
        $data['assigned_vehicle_id'] = $vehId[$r['vehicle']];
        $data['assigned_driver_id']  = $userId[$r['driver']];
    }
    if (isset($r['reviewer']) && $r['scenario'] !== 'pending') {
        $data['reviewed_by'] = $userId[$r['reviewer']];
        $data['reviewed_at'] = ts($NOW, $r['daysAgo'], '07:30');
    }
    if (isset($r['rejectionReason'])) {
        $data['rejection_reason'] = $r['rejectionReason'];
    }
    if (isset($r['cancelReason'])) {
        $data['cancellation_reason'] = $r['cancelReason'];
        $data['cancelled_by'] = $userId[$r['cancelBy'] ?? $r['requester']];
    }
    if (isset($r['ai'])) {
        $data['ai_recommended_vehicle_id'] = $vehId[$r['ai']['top']];
        $data['ai_recommendation_score']   = $r['ai']['score'];
        $data['ai_recommendation_notes']   = $r['ai']['notes'];
    }

    $rid = insert($pdo, 'reservations', $data);
    $pdo->prepare('UPDATE reservations SET reservation_code = :c WHERE reservation_id = :id')
        ->execute([':c' => 'RES-2026-' . str_pad((string) $rid, 6, '0', STR_PAD_LEFT), ':id' => $rid]);
    $resId[$n] = $rid;

    pushNotif($notifRows, $reqUserId, 'Reservation Submitted', 'Your reservation to ' . $r['dest'] . ' has been submitted for review.', NOTIF_RESERVATION, $rid, 'reservation', $createdAt, true);
    pushAudit($auditRows, $reqUserId, 'RESERVATION_CREATED', 'reservations', $rid, null, ['destination' => $r['dest'], 'status' => 'pending'], $createdAt);

    // ── AI recommendation logs (candidate scoring) ──
    if (isset($r['ai'])) {
        $topVeh = $r['ai']['top'];
        $candidates = array_values(array_unique([$topVeh, $topVeh === 1 ? 2 : 1, $topVeh === 4 ? 6 : 4]));
        foreach ($candidates as $cIdx => $vNum) {
            $isTop = $vNum === $topVeh;
            insert($pdo, 'ai_recommendation_logs', [
                'reservation_id' => $rid, 'vehicle_id' => $vehId[$vNum],
                'score' => $isTop ? $r['ai']['score'] : max(20, $r['ai']['score'] - 15 - $cIdx * 12),
                'capacity_score' => $isTop ? 90 : 60, 'cargo_score' => 100, 'schedule_score' => $isTop ? 100 : 70,
                'purpose_fit_score' => $isTop ? 100 : 50, 'maintenance_score' => 100, 'weight_coding_score' => null,
                'disqualified' => 0, 'created_at' => ts($NOW, $r['daysAgo'], '07:00'),
            ]);
        }
    }

    // ── Admin review outcome ──
    if ($r['scenario'] === 'rejected_admin') {
        pushNotif($notifRows, $reqUserId, 'Reservation Rejected', 'Your reservation to ' . $r['dest'] . ' was rejected: ' . $r['rejectionReason'], NOTIF_RESERVATION, $rid, 'reservation', ts($NOW, $r['daysAgo'], '07:30'));
        pushAudit($auditRows, $userId[$r['reviewer']], 'RESERVATION_REJECTED', 'reservations', $rid, ['status' => 'pending'], ['status' => 'rejected'], ts($NOW, $r['daysAgo'], '07:30'));
        continue;
    }
    if ($r['scenario'] === 'cancelled_pending') {
        pushAudit($auditRows, $reqUserId, 'RESERVATION_CANCELLED', 'reservations', $rid, ['status' => 'pending'], ['status' => 'cancelled'], ts($NOW, $r['daysAgo'], '09:00'));
        continue;
    }
    if ($r['scenario'] === 'pending') {
        continue; // no further downstream rows
    }

    // From here on, every remaining scenario passed admin approval — vehicle/driver assigned.
    $approvedAt = ts($NOW, $r['daysAgo'], '07:30');
    pushNotif($notifRows, $reqUserId, 'Reservation Approved', 'Your reservation to ' . $r['dest'] . ' was approved and is awaiting gate pass clearance.', NOTIF_RESERVATION, $rid, 'reservation', $approvedAt);
    pushAudit($auditRows, $userId[$r['reviewer']], 'RESERVATION_APPROVED', 'reservations', $rid, ['status' => 'pending'], ['status' => 'gatepass_pending', 'assigned_vehicle_id' => $vehId[$r['vehicle']], 'assigned_driver_id' => $userId[$r['driver']]], $approvedAt);

    // ── Gatepass ──
    $gpCreatedAt = ts($NOW, $r['gpDaysAgo'] ?? $r['daysAgo'], '07:35');
    $gpData = [
        'reservation_id' => $rid, 'gatepass_code' => bin2hex(random_bytes(15)),
        'status' => 'pending', 'created_at' => $gpCreatedAt, 'updated_at' => $gpCreatedAt,
    ];
    $gid = insert($pdo, 'gatepasses', $gpData);
    $pdo->prepare('UPDATE gatepasses SET gatepass_code = :c WHERE gatepass_id = :id')
        ->execute([':c' => 'GP-2026-' . str_pad((string) $gid, 6, '0', STR_PAD_LEFT), ':id' => $gid]);
    $gpId[$n] = $gid;

    if ($r['scenario'] === 'rejected_gatepass') {
        $gpReviewedAt = ts($NOW, $r['daysAgo'], '11:00');
        $pdo->prepare('UPDATE gatepasses SET status = "rejected", reviewed_by = :rb, reviewed_at = :ra, rejection_reason = :reason, updated_at = :ua WHERE gatepass_id = :id')
            ->execute([':rb' => $userId[1], ':ra' => $gpReviewedAt, ':ua' => $gpReviewedAt, ':reason' => $r['gpRejectionReason'], ':id' => $gid]);
        $pdo->prepare('UPDATE reservations SET status = "rejected", rejection_reason = :reason, reviewed_by = :rb, reviewed_at = :ra WHERE reservation_id = :id')
            ->execute([':reason' => $r['gpRejectionReason'], ':rb' => $userId[1], ':ra' => $gpReviewedAt, ':id' => $rid]);
        pushNotif($notifRows, $reqUserId, 'Gate Pass Rejected', 'Your gate pass request was rejected: ' . $r['gpRejectionReason'], NOTIF_RESERVATION, $rid, 'reservation', $gpReviewedAt);
        pushAudit($auditRows, $userId[1], 'GATEPASS_REJECTED', 'gatepasses', $gid, ['status' => 'pending'], ['status' => 'rejected'], $gpReviewedAt);
        continue;
    }

    if ($r['scenario'] === 'gatepass_pending') {
        continue; // still awaiting super_admin review — no trip yet
    }

    // ── Gatepass approved -> trip created ──
    $gpApprovedAt = ts($NOW, ($r['gpDaysAgo'] ?? $r['daysAgo']), '11:00');
    $pdo->prepare('UPDATE gatepasses SET status = "approved", reviewed_by = :rb, reviewed_at = :ra, updated_at = :ua WHERE gatepass_id = :id')
        ->execute([':rb' => $userId[1], ':ra' => $gpApprovedAt, ':ua' => $gpApprovedAt, ':id' => $gid]);
    $pdo->prepare('UPDATE reservations SET status = "approved" WHERE reservation_id = :id')->execute([':id' => $rid]);
    pushNotif($notifRows, $reqUserId, 'Gate Pass Approved', $gpData['gatepass_code'] . ' is cleared to depart.', NOTIF_RESERVATION, $rid, 'reservation', $gpApprovedAt);
    pushNotif($notifRows, $userId[$r['driver']], 'New Trip Assigned', 'You have been assigned a new trip to ' . $r['dest'] . '.', NOTIF_TRIP, $rid, 'reservation', $gpApprovedAt);
    pushAudit($auditRows, $userId[1], 'GATEPASS_APPROVED', 'gatepasses', $gid, ['status' => 'pending'], ['status' => 'approved'], $gpApprovedAt);

    $tripData = [
        'reservation_id' => $rid, 'vehicle_id' => $vehId[$r['vehicle']], 'driver_id' => $userId[$r['driver']],
        'trip_status' => TRIP_PENDING_START, 'created_at' => $gpApprovedAt, 'updated_at' => $gpApprovedAt,
    ];
    $tid = insert($pdo, 'trips', $tripData);
    $tripId[$n] = $tid;

    if ($r['scenario'] === 'approved') {
        continue; // pending_start — left ready for a live "Start Trip" demo
    }

    if ($r['scenario'] === 'cancelled_approved') {
        $cancelAt = ts($NOW, $r['daysAgo'], '11:30');
        $pdo->prepare('UPDATE trips SET trip_status = "cancelled", cancellation_reason = :reason, cancelled_by = :cb, actual_return = :at, updated_at = :ua WHERE trip_id = :id')
            ->execute([':reason' => $r['cancelReason'], ':cb' => $userId[$r['cancelBy']], ':at' => $cancelAt, ':ua' => $cancelAt, ':id' => $tid]);
        $pdo->prepare('UPDATE reservations SET status = "cancelled", cancelled_by = :cb, cancellation_reason = :reason WHERE reservation_id = :id')
            ->execute([':cb' => $userId[$r['cancelBy']], ':reason' => $r['cancelReason'], ':id' => $rid]);
        pushNotif($notifRows, $reqUserId, 'Trip Cancelled', 'Your trip to ' . $r['dest'] . ' was cancelled: ' . $r['cancelReason'], NOTIF_TRIP, $rid, 'reservation', $cancelAt);
        pushAudit($auditRows, $userId[$r['cancelBy']], 'RESERVATION_CANCELLED', 'reservations', $rid, ['status' => 'approved'], ['status' => 'cancelled'], $cancelAt);
        continue;
    }

    // ── Trip started ──
    $startAt = isset($r['startedHoursAgo']) ? minsAgo($NOW, (int) round($r['startedHoursAgo'] * 60)) : ts($NOW, $r['daysAgo'], $r['depHm']);
    $odoStart = $r['odoStart'];
    $pdo->prepare('UPDATE trips SET trip_status = "in_progress", odometer_start_km = :o, actual_departure = :d, updated_at = :ua WHERE trip_id = :id')
        ->execute([':o' => $odoStart, ':d' => $startAt, ':ua' => $startAt, ':id' => $tid]);
    pushAudit($auditRows, $userId[$r['reviewer']], 'TRIP_STARTED', 'trips', $tid, ['trip_status' => 'pending_start'], ['trip_status' => 'in_progress', 'odometer_start_km' => $odoStart], $startAt);

    if (in_array($r['scenario'], ['in_progress', 'incident'], true)) {
        // Currently on the road — leave reservation 'in_progress', vehicle/driver already on_trip via seed.
        if ($r['scenario'] === 'incident') {
            $occurredAt = minsAgo($NOW, (int) round($r['incidentOccurredHoursAgo'] * 60));
            $iid = insert($pdo, 'trip_incidents', [
                'trip_id' => $tid, 'reported_by' => $userId[$r['driver']],
                'incident_type' => $r['incidentDetail']['type'], 'description' => $r['incidentDetail']['desc'],
                'occurred_at' => $occurredAt, 'created_at' => $occurredAt, 'updated_at' => $occurredAt,
            ]);
            $incId[$n] = $iid;
            $pdo->prepare('UPDATE trips SET trip_status = "incident", updated_at = :t WHERE trip_id = :id')
                ->execute([':t' => $occurredAt, ':id' => $tid]);
            pushAudit($auditRows, $userId[$r['driver']], 'INCIDENT_REPORTED', 'trip_incidents', $iid, null, ['trip_id' => $tid, 'incident_type' => $r['incidentDetail']['type']], $occurredAt);
            foreach ([1, 2, $r['reviewer']] as $notifyIdx) {
                pushNotif($notifRows, $userId[$notifyIdx], 'Incident Reported — Breakdown', $r['dest'] . ': ' . $r['incidentDetail']['desc'], NOTIF_INCIDENT, $tid, 'trip', $occurredAt);
            }
        }
        continue;
    }

    // ── Completed history ──
    $endAt = ts($NOW, $r['daysAgo'], $r['retHm']);
    $odoEnd = $r['odoEnd'];
    $pdo->prepare('UPDATE trips SET trip_status = "completed", odometer_end_km = :o, actual_return = :d, updated_at = :ua WHERE trip_id = :id')
        ->execute([':o' => $odoEnd, ':d' => $endAt, ':ua' => $endAt, ':id' => $tid]);
    $pdo->prepare('UPDATE reservations SET status = "completed" WHERE reservation_id = :id')->execute([':id' => $rid]);
    pushNotif($notifRows, $reqUserId, 'Trip Completed', 'Your trip to ' . $r['dest'] . ' has been completed.', NOTIF_TRIP, $rid, 'reservation', $endAt, true);
    pushAudit($auditRows, $userId[$r['reviewer']], 'TRIP_COMPLETED', 'trips', $tid, ['trip_status' => 'in_progress'], ['trip_status' => 'completed', 'odometer_end_km' => $odoEnd], $endAt);

    if ($r['incident'] !== null) {
        $occurredAt = ts($NOW, $r['daysAgo'], '10:00');
        $iid = insert($pdo, 'trip_incidents', [
            'trip_id' => $tid, 'reported_by' => $userId[$r['driver']],
            'incident_type' => $r['incident']['type'], 'description' => $r['incident']['desc'],
            'occurred_at' => $occurredAt,
            'resolved' => $r['incident']['resolved'] ? 1 : 0,
            'resolution_notes' => $r['incident']['resolved'] ? $r['incident']['resNotes'] : null,
            'created_at' => $occurredAt,
            'updated_at' => $r['incident']['resolved'] ? ts($NOW, $r['daysAgo'], $r['incident']['resHm']) : $occurredAt,
        ]);
        $incId[$n] = $iid;
        pushAudit($auditRows, $userId[$r['driver']], 'INCIDENT_REPORTED', 'trip_incidents', $iid, null, ['trip_id' => $tid, 'incident_type' => $r['incident']['type']], $occurredAt);
        if ($r['incident']['resolved']) {
            $resAt = ts($NOW, $r['daysAgo'], $r['incident']['resHm']);
            pushAudit($auditRows, $userId[$r['reviewer']], 'INCIDENT_RESOLVED', 'trip_incidents', $iid, ['resolved' => 0], ['resolved' => 1], $resAt);
        }
    }
}

// ── 11. GPS breadcrumbs for the two currently-active trips ───────
echo "GPS tracking points...\n";
function gpsRoute(PDO $pdo, int $tripId, float $fromLat, float $fromLng, float $toLat, float $toLng, DateTime $now, float $startedHoursAgo, int $points): void
{
    $startMins = (int) round($startedHoursAgo * 60);
    for ($i = 0; $i < $points; $i++) {
        $frac = $i / max(1, $points - 1);
        $lat  = $fromLat + ($toLat - $fromLat) * $frac + (mt_rand(-15, 15) / 100000);
        $lng  = $fromLng + ($toLng - $fromLng) * $frac + (mt_rand(-15, 15) / 100000);
        $minsAgoVal = (int) round($startMins * (1 - $frac));
        insert($pdo, 'gps_tracking_logs', [
            'trip_id' => $tripId, 'latitude' => $lat, 'longitude' => $lng,
            'speed_kph' => mt_rand(20, 60), 'heading_degrees' => mt_rand(0, 359), 'accuracy_meters' => mt_rand(5, 20),
            'logged_at' => minsAgo($now, $minsAgoVal),
        ]);
    }
}
if (isset($tripId[15])) { // in_progress: warehouse -> Norzagaray, Bulacan
    gpsRoute($pdo, $tripId[15], 14.6760, 121.0437, 14.9110, 121.0520, $NOW, 3.5, 10);
}
if (isset($tripId[16])) { // incident: warehouse -> Imus, Cavite (stalled near breakdown point)
    gpsRoute($pdo, $tripId[16], 14.6760, 121.0437, 14.4600, 120.9700, $NOW, 4, 9);
}

// ── 12. Extra standalone notifications (maintenance, system) ─────
echo "Notifications, audit logs...\n";
pushNotif($notifRows, $userId[2], 'Maintenance Due Soon', 'Vehicle NGV 5678 (Nissan Urvan) is over its scheduled maintenance interval — 580 km past next service.', NOTIF_MAINTENANCE, $vehId[2], 'maintenance', ts($NOW, -1, '09:00'));
pushNotif($notifRows, $userId[1], 'Maintenance Due Soon', 'Vehicle NGP 1234 (Toyota HiAce) is approaching its next scheduled service.', NOTIF_MAINTENANCE, $vehId[1], 'maintenance', ts($NOW, -2, '09:00'), true);
pushNotif($notifRows, $userId[3], 'Vehicle Under Maintenance', 'Vehicle NDR 8802 (Honda City) was marked under maintenance for a brake inspection.', NOTIF_MAINTENANCE, $vehId[7], 'maintenance', ts($NOW, -3, '08:00'), true);

foreach ($notifRows as $n) {
    insert($pdo, 'notifications', [
        'user_id' => $n['userId'], 'title' => $n['title'], 'message' => $n['msg'], 'type' => $n['type'],
        'reference_id' => $n['refId'], 'reference_type' => $n['refType'],
        'is_read' => $n['read'] ? 1 : 0, 'created_at' => $n['createdAt'],
    ]);
}

// ── 13. Audit log — extra system/account events for a fuller trail ─
$extraAudit = [
    [1, 'LOGIN_SUCCESS', 'users', $userId[1], -20, '08:00'],
    [2, 'LOGIN_SUCCESS', 'users', $userId[2], -19, '08:05'],
    [3, 'LOGIN_SUCCESS', 'users', $userId[3], -18, '07:55'],
    [4, 'LOGIN_SUCCESS', 'users', $userId[4], -17, '08:10'],
    [5, 'LOGIN_SUCCESS', 'users', $userId[5], -16, '07:50'],
    [1, 'LOGIN_SUCCESS', 'users', $userId[1], -1, '07:30'],
    [2, 'LOGIN_SUCCESS', 'users', $userId[2], -0.2, '07:45'],
    [3, 'LOGIN_SUCCESS', 'users', $userId[3], -0.1, '08:00'],
    [1, 'SETTINGS_UPDATED', 'system_settings', null, -25, '10:00'],
    [1, 'DEPARTMENT_CREATED', 'departments', $deptId[1], -95, '09:00'],
    [1, 'DEPARTMENT_CREATED', 'departments', $deptId[4], -95, '09:05'],
    [1, 'DEPARTMENT_CREATED', 'departments', $deptId[7], -95, '09:10'],
    [1, 'USER_CREATED', 'users', $userId[3], -95, '09:20'],
    [1, 'USER_CREATED', 'users', $userId[4], -95, '09:25'],
    [1, 'USER_CREATED', 'users', $userId[5], -95, '09:30'],
    [3, 'MAINTENANCE_LOGGED', 'vehicle_maintenance', $vehId[7], -3, '08:15'],
    [2, 'MAINTENANCE_LOGGED', 'vehicle_maintenance', $vehId[8], -8, '09:00'],
];
foreach ($extraAudit as [$actorIdx, $action, $table, $recordId, $daysAgo, $hm]) {
    pushAudit($auditRows, $userId[$actorIdx], $action, $table, $recordId, null, null, ts($NOW, $daysAgo, $hm));
}

foreach ($auditRows as $a) {
    insert($pdo, 'audit_logs', [
        'user_id' => $a['userId'], 'action' => $a['action'], 'table_name' => $a['table'], 'record_id' => $a['recordId'],
        'old_values' => $a['old'] !== null ? json_encode($a['old'], JSON_UNESCAPED_UNICODE) : null,
        'new_values' => $a['new'] !== null ? json_encode($a['new'], JSON_UNESCAPED_UNICODE) : null,
        'ip_address' => '192.168.1.' . mt_rand(10, 60), 'created_at' => $a['createdAt'],
    ]);
}

// ── 14. System settings ───────────────────────────────────────────
echo "System settings...\n";
$settings = [
    'warehouse_address'       => '88 Congressional Avenue, Barangay Bahay Toro, Quezon City, Metro Manila',
    'warehouse_lat'           => '14.67600000',
    'warehouse_lng'           => '121.04370000',
    'system_name'             => 'MoveOps',
    'company_name'             => 'Remix Construction and Trading Corporation',
    'reservation_prefix'      => 'RES',
    'gatepass_prefix'         => 'GP',
    'employee_id_prefix'      => 'EMP',
    'maintenance_interval_km' => '5000',
];
foreach ($settings as $key => $value) {
    $stmt = $pdo->prepare('SELECT setting_id FROM system_settings WHERE setting_key = :k');
    $stmt->execute([':k' => $key]);
    if ($stmt->fetch()) {
        $pdo->prepare('UPDATE system_settings SET setting_value = :v, updated_by = :u WHERE setting_key = :k')
            ->execute([':v' => $value, ':u' => $userId[1], ':k' => $key]);
    } else {
        insert($pdo, 'system_settings', ['setting_key' => $key, 'setting_value' => $value, 'updated_by' => $userId[1]]);
    }
}

echo "\nDone.\n";

// ── Summary ────────────────────────────────────────────────────────
$counts = [];
foreach (['companies','departments','users','driver_profiles','vehicle_categories','vehicles',
          'vehicle_maintenance','trip_purposes','projects','reservations','gatepasses',
          'ai_recommendation_logs','trips','trip_incidents','gps_tracking_logs','notifications',
          'audit_logs','system_settings'] as $t) {
    $counts[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}
foreach ($counts as $t => $c) {
    printf("  %-24s %d\n", $t, $c);
}

echo "\nAll seeded accounts use password: password\n";
echo "Key logins:\n";
echo "  superadmin@lvms.test        (super_admin)\n";
echo "  fleetadmin@lvms.test        (fleet_admin)\n";
echo "  admin.remix@lvms.test       (admin, REMIX)\n";
echo "  admin.ideal@lvms.test       (admin, IDEAL)\n";
echo "  admin.tenbuild@lvms.test    (admin, TenBuild)\n";
echo "  juan.delacruz@lvms.test     (employee, REMIX) — has 2 pending Site Visit slots left on project PRJ-IDEAL... see reservation review queue\n";
echo "  josefino.martinez@lvms.test (driver, currently on_trip)\n";
