<?php

/**
 * VehicleMaintenanceModel
 *
 * Wraps the `vehicle_maintenance` table.
 *
 * Used in:
 *   VehicleController maintenance log + display
 *   MaintenanceService::checkAfterTrip()
 *   ReportController maintenance history report
 */
class VehicleMaintenanceModel extends BaseModel
{
    /**
     * Shared WHERE-builder for the per-vehicle history and fleet-wide report
     * queries below. $filters may contain vehicle_id, date_from, date_to,
     * and maintenance_type — every key is optional.
     *
     * @param  array<string, mixed> $filters
     * @return array{0: string[], 1: array<int, mixed>}
     */
    private function conditions(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $where[]  = 'vm.vehicle_id = ?';
            $params[] = (int) $filters['vehicle_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'vm.service_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'vm.service_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['maintenance_type'])) {
            $where[]  = 'vm.maintenance_type = ?';
            $params[] = $filters['maintenance_type'];
        }

        return [$where, $params];
    }

    /**
     * Return maintenance records for a vehicle, most recent first, with
     * optional date-range / type filters. Used to display the maintenance
     * history table on the vehicle maintenance page.
     *
     * @param  array<string, mixed> $filters date_from, date_to, maintenance_type
     * @return array<int, array<string, mixed>>
     */
    public function findByVehicle(int $vehicleId, array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        [$where, $params] = $this->conditions(array_merge($filters, ['vehicle_id' => $vehicleId]));

        $sql = 'SELECT   vm.*, u.first_name, u.last_name
                FROM     vehicle_maintenance vm
                JOIN     users u ON u.user_id = vm.recorded_by
                WHERE    ' . implode(' AND ', $where) . '
                ORDER BY vm.service_date DESC, vm.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count maintenance records for a vehicle matching the same filters as
     * findByVehicle() — used to compute pagination without pulling every row.
     *
     * @param array<string, mixed> $filters
     */
    public function countByVehicle(int $vehicleId, array $filters = []): int
    {
        [$where, $params] = $this->conditions(array_merge($filters, ['vehicle_id' => $vehicleId]));

        $sql = 'SELECT COUNT(*) AS cnt FROM vehicle_maintenance vm WHERE ' . implode(' AND ', $where);
        $row = $this->fetchOne($sql, $params);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return maintenance records across the whole fleet, most recent first,
     * with optional date-range / vehicle / type filters. Used by the Reports
     * > Maintenance History tab.
     *
     * @param  array<string, mixed> $filters date_from, date_to, vehicle_id, maintenance_type
     * @return array<int, array<string, mixed>>
     */
    public function findForReport(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        [$where, $params] = $this->conditions($filters);

        $sql = 'SELECT vm.*, u.first_name, u.last_name,
                       v.plate_number, v.brand, v.model
                FROM   vehicle_maintenance vm
                JOIN   users    u ON u.user_id    = vm.recorded_by
                JOIN   vehicles v ON v.vehicle_id = vm.vehicle_id';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY vm.service_date DESC, vm.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count fleet-wide maintenance records matching the same filters as
     * findForReport() — used to compute pagination without pulling every row.
     *
     * @param array<string, mixed> $filters
     */
    public function countForReport(array $filters = []): int
    {
        [$where, $params] = $this->conditions($filters);

        $sql = 'SELECT COUNT(*) AS cnt FROM vehicle_maintenance vm';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $row = $this->fetchOne($sql, $params);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Return the vehicle's most recent maintenance record that established a
     * baseline (has next_service_km set). Records without next_service_km —
     * e.g. an inspection with no follow-up interval — are skipped so a newer
     * non-baseline entry can't mask an older established baseline; this
     * mirrors the correlated subquery in VehicleModel::findForMaintenanceReport().
     * Used by MaintenanceService, VehicleController::maintenance(), and
     * VehicleRecommendationService to determine next_service_km.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestByVehicle(int $vehicleId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM vehicle_maintenance
             WHERE  vehicle_id      = :vehicle_id
               AND  next_service_km IS NOT NULL
             ORDER  BY service_date DESC, created_at DESC
             LIMIT  1',
            [':vehicle_id' => $vehicleId]
        );
    }

    /**
     * Insert a new maintenance record.
     * next_service_km is auto-calculated by the caller if not provided:
     *   next_service_km = odometer_at_service + 5000
     *
     * @param array<string, mixed> $data
     */
    public function create(int $vehicleId, int $recordedBy, array $data): void
    {
        $this->execute(
            'INSERT INTO vehicle_maintenance
                (vehicle_id, recorded_by, maintenance_type, description,
                 odometer_at_service, service_date, next_service_date,
                 next_service_km, cost, performed_by)
             VALUES
                (:vehicle_id, :recorded_by, :maintenance_type, :description,
                 :odometer_at_service, :service_date, :next_service_date,
                 :next_service_km, :cost, :performed_by)',
            [
                ':vehicle_id'          => $vehicleId,
                ':recorded_by'         => $recordedBy,
                ':maintenance_type'    => $data['maintenance_type'],
                ':description'         => $data['description']         ?? null,
                ':odometer_at_service' => $data['odometer_at_service'] ?? null,
                ':service_date'        => $data['service_date'],
                ':next_service_date'   => $data['next_service_date']   ?? null,
                ':next_service_km'     => $data['next_service_km']     ?? null,
                ':cost'                => $data['cost']                ?? null,
                ':performed_by'        => $data['performed_by']        ?? null,
            ]
        );
    }
}
