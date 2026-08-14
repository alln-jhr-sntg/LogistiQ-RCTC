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
    /**
     * Find an active user by their API bearer token.
     * Used by api/index.php middleware on every non-login request.
     * Returns null if the token is invalid or the user is inactive.
     *
     * @return array<string, mixed>|null
     */
    public function findByApiToken(string $token): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users
             WHERE  api_token = :token
               AND  is_active = 1
             LIMIT  1',
            [':token' => $token]
        );
    }

    /**
     * Store or replace the bearer token for a user.
     * Called on login — replaces any previous token.
     * Set to NULL on logout (not currently implemented).
     */
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

    /**
     * Return every active user with their company code, ordered by
     * user_id. Used to populate the login page's demo-accounts panel —
     * the caller picks the earliest match per role/company slot, so the
     * panel always reflects real, currently-active credentials.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActiveWithCompany(): array
    {
        return $this->fetchAll(
            'SELECT   u.email, u.role, c.company_code
             FROM     users u
             JOIN     companies c ON c.company_id = u.company_id
             WHERE    u.is_active = 1
             ORDER BY u.user_id ASC'
        );
    }

    /** Count all active users. Used by the super_admin dashboard stat card. */
    public function countAll(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM users WHERE is_active = 1');
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Count active users belonging to a single company.
     * Used by the admin dashboard's "Company Employees" stat card.
     */
    public function countByCompany(int $companyId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE company_id = :company_id AND is_active = 1',
            [':company_id' => $companyId]
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
