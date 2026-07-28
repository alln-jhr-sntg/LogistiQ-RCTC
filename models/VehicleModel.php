<?php

/**
 * VehicleModel
 *
 * Wraps the `vehicles` table. Vehicles are shared across all companies
 * (no company_id column). Status transitions ('reserved', 'on_trip')
 * are managed exclusively by TripController — never set them here.
 *
 * Used in:
 *   Step 6d  — VehicleController full CRUD
 *   Step 10  — VehicleRecommendationService candidate queries
 *   Step 11  — TripController status transitions
 *   Step 14  — ReportController vehicle utilization
 */
class VehicleModel extends BaseModel
{
    /**
     * Return all vehicles joined with their category name.
     * Optional status filter for the fleet list page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(string $statusFilter = ''): array
    {
        $sql    = 'SELECT   v.*, vc.category_name
                   FROM     vehicles v
                   JOIN     vehicle_categories vc ON vc.category_id = v.category_id';
        $params = [];

        if ($statusFilter !== '') {
            $sql   .= ' WHERE v.status = :status';
            $params = [':status' => $statusFilter];
        }

        $sql .= ' ORDER BY v.plate_number ASC';

        return $this->fetchAll($sql, $params);
    }

    /**
     * Return a single vehicle by primary key, joined with category name.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT   v.*, vc.category_name
             FROM     vehicles v
             JOIN     vehicle_categories vc ON vc.category_id = v.category_id
             WHERE    v.vehicle_id = :id
             LIMIT    1',
            [':id' => $id]
        );
    }

    /**
     * Return all vehicles currently available for assignment.
     * Used by VehicleRecommendationService in Step 10.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAvailable(): array
    {
        return $this->fetchAll(
            'SELECT   v.*, vc.category_name
             FROM     vehicles v
             JOIN     vehicle_categories vc ON vc.category_id = v.category_id
             WHERE    v.status = \'available\'
             ORDER BY v.plate_number ASC'
        );
    }

    /**
     * Insert a new vehicle. Returns the new vehicle_id.
     * Caller should catch PDOException with getCode() === '23000'
     * to handle duplicate plate_number gracefully.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO vehicles
                (category_id, plate_number, brand, model, year_model,
                 color, fuel_type, passenger_capacity, cargo_capacity_kg,
                 current_odometer_km, gross_weight_kg, status, remarks)
             VALUES
                (:category_id, :plate_number, :brand, :model, :year_model,
                 :color, :fuel_type, :passenger_capacity, :cargo_capacity_kg,
                 :current_odometer_km, :gross_weight_kg, :status, :remarks)',
            [
                ':category_id'         => $data['category_id'],
                ':plate_number'        => $data['plate_number'],
                ':brand'               => $data['brand'],
                ':model'               => $data['model'],
                ':year_model'          => $data['year_model'],
                ':color'               => $data['color']               ?? null,
                ':fuel_type'           => $data['fuel_type'],
                ':passenger_capacity'  => $data['passenger_capacity'],
                ':cargo_capacity_kg'   => $data['cargo_capacity_kg']   ?? 0,
                ':current_odometer_km' => $data['current_odometer_km'] ?? 0,
                ':gross_weight_kg'     => $data['gross_weight_kg'],
                ':status'              => $data['status']              ?? 'available',
                ':remarks'             => $data['remarks']             ?? null,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing vehicle's editable fields.
     * Does NOT include status — status transitions are managed by
     * TripController. Odometer is included for manual corrections.
     * Caller should catch PDOException '23000' for duplicate plates.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE vehicles
             SET    category_id         = :category_id,
                    plate_number        = :plate_number,
                    brand               = :brand,
                    model               = :model,
                    year_model          = :year_model,
                    color               = :color,
                    fuel_type           = :fuel_type,
                    gross_weight_kg     = :gross_weight_kg,
                    current_odometer_km = :current_odometer_km,
                    status              = :status,
                    remarks             = :remarks
             WHERE  vehicle_id          = :id',
            [
                ':category_id'         => $data['category_id'],
                ':plate_number'        => $data['plate_number'],
                ':brand'               => $data['brand'],
                ':model'               => $data['model'],
                ':year_model'          => $data['year_model'],
                ':color'               => $data['color']               ?? null,
                ':fuel_type'           => $data['fuel_type'],
                ':gross_weight_kg'     => $data['gross_weight_kg'],
                ':current_odometer_km' => $data['current_odometer_km'] ?? 0,
                ':status'              => $data['status'],
                ':remarks'             => $data['remarks']             ?? null,
                ':id'                  => $id,
            ]
        );
    }

    /**
     * Update only the current odometer. Called by TripController::complete()
     * after a trip ends to stamp the actual return odometer.
     */
    public function updateOdometer(int $id, float $odometer): void
    {
        $this->execute(
            'UPDATE vehicles SET current_odometer_km = :odo WHERE vehicle_id = :id',
            [':odo' => $odometer, ':id' => $id]
        );
    }

    /**
     * Update only the status. Called by TripController for status
     * transitions: reserved → on_trip → available, etc.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE vehicles SET status = :status WHERE vehicle_id = :id',
            [':status' => $status, ':id' => $id]
        );
    }
}
