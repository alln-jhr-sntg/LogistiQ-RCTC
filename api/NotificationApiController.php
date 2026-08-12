<?php

require_once __DIR__ . '/BaseApiController.php';

/**
 * NotificationApiController
 *
 * Handles the driver app's notification feed. Both endpoints require
 * ROLE_DRIVER. Rows are filtered server-side to type = 'trip' — drivers
 * must never receive reservation, gatepass, user, company, settings,
 * report, or maintenance notifications (see drivers_app.txt).
 *
 * GET   /api/notifications           — driver's trip notifications
 * PATCH /api/notifications/{id}/read — mark one notification read
 */
class NotificationApiController extends BaseApiController
{
    // GET /api/notifications
    // Newest first, capped to 50, plus an unread count for the bell badge.
    public function index(): void
    {
        $this->requireRole(ROLE_DRIVER);

        $notifModel = new NotificationModel();
        $userId     = $this->authUserId();
        $rows       = $notifModel->getForDriver($userId);

        $notifications = [];
        foreach ($rows as $row) {
            $notifications[] = [
                'notification_id' => (int) $row['notification_id'],
                'title'           => $row['title'],
                'message'         => $row['message'],
                'is_read'         => (bool) $row['is_read'],
                'created_at'      => $row['created_at'],
            ];
        }

        $this->json([
            'notifications' => $notifications,
            'unread_count'  => $notifModel->getUnreadCountForDriver($userId),
        ]);
    }

    // PATCH /api/notifications/{id}/read
    // {id} is the notification_id. markOneRead() scopes by user_id, so a
    // driver can never mark another user's notification as read.
    public function markRead(int $id): void
    {
        $this->requireRole(ROLE_DRIVER);

        (new NotificationModel())->markOneRead($id, $this->authUserId());

        $this->json(['status' => 'ok']);
    }
}
