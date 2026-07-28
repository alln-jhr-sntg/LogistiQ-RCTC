<?php

/**
 * AuditLogModel
 *
 * Wraps the `audit_logs` table. Append-only — no update or delete.
 * Used throughout the system to record who did what, to which record,
 * and what changed.
 *
 * Failures are caught and written to the PHP error log rather than
 * thrown — an audit log failure must never crash the application or
 * interrupt a user-facing action. Check the XAMPP PHP error log
 * (C:\xampp\php\logs\php_error_log) if entries stop appearing.
 */
class AuditLogModel extends BaseModel
{
    /**
     * Insert one audit log row.
     *
     * @param int|null    $userId    User who triggered the action.
     *                               NULL for system-triggered or failed login events.
     * @param string      $action    Constant string e.g. 'LOGIN_SUCCESS', 'VEHICLE_CREATED'.
     * @param string|null $table     Table affected e.g. 'vehicles'.
     * @param int|null    $recordId  Primary key of the affected row.
     * @param array|null  $old       Previous values as associative array. NULL for creates.
     * @param array|null  $new       New/context values as associative array. NULL for deletes.
     * @param string|null $ip        IP address. Defaults to REMOTE_ADDR.
     * @param string|null $ua        User agent. Defaults to HTTP_USER_AGENT.
     */
    public function log(
        ?int    $userId,
        string  $action,
        ?string $table    = null,
        ?int    $recordId = null,
        ?array  $old      = null,
        ?array  $new      = null,
        ?string $ip       = null,
        ?string $ua       = null
    ): void {
        try {
            $this->execute(
                'INSERT INTO audit_logs
                    (user_id, action, table_name, record_id,
                     old_values, new_values, ip_address, user_agent)
                 VALUES
                    (:user_id, :action, :table_name, :record_id,
                     :old_values, :new_values, :ip_address, :user_agent)',
                [
                    ':user_id'    => $userId,
                    ':action'     => $action,
                    ':table_name' => $table,
                    ':record_id'  => $recordId,
                    ':old_values' => $old !== null
                        ? json_encode($old, JSON_UNESCAPED_UNICODE)
                        : null,
                    ':new_values' => $new !== null
                        ? json_encode($new, JSON_UNESCAPED_UNICODE)
                        : null,
                    ':ip_address' => $ip ?? ($_SERVER['REMOTE_ADDR']     ?? null),
                    ':user_agent' => $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                ]
            );
        } catch (Throwable $e) {
            // Audit log failures must not crash the application.
            // Check C:\xampp\php\logs\php_error_log for details.
            error_log(
                '[LVMS AuditLogModel] INSERT failed — '
                . $e->getMessage()
                . ' | action=' . $action
                . ' | user_id=' . ($userId ?? 'null')
            );
        }
    }
}
