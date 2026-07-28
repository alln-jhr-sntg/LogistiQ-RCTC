<?php

/**
 * VehicleMaintenanceModel
 *
 * Wraps the `vehicle_maintenance` table.
 *
 * Used in:
 *   Step 6d  — VehicleController maintenance log + display
 *   Step 12  — MaintenanceService::checkAfterTrip()
 *   Step 14  — ReportController maintenance due report
 */
class VehicleMaintenanceModel extends BaseModel
{
    /**
     * Return all maintenance records for a vehicle, most recent first.
     * Used to display the maintenance history table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByVehicle(int $vehicleId): array
    {
        return $this->fetchAll(
            'SELECT   vm.*, u.first_name, u.last_name
             FROM     vehicle_maintenance vm
             JOIN     users u ON u.user_id = vm.recorded_by
             WHERE    vm.vehicle_id = :vehicle_id
             ORDER BY vm.service_date DESC, vm.created_at DESC',
            [':vehicle_id' => $vehicleId]
        );
    }

    /**
     * Return the single most recent maintenance record for a vehicle.
     * Used by MaintenanceService to determine next_service_km.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestByVehicle(int $vehicleId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM vehicle_maintenance
             WHERE  vehicle_id  = :vehicle_id
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
