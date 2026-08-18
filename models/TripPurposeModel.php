<?php

/**
 * TripPurposeModel
 *
 * Wraps the `trip_purposes` table.
 *
 * Used in:
 *   ReservationController purposes CRUD
 *   TripLimitService max_per_project check
 *   ReservationController create form dropdown
 *   VehicleRecommendationService purpose-fit scoring
 *             (scoring now reads preferred_purpose_ids on vehicles,
 *              not preferred_category_ids on trip_purposes)
 */
class TripPurposeModel extends BaseModel
{
    /**
     * Return all purposes ordered by name.
     * Used by the purposes management page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->fetchAll(
            'SELECT * FROM trip_purposes ORDER BY purpose_name ASC'
        );
    }

    /**
     * Return only active purposes.
     * Used in reservation create form dropdown and vehicle create/edit
     * form preferred-purposes multi-select.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActive(): array
    {
        return $this->fetchAll(
            'SELECT * FROM trip_purposes WHERE is_active = 1 ORDER BY purpose_name ASC'
        );
    }

    /**
     * Return a single purpose by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM trip_purposes WHERE purpose_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Insert a new trip purpose. Returns the new purpose_id.
     * Caller should catch PDOException '23000' for duplicate purpose_name.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO trip_purposes
                (purpose_name, description, requires_project,
                 max_per_project, is_active)
             VALUES
                (:purpose_name, :description, :requires_project,
                 :max_per_project, :is_active)',
            [
                ':purpose_name'     => $data['purpose_name'],
                ':description'      => $data['description']      ?? null,
                ':requires_project' => $data['requires_project'] ?? 0,
                ':max_per_project'  => $data['max_per_project']  ?? null,
                ':is_active'        => $data['is_active']        ?? 1,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing trip purpose.
     * Caller should catch PDOException '23000' for duplicate purpose_name.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE trip_purposes
             SET    purpose_name     = :purpose_name,
                    description      = :description,
                    requires_project = :requires_project,
                    max_per_project  = :max_per_project,
                    is_active        = :is_active
             WHERE  purpose_id       = :id',
            [
                ':purpose_name'     => $data['purpose_name'],
                ':description'      => $data['description']      ?? null,
                ':requires_project' => $data['requires_project'] ?? 0,
                ':max_per_project'  => $data['max_per_project']  ?? null,
                ':is_active'        => $data['is_active']        ?? 1,
                ':id'               => $id,
            ]
        );
    }
}
