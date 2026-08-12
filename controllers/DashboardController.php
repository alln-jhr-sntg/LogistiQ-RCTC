<?php

class DashboardController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/dashboard/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    public function superAdmin(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN);

        $companyModel      = new CompanyModel();
        $userModel         = new UserModel();
        $gatepassModel     = new GatepassModel();
        $tripModel         = new TripModel();
        $reservationModel  = new ReservationModel();
        $vehicleModel      = new VehicleModel();

        $pendingGatepasses = $gatepassModel->findPending();
        $vehicleCounts     = $vehicleModel->countByStatus();

        $assigned = 0;
        foreach (VEH_ASSIGNED_STATUSES as $status) {
            $assigned += $vehicleCounts[$status] ?? 0;
        }

        $this->render('super_admin', [
            'page_title' => 'Dashboard',
            'stats' => [
                'total_companies'  => $companyModel->countAll(),
                'total_users'      => $userModel->countAll(),
                'gatepass_pending' => count($pendingGatepasses),
                'active_trips'     => $tripModel->countByStatuses(TRIP_TRACKING_STATUSES),
            ],
            'pending_gatepasses'  => array_slice($pendingGatepasses, 0, 5),
            'companies'           => $companyModel->findAllWithStats(),
            'recent_reservations' => array_slice($reservationModel->findAll(), 0, 5),
            'fleet_summary' => [
                'total'       => array_sum($vehicleCounts),
                'available'   => $vehicleCounts[VEH_AVAILABLE] ?? 0,
                'assigned'    => $assigned,
                'maintenance' => $vehicleCounts[VEH_MAINTENANCE] ?? 0,
            ],
        ]);
    }

    public function fleetAdmin(): void
    {
        Auth::requireRole(ROLE_FLEET_ADMIN);

        $reservationModel = new ReservationModel();
        $tripModel        = new TripModel();
        $vehicleModel     = new VehicleModel();

        $pendingReservations = $reservationModel->findAll(RES_PENDING);
        $vehicleCounts       = $vehicleModel->countByStatus();
        $maintenanceDue      = $vehicleModel->findMaintenanceDue();
        $activeTrips         = $tripModel->findByStatuses(TRIP_TRACKING_STATUSES);

        $assigned = 0;
        foreach (VEH_ASSIGNED_STATUSES as $status) {
            $assigned += $vehicleCounts[$status] ?? 0;
        }

        $this->render('fleet_admin', [
            'page_title' => 'Dashboard',
            'stats' => [
                'pending_reservations' => count($pendingReservations),
                'active_trips'         => count($activeTrips),
                'vehicles_available'   => $vehicleCounts[VEH_AVAILABLE] ?? 0,
                'maintenance_due'      => count($maintenanceDue),
            ],
            'pending_reservations' => array_slice($pendingReservations, 0, 5),
            'fleet_availability' => [
                'available'   => $vehicleCounts[VEH_AVAILABLE] ?? 0,
                'assigned'    => $assigned,
                'maintenance' => $vehicleCounts[VEH_MAINTENANCE] ?? 0,
            ],
            'maintenance_due' => $maintenanceDue,
            'active_trips'    => $activeTrips,
        ]);
    }

    public function admin(): void
    {
        Auth::requireRole(ROLE_ADMIN);

        $companyId = (int) Auth::companyId();

        $projectModel      = new ProjectModel();
        $reservationModel  = new ReservationModel();
        $userModel         = new UserModel();
        $notificationModel = new NotificationModel();

        $pendingReservations  = $reservationModel->findForCompany($companyId, RES_PENDING);
        $approvedReservations = $reservationModel->findForCompany($companyId, RES_APPROVED);

        // "Upcoming" = approved reservations whose departure hasn't happened yet.
        $upcomingReservations = array_values(array_filter(
            $approvedReservations,
            fn(array $r): bool => strtotime($r['departure_datetime']) >= time()
        ));
        usort(
            $upcomingReservations,
            fn(array $a, array $b): int => strtotime($a['departure_datetime']) <=> strtotime($b['departure_datetime'])
        );

        $this->render('admin', [
            'page_title' => 'Dashboard',
            'stats' => [
                'pending_projects'      => $projectModel->countPendingByCompany($companyId),
                'pending_reservations'  => count($pendingReservations),
                'approved_reservations' => count($approvedReservations),
                'company_employees'     => $userModel->countByCompany($companyId),
            ],
            'recent_projects'       => $projectModel->findRecentByCompany($companyId, 5),
            'upcoming_reservations' => array_slice($upcomingReservations, 0, 5),
            'recent_activity'       => $notificationModel->getByUser((int) Auth::id(), 5),
            'company_id'            => $companyId,
        ]);
    }

    public function employee(): void
    {
        Auth::requireRole(ROLE_EMPLOYEE);

        $userId = (int) Auth::id();

        $reservationModel  = new ReservationModel();
        $tripModel         = new TripModel();
        $notificationModel = new NotificationModel();

        // Upcoming trip = the soonest trip that's confirmed but not yet started.
        $pendingStartTrips = $tripModel->findForEmployee($userId, TRIP_PENDING_START);
        usort(
            $pendingStartTrips,
            fn(array $a, array $b): int => strtotime($a['departure_datetime']) <=> strtotime($b['departure_datetime'])
        );

        $this->render('employee', [
            'page_title' => 'Dashboard',
            'stats' => [
                'pending'         => count($reservationModel->findForEmployee($userId, RES_PENDING)),
                'approved'        => count($reservationModel->findForEmployee($userId, RES_APPROVED)),
                'active_trips'    => $tripModel->countForEmployeeByStatuses($userId, TRIP_TRACKING_STATUSES),
                'completed_trips' => $tripModel->countForEmployeeByStatuses($userId, [TRIP_COMPLETED]),
            ],
            'upcoming_trip'   => $pendingStartTrips[0] ?? null,
            'recent_activity' => $notificationModel->getByUser($userId, 5),
        ]);
    }

    public function driver(): void
    {
        Auth::requireRole(ROLE_DRIVER);
        $this->render('driver', [
            'page_title' => 'Dashboard',
        ]);
    }
}
