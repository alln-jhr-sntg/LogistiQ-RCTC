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
    public function findAll(string $statusFilter = '', int $categoryId = 0): array
    {
        $sql    = 'SELECT   v.*, vc.category_name
                   FROM     vehicles v
                   JOIN     vehicle_categories vc ON vc.category_id = v.category_id';
        $params = [];
        $where  = [];

        if ($statusFilter !== '') {
            $where[] = 'v.status = :status';
            $params[':status'] = $statusFilter;
        }
        if ($categoryId > 0) {
            $where[] = 'v.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
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
     * Return vehicles eligible for the recommendation engine.
     * Includes status = 'available' AND 'reserved' — 'reserved' vehicles
     * may still be schedulable for different date ranges.
     * The Phase 1 date-overlap check in VehicleRecommendationService
     * determines whether a specific vehicle truly conflicts with the
     * requested window.
     * Excludes: 'on_trip', 'under_maintenance', 'retired'.
     *
     * preferred_purpose_ids is included via v.* and read per-vehicle
     * by VehicleRecommendationService for purpose_fit scoring.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForRecommendation(): array
    {
        return $this->fetchAll(
            'SELECT   v.*, vc.category_name
             FROM     vehicles v
             JOIN     vehicle_categories vc ON vc.category_id = v.category_id
             WHERE    v.status IN (\'available\', \'reserved\')
             ORDER BY v.plate_number ASC'
        );
    }

    /**
     * Return only vehicles with status = 'available'.
     * Used by the review form vehicle dropdown and trip assignment.
     * A simpler check than findForRecommendation — the admin is expected
     * to know if a specific vehicle is suitable for the requested dates.
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
                 current_odometer_km, gross_weight_kg, status, remarks,
                 preferred_purpose_ids)
             VALUES
                (:category_id, :plate_number, :brand, :model, :year_model,
                 :color, :fuel_type, :passenger_capacity, :cargo_capacity_kg,
                 :current_odometer_km, :gross_weight_kg, :status, :remarks,
                 :preferred_purpose_ids)',
            [
                ':category_id'           => $data['category_id'],
                ':plate_number'          => $data['plate_number'],
                ':brand'                 => $data['brand'],
                ':model'                 => $data['model'],
                ':year_model'            => $data['year_model'],
                ':color'                 => $data['color']                 ?? null,
                ':fuel_type'             => $data['fuel_type'],
                ':passenger_capacity'    => $data['passenger_capacity'],
                ':cargo_capacity_kg'     => $data['cargo_capacity_kg']     ?? 0,
                ':current_odometer_km'   => $data['current_odometer_km']   ?? 0,
                ':gross_weight_kg'       => $data['gross_weight_kg'],
                ':status'                => $data['status']                ?? 'available',
                ':remarks'               => $data['remarks']               ?? null,
                ':preferred_purpose_ids' => $data['preferred_purpose_ids'] ?? null,
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
             SET    category_id           = :category_id,
                    plate_number          = :plate_number,
                    brand                 = :brand,
                    model                 = :model,
                    year_model            = :year_model,
                    color                 = :color,
                    fuel_type             = :fuel_type,
                    gross_weight_kg       = :gross_weight_kg,
                    current_odometer_km   = :current_odometer_km,
                    status                = :status,
                    remarks               = :remarks,
                    preferred_purpose_ids = :preferred_purpose_ids
             WHERE  vehicle_id            = :id',
            [
                ':category_id'           => $data['category_id'],
                ':plate_number'          => $data['plate_number'],
                ':brand'                 => $data['brand'],
                ':model'                 => $data['model'],
                ':year_model'            => $data['year_model'],
                ':color'                 => $data['color']                 ?? null,
                ':fuel_type'             => $data['fuel_type'],
                ':gross_weight_kg'       => $data['gross_weight_kg'],
                ':current_odometer_km'   => $data['current_odometer_km']  ?? 0,
                ':status'                => $data['status'],
                ':remarks'               => $data['remarks']              ?? null,
                ':preferred_purpose_ids' => $data['preferred_purpose_ids'] ?? null,
                ':id'                    => $id,
            ]
        );
    }

    /**
     * Return all non-retired vehicles with their latest maintenance data,
     * ordered by urgency (overdue first, then due soon, then ok, then no baseline).
     * Used by ReportController::maintenanceDue().
     *
     * The correlated subquery finds the latest vehicle_maintenance row that
     * has a next_service_km value set. Rows without next_service_km are skipped
     * so they don't replace a valid baseline row.
     *
     * $limit/$offset (cast int, interpolated not bound — see findForReport()
     * in TripModel for why) default to null = no LIMIT, so the existing
     * findMaintenanceDue() dashboard caller keeps getting the full list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForMaintenanceReport(?int $limit = null, ?int $offset = null): array
    {
        $sql = 'SELECT v.*,
                    vc.category_name,
                    vm.service_date        AS last_service_date,
                    vm.next_service_km,
                    vm.next_service_date,
                    vm.odometer_at_service,
                    (v.current_odometer_km - IFNULL(vm.next_service_km, 0)) AS km_over,
                    (IFNULL(vm.next_service_km, 0) - v.current_odometer_km) AS km_remaining
             FROM   vehicles v
             LEFT JOIN vehicle_categories  vc ON vc.category_id   = v.category_id
             LEFT JOIN vehicle_maintenance vm ON vm.maintenance_id = (
                 SELECT vm2.maintenance_id
                 FROM   vehicle_maintenance vm2
                 WHERE  vm2.vehicle_id      = v.vehicle_id
                   AND  vm2.next_service_km IS NOT NULL
                 ORDER  BY vm2.service_date DESC, vm2.maintenance_id DESC
                 LIMIT  1
             )
             WHERE  v.status != \'retired\'
             ORDER  BY
                 CASE
                     WHEN vm.next_service_km IS NULL                        THEN 3
                     WHEN v.current_odometer_km >= vm.next_service_km       THEN 0
                     WHEN v.current_odometer_km >= vm.next_service_km - 500 THEN 1
                     ELSE                                                        2
                 END,
                 (v.current_odometer_km - IFNULL(vm.next_service_km, 0)) DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql);
    }

    /**
     * Return all non-retired vehicles with completed trip counts and total
     * distance driven within an optional date range.
     * Uses LEFT JOIN so vehicles with zero trips in the range still appear.
     * Used by ReportController::vehicleUtilization().
     *
     * $limit/$offset (cast int, interpolated not bound — see findForReport()
     * in TripModel for why) default to null = no LIMIT (full result set —
     * used by CSV export and to compute the report's stat cards, which must
     * reflect the whole filtered fleet, not just the current page).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForUtilizationReport(string $dateFrom = '', string $dateTo = '', ?int $limit = null, ?int $offset = null): array
    {
        // Build JOIN ON condition dynamically — positional params in JOIN ON
        // are processed left to right by PDO just like WHERE params.
        $joinCondition = "t.vehicle_id = v.vehicle_id AND t.trip_status = 'completed'";
        $params        = [];

        if ($dateFrom !== '') {
            $joinCondition .= ' AND DATE(t.actual_departure) >= ?';
            $params[]       = $dateFrom;
        }
        if ($dateTo !== '') {
            $joinCondition .= ' AND DATE(t.actual_departure) <= ?';
            $params[]       = $dateTo;
        }

        $sql = "SELECT v.vehicle_id, v.plate_number, v.brand, v.model, v.year_model,
                    v.status, v.current_odometer_km,
                    vc.category_name,
                    COUNT(t.trip_id)                                       AS trip_count,
                    COALESCE(SUM(t.odometer_end_km - t.odometer_start_km), 0) AS total_km
             FROM   vehicles v
             LEFT JOIN vehicle_categories vc ON vc.category_id = v.category_id
             LEFT JOIN trips t ON $joinCondition
             WHERE  v.status != 'retired'
             GROUP  BY v.vehicle_id, v.plate_number, v.brand, v.model,
                       v.year_model, v.status, v.current_odometer_km, vc.category_name
             ORDER  BY total_km DESC, trip_count DESC";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Return vehicle counts grouped by status, e.g. ['available' => 12, 'on_trip' => 3].
     * Statuses with zero vehicles are simply absent from the map — callers
     * should read with ($counts['x'] ?? 0). A single GROUP BY query, used by
     * the super_admin and fleet_admin dashboard fleet summary sections.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS cnt FROM vehicles GROUP BY status');
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        return $counts;
    }

    /**
     * Return non-retired vehicles that are overdue or due soon for service,
     * overdue first. Reuses findForMaintenanceReport()'s data and urgency
     * ordering, filtered down to just the "needs attention" subset and
     * tagged with maintenance_alert ('overdue' | 'due_soon') so callers
     * don't have to re-derive the threshold logic.
     * Used by the fleet_admin dashboard's Maintenance Due card + table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findMaintenanceDue(): array
    {
        $due = [];
        foreach ($this->findForMaintenanceReport() as $vehicle) {
            $current = (float) $vehicle['current_odometer_km'];
            $next    = $vehicle['next_service_km'] !== null ? (float) $vehicle['next_service_km'] : null;

            if ($next === null) {
                continue;
            }
            if ($current >= $next) {
                $vehicle['maintenance_alert'] = 'overdue';
                $due[] = $vehicle;
            } elseif ($current >= $next - 500) {
                $vehicle['maintenance_alert'] = 'due_soon';
                $due[] = $vehicle;
            }
        }
        return $due;
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

    /**
     * Return true if this vehicle has an approved/in_progress reservation
     * that overlaps the requested window. Used by VehicleRecommendationService
     * Phase 1 availability check.
     *
     * Overlap condition (correct interval logic per the plan):
     *   existing.departure < requested.return
     *   AND existing.return > requested.departure
     *
     * A vehicle whose reservation ends exactly at the requested departure
     * time is NOT considered conflicting (strict inequalities).
     */
    public function hasConflictingReservation(
        int    $vehicleId,
        string $requestedDeparture,
        string $requestedReturn
    ): bool {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM   reservations
             WHERE  assigned_vehicle_id = :vehicle_id
               AND  status              IN (\'approved\', \'in_progress\')
               AND  departure_datetime  < :requested_return
               AND  return_datetime     > :requested_departure',
            [
                ':vehicle_id'          => $vehicleId,
                ':requested_return'    => $requestedReturn,
                ':requested_departure' => $requestedDeparture,
            ]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }
}
