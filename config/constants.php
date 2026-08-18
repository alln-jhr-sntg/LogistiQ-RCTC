<?php

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_FLEET_ADMIN = 'fleet_admin';
const ROLE_ADMIN       = 'admin';
const ROLE_EMPLOYEE    = 'employee';
const ROLE_DRIVER      = 'driver';

const ROLES = [ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_DRIVER];

// Display labels — ucfirst('fleet_admin') renders "Fleet_admin", which is wrong
// everywhere it appears. Use this map in dropdowns, badges and the sidebar.
const ROLE_LABELS = [
    ROLE_SUPER_ADMIN => 'Super Admin',
    ROLE_FLEET_ADMIN => 'Fleet Admin',
    ROLE_ADMIN       => 'Admin',
    ROLE_EMPLOYEE    => 'Employee',
    ROLE_DRIVER      => 'Driver',
];

// Which roles each actor may assign when creating or editing a user.
// super_admin is deliberately absent even from its own row: nobody can
// promote another account to super_admin (or demote their own) through the
// app. A new super_admin is created directly in the database.
const ROLE_ASSIGNABLE = [
    ROLE_SUPER_ADMIN => [ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_DRIVER],
    ROLE_FLEET_ADMIN => [ROLE_EMPLOYEE, ROLE_DRIVER],
    ROLE_ADMIN       => [ROLE_EMPLOYEE],
];

const RES_PENDING          = 'pending';
const RES_APPROVED         = 'approved';
const RES_GATEPASS_PENDING = 'gatepass_pending';
const RES_REJECTED         = 'rejected';
const RES_CANCELLED        = 'cancelled';
const RES_IN_PROGRESS      = 'in_progress';
const RES_COMPLETED        = 'completed';

// Display labels for the Reservations list status filter dropdown.
const RES_STATUS_LABELS = [
    RES_PENDING          => 'Pending',
    RES_APPROVED         => 'Approved',
    RES_GATEPASS_PENDING => 'Gatepass',
    RES_IN_PROGRESS      => 'In Progress',
    RES_COMPLETED        => 'Completed',
    RES_REJECTED         => 'Rejected',
    RES_CANCELLED        => 'Cancelled',
];
// Gate pass exists in the schema only (gatepasses table + the reservation status
// above). No controller, model, route or view yet — built in a later step. When it
// lands, its review/approve/reject guards are super_admin ONLY.

// Reservation statuses that hold a vehicle and a driver for their booked
// window — a row in any of these blocks an overlapping assignment to the
// same vehicle or driver. Used by VehicleModel/DriverProfileModel conflict
// queries. 'gatepass_pending' matters here: ReservationModel::approve()
// writes that status (not 'approved') the moment a vehicle/driver is held;
// the row only becomes 'approved' later, once GatepassController::approve()
// clears the gatepass. Omitting it would leave every reservation sitting in
// the gatepass queue invisible to the conflict check.
const RES_BLOCKING_STATUSES = [RES_GATEPASS_PENDING, RES_APPROVED, RES_IN_PROGRESS];

const TRIP_PENDING_START = 'pending_start';
const TRIP_IN_PROGRESS   = 'in_progress';
const TRIP_COMPLETED     = 'completed';
const TRIP_INCIDENT      = 'incident';
const TRIP_CANCELLED     = 'cancelled';

// Display labels for the Trips list status filter dropdown.
const TRIP_STATUS_LABELS = [
    TRIP_PENDING_START => 'Pending Start',
    TRIP_IN_PROGRESS   => 'In Progress',
    TRIP_COMPLETED     => 'Completed',
    TRIP_INCIDENT      => 'Incident',
    TRIP_CANCELLED     => 'Cancelled',
];

// Statuses a trip cannot leave. Guards read this instead of comparing
// to 'completed' by hand, so adding a future terminal status is one edit.
const TRIP_TERMINAL_STATUSES = ['completed', 'cancelled'];

// Statuses during which GPS pings are accepted and the Live Map is shown.
// Tracking deliberately continues through 'incident' — an accident is
// exactly when the vehicle's location matters most — and only stops once
// the trip reaches a terminal status.
const TRIP_TRACKING_STATUSES = ['in_progress', 'incident'];

const PROJ_PENDING   = 'pending';
const PROJ_ACTIVE    = 'active';
const PROJ_COMPLETED = 'completed';
const PROJ_REJECTED  = 'rejected';

// Statuses an admin may set directly on the project edit form. A project
// finishes and a project reopens, so active <-> completed moves freely in
// both directions.
const PROJ_EDITABLE_STATUSES = [PROJ_ACTIVE, PROJ_COMPLETED];

// Rows in these statuses refuse ANY status change through the edit form,
// for every role including super_admin — approve() and reject() are the
// only exits from pending, and rejected is terminal (the requester files
// a new request rather than resubmitting in place).
const PROJ_LOCKED_STATUSES = [PROJ_PENDING, PROJ_REJECTED];

