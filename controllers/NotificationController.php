<?php

class NotificationController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/notifications/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /notifications
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $notifModel = new NotificationModel();
        $userId     = (int) Auth::id();

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $total      = $notifModel->countByUser($userId);
        $pagination = Helpers::paginate($total, $page, 10, 'url=notifications');

        $notifications = $notifModel->getByUser($userId, $pagination['limit'], $pagination['offset']);

        $this->render('index', [
            'page_title'    => 'Notifications',
            'notifications' => $notifications,
            'pagination'    => $pagination['html'],
        ]);
    }

    // POST /notifications/read-all
    public function markAllRead(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $notifModel = new NotificationModel();
        $notifModel->markAllRead((int) Auth::id());

        Helpers::redirect('/notifications');
    }

    // GET /notifications/{id}/read
    // Marks the notification read and redirects to its referenced resource.
    // Used by both the "View →" link and the card click in the notifications list.
    public function markOneRead(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $notifModel   = new NotificationModel();
        $notification = $notifModel->getById($id);

        // Only mark read if the notification belongs to the logged-in user
        if ($notification && (int) $notification['user_id'] === (int) Auth::id()) {
            $notifModel->markOneRead($id, (int) Auth::id());

            // Redirect to the referenced resource if one exists
            $refUrls = [
                'reservation' => '/reservations/' . $notification['reference_id'],
                'trip'        => '/trips/'         . $notification['reference_id'],
                'maintenance' => '/vehicles/'      . $notification['reference_id'] . '/maintenance',
                'gatepass'    => '/gatepasses/'    . $notification['reference_id'] . '/review',
            ];

            if ($notification['reference_id']
                && isset($refUrls[$notification['reference_type']])) {
                Helpers::redirect($refUrls[$notification['reference_type']]);
            }
        }

        Helpers::redirect('/notifications');
    }

    // GET /notifications/count — JSON endpoint for badge polling
    // Does not render the layout; outputs JSON and exits.
    public function count(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN, ROLE_EMPLOYEE);

        $notifModel = new NotificationModel();
        $count      = $notifModel->getUnreadCount((int) Auth::id());

        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }
}
