<?php

/**
 * NotificationModel
 *
 * Wraps the `notifications` table.
 *
 * Step 9:  createForUsers() — write notifications when reservation created
 * Step 12: createForUsers() reused for maintenance alerts
 * Step 13: getByUser(), getUnreadCount(), markAllRead(), markOneRead()
 *          — reading/marking notifications wired in Step 13
 */
class NotificationModel extends BaseModel
{
    /**
     * Insert a notification row for each user in $userIds.
     * Silently skips empty arrays — callers do not need to guard against this.
     *
     * @param int[]               $userIds       Recipients.
     * @param array<string,mixed> $data          Must include: title, message, type.
     *                                           Optional: reference_id, reference_type.
     */
    public function createForUsers(array $userIds, array $data): void
    {
        if (empty($userIds)) {
            return;
        }

        foreach ($userIds as $userId) {
            $this->execute(
                'INSERT INTO notifications
                    (user_id, title, message, type, reference_id, reference_type)
                 VALUES
                    (:user_id, :title, :message, :type, :reference_id, :reference_type)',
                [
                    ':user_id'        => (int) $userId,
                    ':title'          => $data['title'],
                    ':message'        => $data['message'],
                    ':type'           => $data['type']           ?? 'system',
                    ':reference_id'   => $data['reference_id']   ?? null,
                    ':reference_type' => $data['reference_type'] ?? null,
                ]
            );
        }
    }

    /**
     * Return true if a maintenance notification for this vehicle was already
     * sent within the last 24 hours. Used by MaintenanceService to prevent
     * notification spam from repeated button clicks or multiple trips per day.
     *
     * Matches on reference_id = vehicle_id AND reference_type = 'maintenance'
     * AND type = 'maintenance' — the same triple written by MaintenanceService.
     */
    public function hasRecentMaintenance(int $vehicleId): bool
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM   notifications
             WHERE  reference_id   = :vehicle_id
               AND  reference_type = \'maintenance\'
               AND  type           = \'maintenance\'
               AND  created_at     >= NOW() - INTERVAL 24 HOUR',
            [':vehicle_id' => $vehicleId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Return a single notification by primary key.
     * Used by markOneRead() to fetch the reference URL for redirect.
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM notifications WHERE notification_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Return notifications for a user, newest first.
     * Used by NotificationController::index().
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications
             WHERE  user_id    = :user_id
             ORDER  BY created_at DESC
             LIMIT  :limit',
            [':user_id' => $userId, ':limit' => $limit]
        );
    }

    /**
     * Mark all unread notifications as read for a user.
     * Called when the user clicks "Mark All Read" on the notifications page.
     */
    public function markAllRead(int $userId): void
    {
        $this->execute(
            'UPDATE notifications
             SET    is_read = 1
             WHERE  user_id = :user_id
               AND  is_read = 0',
            [':user_id' => $userId]
        );
    }

    /**
     * Mark a single notification as read.
     * The AND user_id guard prevents users from marking others' notifications.
     */
    public function markOneRead(int $notificationId, int $userId): void
    {
        $this->execute(
            'UPDATE notifications
             SET    is_read = 1
             WHERE  notification_id = :id
               AND  user_id         = :user_id',
            [':id' => $notificationId, ':user_id' => $userId]
        );
    }

    /**
     * Count unread notifications for a user.
     * Used by the topbar badge in Step 13.
     */
    public function getUnreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM notifications
             WHERE user_id = :user_id AND is_read = 0',
            [':user_id' => $userId]
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
