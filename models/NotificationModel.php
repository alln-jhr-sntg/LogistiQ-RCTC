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
