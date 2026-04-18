<?php
class ReservationController {
    private function render(string $view, array $data = []): void { extract($data); $content_view = __DIR__ . '/../views/reservations/' . $view . '.php'; require_once __DIR__ . '/../views/layouts/main.php'; }
    public function index(): void   { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE); $this->render('index', ['page_title' => 'Reservations']); }
    public function create(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE); $this->render('create', ['page_title' => 'New Reservation']); }
    public function store(): void   { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE); Helpers::setFlash('success', 'Reservation submitted. (Capstone 1)'); Helpers::redirect('/reservations'); }
    public function detail(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        // Cap 1: mock reservations keyed by ID
        $mockReservations = [
            '1' => ['code' => 'RES-2025-00001', 'status' => 'pending',     'destination' => 'Pasig City Warehouse',      'requester' => 'Juan dela Cruz',  'department' => 'Engineering', 'company' => 'REMIX',    'purpose' => 'Site Visit',       'departure' => 'Dec 10, 2025 08:00 AM', 'return' => 'Dec 10, 2025 05:00 PM', 'passengers' => 3,  'cargo' => '0 kg',           'trip_details' => 'Monthly inspection of materials at site.', 'submitted' => 'Dec 9, 2025'],
            '2' => ['code' => 'RES-2025-00002', 'status' => 'approved',    'destination' => 'Quezon City Site',          'requester' => 'Maria Santos',    'department' => 'Operations',  'company' => 'IDEAL',    'purpose' => 'Material Delivery','departure' => 'Dec 11, 2025 07:00 AM', 'return' => 'Dec 11, 2025 06:00 PM', 'passengers' => 2,  'cargo' => '250 kg — Cement bags', 'trip_details' => 'Delivery of cement and rebar to site.', 'submitted' => 'Dec 8, 2025'],
            '3' => ['code' => 'RES-2025-00003', 'status' => 'in_progress', 'destination' => 'Laguna Construction Site',  'requester' => 'Pedro Reyes',     'department' => 'Logistics',   'company' => 'TENBUILD', 'purpose' => 'Equipment Transport', 'departure' => 'Dec 12, 2025 06:00 AM', 'return' => 'Dec 12, 2025 08:00 PM', 'passengers' => 4,  'cargo' => '500 kg — Equipment','trip_details' => 'Heavy equipment transport to Laguna site.', 'submitted' => 'Dec 10, 2025'],
            '4' => ['code' => 'RES-2025-00004', 'status' => 'completed',   'destination' => 'Makati Office',             'requester' => 'Ana Lim',         'department' => 'Admin',       'company' => 'REMIX',    'purpose' => 'Client Meeting',  'departure' => 'Dec 08, 2025 09:00 AM', 'return' => 'Dec 08, 2025 04:00 PM', 'passengers' => 2,  'cargo' => 'None',           'trip_details' => 'Quarterly client meeting at Makati headquarters.', 'submitted' => 'Dec 5, 2025'],
        ];

        // Get ID from URL — e.g. ?url=reservations/3
        $urlParts = explode('/', trim($_GET['url'] ?? '', '/'));
        $id = $urlParts[1] ?? '1';
        $reservation = $mockReservations[$id] ?? $mockReservations['1'];

        $this->render('detail', ['page_title' => 'Reservation Detail', 'reservation' => $reservation]);
    }
    public function review(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('review', ['page_title' => 'Review Reservation']); }
    public function approve(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Reservation approved. (Capstone 1)'); Helpers::redirect('/reservations'); }
    public function reject(): void  { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Reservation rejected. (Capstone 1)'); Helpers::redirect('/reservations'); }
    public function purposes(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); $this->render('purposes', ['page_title' => 'Trip Purposes']); }
    public function storePurpose(): void { Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN); Helpers::setFlash('success', 'Trip purpose saved. (Capstone 1)'); Helpers::redirect('/reservations/purposes'); }
}
