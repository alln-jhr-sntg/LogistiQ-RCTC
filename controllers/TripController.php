<?php

class TripController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/trips/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /trips — list differs by role
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_DRIVER);
        $this->render('index', ['page_title' => Auth::isDriver() ? 'My Trips' : 'Trips']);
    }

    // GET /trips/[id] — detail differs by role
    public function detail(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_DRIVER, ROLE_EMPLOYEE);
        $this->render('detail', ['page_title' => 'Trip Detail']);
    }

    // GET /trips/[id]/active — driver active trip screen
    public function active(): void
    {
        Auth::requireRole(ROLE_DRIVER);
        $this->render('active', ['page_title' => 'Active Trip']);
    }

    // GET /trips/[id]/map — admin live map
    public function liveMap(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        $this->render('live_map', ['page_title' => 'Live Map']);
    }

    // POST /trips/[id]/start
    public function start(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_DRIVER);
        Helpers::setFlash('success', 'Trip started. (Capstone 1 — not saved to DB)');
        if (Auth::isDriver()) {
            Helpers::redirect('/trips/1/active');
        } else {
            Helpers::redirect('/trips/1');
        }
    }

    // POST /trips/[id]/complete
    public function complete(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_DRIVER);
        Helpers::setFlash('success', 'Trip completed. (Capstone 1 — not saved to DB)');
        Helpers::redirect('/trips');
    }

    // POST /trips/[id]/incident
    public function reportIncident(): void
    {
        Auth::requireRole(ROLE_DRIVER, ROLE_EMPLOYEE);
        Helpers::setFlash('success', 'Incident reported successfully. (Capstone 1 — not saved to DB)');
        if (Auth::isDriver()) {
            Helpers::redirect('/trips/1/active');
        } else {
            // Redirect back to the reservation the employee came from
            $resId = $_POST['reservation_url_id'] ?? '1';
            $resId = preg_replace('/[^0-9]/', '', $resId); // sanitize
            Helpers::redirect('/reservations/' . $resId);
        }
    }

    // POST /trips/[id]/notes
    public function notes(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
        Helpers::setFlash('success', 'Note saved. (Capstone 1 — not saved to DB)');
        Helpers::redirect('/trips/1');
    }
}
