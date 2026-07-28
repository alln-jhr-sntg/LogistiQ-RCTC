<?php

/**
 * VehicleCategoryModel
 *
 * Wraps the `vehicle_categories` table.
 *
 * Used in:
 *   Step 6c  — VehicleController categories CRUD
 *   Step 6d  — VehicleController create/edit (category dropdown)
 *   Step 6f  — TripPurposeModel preferred_category_ids multi-select
 *   Step 10  — VehicleRecommendationService purpose-fit scoring
 */
class VehicleCategoryModel extends BaseModel
{
    /**
     * Return all categories with a count of vehicles in each.
     * Used by the categories list page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->fetchAll(
            'SELECT   vc.*,
                      COUNT(v.vehicle_id) AS vehicle_count
             FROM     vehicle_categories vc
             LEFT JOIN vehicles v ON v.category_id = vc.category_id
             GROUP BY vc.category_id
             ORDER BY vc.category_name ASC'
        );
    }

    /**
     * Return a single category by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM vehicle_categories WHERE category_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Insert a new vehicle category.
     * Returns the new category_id.
     *
     * Caller should catch PDOException with getCode() === '23000' to
     * handle UNIQUE constraint violations on category_name gracefully.
     */
    public function create(
        string $name,
        int    $maxPassengers,
        float  $maxCargoKg
    ): int {
        $this->execute(
            'INSERT INTO vehicle_categories (category_name, max_passengers, max_cargo_kg)
             VALUES (:name, :max_passengers, :max_cargo_kg)',
            [
                ':name'           => $name,
                ':max_passengers' => $maxPassengers,
                ':max_cargo_kg'   => $maxCargoKg,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing vehicle category.
     *
     * Caller should catch PDOException with getCode() === '23000' to
     * handle UNIQUE constraint violations on category_name gracefully.
     */
    public function update(
        int    $id,
        string $name,
        int    $maxPassengers,
        float  $maxCargoKg
    ): void {
        $this->execute(
            'UPDATE vehicle_categories
             SET    category_name  = :name,
                    max_passengers = :max_passengers,
                    max_cargo_kg   = :max_cargo_kg
             WHERE  category_id    = :id',
            [
                ':name'           => $name,
                ':max_passengers' => $maxPassengers,
                ':max_cargo_kg'   => $maxCargoKg,
                ':id'             => $id,
            ]
        );
    }
}
