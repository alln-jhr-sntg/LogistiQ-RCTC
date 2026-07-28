<?php

/**
 * UserModel
 *
 * Wraps the `users` table.
 *
 * Step 3:  findByEmail, findById, updateLastLogin, updateApiToken
 * Step 6b: findByRole
 * Step 6g: findAll, create, update, updatePassword
 */
class UserModel extends BaseModel
{
    /** Find a user by email address. Used by AuthController. */
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            [':email' => $email]
        );
    }

    /** Find a user by primary key. */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT   u.*, c.company_name, c.company_code,
                      d.department_name
             FROM     users u
             JOIN     companies  c ON c.company_id    = u.company_id
             LEFT JOIN departments d ON d.department_id = u.department_id
             WHERE    u.user_id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /** Stamp last_login_at on successful login. */
    public function updateLastLogin(int $userId): void
    {
        $this->execute(
            'UPDATE users SET last_login_at = NOW() WHERE user_id = :id',
            [':id' => $userId]
        );
    }

    /** Store bearer token for Android API. */
    public function updateApiToken(int $userId, string $token): void
    {
        $this->execute(
            'UPDATE users SET api_token = :token WHERE user_id = :id',
            [':token' => $token, ':id' => $userId]
        );
    }

    /**
     * Return all active users with a given role + company name.
     * Used for dropdowns (driver select in reservation review, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByRole(string $role, bool $activeOnly = true): array
    {
        $sql = 'SELECT   u.*, c.company_name, c.company_code
                FROM     users u
                JOIN     companies c ON c.company_id = u.company_id
                WHERE    u.role = :role';
        if ($activeOnly) {
            $sql .= ' AND u.is_active = 1';
        }
        $sql .= ' ORDER BY u.last_name ASC, u.first_name ASC';
        return $this->fetchAll($sql, [':role' => $role]);
    }

    /**
     * Return all users with optional role and company filters.
     * Joined with company name and department name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(string $roleFilter = '', int $companyFilter = 0): array
    {
        $sql    = 'SELECT   u.*, c.company_name, c.company_code,
                            d.department_name
                   FROM     users u
                   JOIN     companies   c ON c.company_id    = u.company_id
                   LEFT JOIN departments d ON d.department_id = u.department_id
                   WHERE    1=1';
        $params = [];

        if ($roleFilter !== '') {
            $sql   .= ' AND u.role = :role';
            $params[':role'] = $roleFilter;
        }
        if ($companyFilter > 0) {
            $sql   .= ' AND u.company_id = :company_id';
            $params[':company_id'] = $companyFilter;
        }

        $sql .= ' ORDER BY u.last_name ASC, u.first_name ASC';
        return $this->fetchAll($sql, $params);
    }

    /**
     * Insert a new user. Returns the new user_id.
     * Caller should catch PDOException '23000' for duplicate email/employee_id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO users
                (company_id, department_id, role, employee_id,
                 first_name, last_name, email, password_hash,
                 phone_number, profile_photo, is_active)
             VALUES
                (:company_id, :department_id, :role, :employee_id,
                 :first_name, :last_name, :email, :password_hash,
                 :phone_number, :profile_photo, 1)',
            [
                ':company_id'    => $data['company_id'],
                ':department_id' => $data['department_id'] ?? null,
                ':role'          => $data['role'],
                ':employee_id'   => $data['employee_id']   ?? null,
                ':first_name'    => $data['first_name'],
                ':last_name'     => $data['last_name'],
                ':email'         => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':phone_number'  => $data['phone_number']  ?? null,
                ':profile_photo' => $data['profile_photo'] ?? null,
            ]
        );
        return $this->lastInsertId();
    }

    /**
     * Update an existing user's profile fields.
     * Password is updated separately via updatePassword().
     * is_active handles deactivation (never DELETE).
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE users
             SET    company_id    = :company_id,
                    department_id = :department_id,
                    role          = :role,
                    employee_id   = :employee_id,
                    first_name    = :first_name,
                    last_name     = :last_name,
                    email         = :email,
                    phone_number  = :phone_number,
                    profile_photo = :profile_photo,
                    is_active     = :is_active
             WHERE  user_id       = :id',
            [
                ':company_id'    => $data['company_id'],
                ':department_id' => $data['department_id'] ?? null,
                ':role'          => $data['role'],
                ':employee_id'   => $data['employee_id']   ?? null,
                ':first_name'    => $data['first_name'],
                ':last_name'     => $data['last_name'],
                ':email'         => $data['email'],
                ':phone_number'  => $data['phone_number']  ?? null,
                ':profile_photo' => $data['profile_photo'] ?? null,
                ':is_active'     => $data['is_active'],
                ':id'            => $id,
            ]
        );
    }

    /**
     * Update a user's password hash.
     * Called separately from update() so the hash is never mixed
     * into the general data array.
     */
    public function updatePassword(int $id, string $hash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = :hash WHERE user_id = :id',
            [':hash' => $hash, ':id' => $id]
        );
    }
}