const VEH_AVAILABLE   = 'available';
const VEH_RESERVED    = 'reserved';
const VEH_ON_TRIP     = 'on_trip';
const VEH_MAINTENANCE = 'under_maintenance';
const VEH_RETIRED     = 'retired';

// Vehicles currently tied to a reservation or an active trip — i.e. not
// free to dispatch. Used by dashboard fleet-summary cards to report an
// "assigned" bucket distinct from available/maintenance/retired.
const VEH_ASSIGNED_STATUSES = [VEH_RESERVED, VEH_ON_TRIP];

const DRV_AVAILABLE = 'available';
const DRV_ON_TRIP   = 'on_trip';
const DRV_OFF_DUTY  = 'off_duty';
const DRV_ON_LEAVE  = 'on_leave';

const NOTIF_RESERVATION = 'reservation';
const NOTIF_TRIP        = 'trip';
const NOTIF_MAINTENANCE = 'maintenance';
const NOTIF_SYSTEM      = 'system';
const NOTIF_INCIDENT    = 'incident';

// Display labels for the Notifications list type filter dropdown.
const NOTIF_TYPE_LABELS = [
    NOTIF_RESERVATION => 'Reservation',
    NOTIF_TRIP        => 'Trip',
    NOTIF_MAINTENANCE => 'Maintenance',
    NOTIF_SYSTEM      => 'System',
    NOTIF_INCIDENT    => 'Incident',
];

const MAINTENANCE_INTERVAL_KM = 5000;

// ── VehicleRecommendationService tuning ───────────────────────
// Every number the rule-based weighted scoring engine runs on lives here so
// none of it is buried as a magic literal inside the service classes.

// Scores are integers on a 0-100 scale (not 0.00-1.00), stored as
// TINYINT UNSIGNED in ai_recommendation_logs and reservations.
const SCORE_MAX = 100;

// Weighted criteria, in percentage points — MUST sum to SCORE_MAX (enforced
// by an assertion in VehicleRecommendationService). A sub-score of null
// means "not applicable to this request" (e.g. no cargo requested), and
// that criterion's weight is dropped from the denominator for that vehicle
// rather than counted as zero — see VehicleRecommendationService::recommend().
const RECOMMENDATION_WEIGHTS = [
    'capacity'      => 20,
    'cargo'         => 15,
    'schedule'      => 15,
    'purpose_fit'   => 25,
    'maintenance'   => 15,
    'weight_coding' => 10,
];

// Capacity scoring: seats in excess of the requested passenger count that
// drive the capacity sub-score down to 0 (a linear ramp from 0 excess seats
// = 100 down to this many excess seats = 0). Replaces the old
// requested/available ratio, which cratered for small parties regardless of
// how well-suited the vehicle actually was.
const CAPACITY_EXCESS_SEATS_FULL_PENALTY = 12;

// Schedule scoring: hours of clear space on either side of the requested
// window that earn a full (100) schedule score. A vehicle with no
// neighboring booking at all also scores 100.
const SCHEDULE_BUFFER_HOURS = 24;

// Philippine LTO weight-coding truck ban, modeled as a time-window soft
// criterion (see WeightCodingService) instead of the old unconditional
// >4,500kg hard exclusion, which made every truck in the fleet permanently
// unrecommendable. Vehicles at or under this GVWR are unaffected — the
// criterion doesn't apply to them at all (null sub-score, not a penalty).
const TRUCK_BAN_GVWR_KG = 4500;
// ISO-8601 day-of-week numbers, Monday=1 .. Sunday=7.
const TRUCK_BAN_DAYS    = [1, 2, 3, 4, 5];
const TRUCK_BAN_WINDOWS = [['06:00', '10:00'], ['17:00', '22:00']];

// Mirrors the vehicle_maintenance.maintenance_type ENUM (database/migrations/
// 2026_08_15_maintenance_type_enum.sql) — the single source of truth for the
// "Log Maintenance Record" type dropdown and the maintenance report/history
// type filters, so a filter option always matches a value the DB will accept.
const MAINTENANCE_TYPES = [
    'Oil Change',
    'Preventive Maintenance',
    'Brake Inspection',
    'Tire Inspection',
    'Engine Check',
    'Repair',
    'Other',
];

const ROLE_DASHBOARD = [
    ROLE_SUPER_ADMIN => '/dashboard/super_admin',
    ROLE_FLEET_ADMIN => '/dashboard/fleet_admin',
    ROLE_ADMIN       => '/dashboard/admin',
    ROLE_EMPLOYEE    => '/dashboard/employee',
    ROLE_DRIVER      => '/dashboard/driver',
];

// Google Maps JavaScript API key used by live map.
// Required APIs: Maps JavaScript API
require_once __DIR__ . '/secrets.php';
