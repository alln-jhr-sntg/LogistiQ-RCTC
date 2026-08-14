<?php

class UserController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/users/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /users
    public function index(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $roleFilter    = $_GET['role']       ?? '';
        $companyFilter = (int) ($_GET['company_id'] ?? 0);
        $activeFilter  = $_GET['is_active']  ?? '';
        if (!in_array($activeFilter, ['0', '1'], true)) {
            $activeFilter = '';
        }

        // Admin sees only their own company's staff — a directory of another
        // company's users is not fleet transparency, unlike reservations/trips.
        if (Auth::role() === ROLE_ADMIN) {
            $companyFilter = (int) Auth::companyId();
        }

        $userModel = new UserModel();
        $users     = $userModel->findAll($roleFilter, $companyFilter, $activeFilter);

        $companyModel = new CompanyModel();
        $companies    = $companyModel->findAll();

        $this->render('index', [
            'page_title'    => 'Users',
            'users'         => $users,
            'companies'     => $companies,
            'roleFilter'    => $roleFilter,
            'companyFilter' => $companyFilter,
            'activeFilter'  => $activeFilter,
        ]);
    }

    // GET /users/create
    public function create(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $companyModel = new CompanyModel();
        $deptModel    = new DepartmentModel();

        $this->render('create', [
            'page_title'  => 'Add User',
            'companies'   => $companyModel->findAll(),
            'departments' => $deptModel->findAllWithCompany(),
        ]);
    }

    // POST /users/create
    public function store(): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $firstName    = trim($_POST['first_name']   ?? '');
        $lastName     = trim($_POST['last_name']    ?? '');
        $email        = trim($_POST['email']        ?? '');
        $password     =      $_POST['password']     ?? '';
        $role         = trim($_POST['role']         ?? '');
        $companyId    = (int) ($_POST['company_id'] ?? 0);
        $departmentId = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
        $employeeId   = trim($_POST['employee_id']  ?? '') ?: null;
        $phone        = trim($_POST['phone_number'] ?? '') ?: null;

        if ($firstName === '' || $lastName === '' || $email === '' ||
            $password === '' || $role === '' || $companyId === 0) {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/users/create');
        }

        $allowedRoles = ROLE_ASSIGNABLE[Auth::role()] ?? [];
        if (!in_array($role, $allowedRoles, true)) {
            Helpers::setFlash('error', 'You are not permitted to assign that role.');
            Helpers::redirect('/users/create');
        }

        // Drivers, super_admins, and fleet_admins have no department
        if (in_array($role, [ROLE_DRIVER, ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN], true)) {
            $departmentId = null;
        }

        $userModel = new UserModel();
        try {
            $newId = $userModel->create([
                'company_id'    => $companyId,
                'department_id' => $departmentId,
                'role'          => $role,
                'employee_id'   => $employeeId,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'phone_number'  => $phone,
                'profile_photo' => null,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A user with that email or employee ID already exists.');
                Helpers::redirect('/users/create');
            }
            throw $e;
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'USER_CREATED',
            'users',
            $newId,
            null,
            ['email' => $email, 'role' => $role]
        );

        Helpers::setFlash('success', $firstName . ' ' . $lastName . ' created.');
        Helpers::redirect('/users');
    }

    // GET /users/{id}/edit
    public function edit(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $userModel = new UserModel();
        $user      = $userModel->findById($id);

        if (!$user) {
            Helpers::setFlash('error', 'User not found.');
            Helpers::redirect('/users');
        }

        $companyModel = new CompanyModel();
        $deptModel    = new DepartmentModel();

        $this->render('edit', [
            'page_title'  => 'Edit User — ' . $user['first_name'] . ' ' . $user['last_name'],
            'user'        => $user,
            'companies'   => $companyModel->findAll(),
            'departments' => $deptModel->findAllWithCompany(),
        ]);
    }

    // POST /users/{id}/edit
    public function update(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN, ROLE_ADMIN);

        $firstName    = trim($_POST['first_name']   ?? '');
        $lastName     = trim($_POST['last_name']    ?? '');
        $email        = trim($_POST['email']        ?? '');
        $role         = trim($_POST['role']         ?? '');
        $companyId    = (int) ($_POST['company_id'] ?? 0);
        $departmentId = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
        $employeeId   = trim($_POST['employee_id']  ?? '') ?: null;
        $phone        = trim($_POST['phone_number'] ?? '') ?: null;
        $isActive     = (int) ($_POST['is_active']  ?? 1);
        $newPassword  = $_POST['password'] ?? '';

        if ($firstName === '' || $lastName === '' || $email === '' ||
            $role === '' || $companyId === 0) {
            Helpers::setFlash('error', 'Please fill in all required fields.');
            Helpers::redirect('/users/' . $id . '/edit');
        }

        $userModel = new UserModel();
        $old       = $userModel->findById($id);

        if (!$old) {
            Helpers::setFlash('error', 'User not found.');
            Helpers::redirect('/users');
        }

        $actorRole    = Auth::role();
        $allowedRoles = ROLE_ASSIGNABLE[$actorRole] ?? [];

        // The actor must be permitted to assign the submitted role AND to
        // have created the user's current role — an admin must not be able
        // to edit a super_admin at all, even without changing their role.
        if (!in_array($role, $allowedRoles, true) || !in_array($old['role'], $allowedRoles, true)) {
            Helpers::setFlash('error', 'You are not permitted to assign or edit that role.');
            Helpers::redirect('/users/' . $id . '/edit');
        }

        if ($actorRole !== ROLE_SUPER_ADMIN && $companyId !== (int) $old['company_id']) {
            Helpers::setFlash('error', "You are not permitted to change a user's company.");
            Helpers::redirect('/users/' . $id . '/edit');
        }

        if ($actorRole === ROLE_ADMIN && (int) $old['company_id'] !== (int) Auth::companyId()) {
            Helpers::setFlash('error', 'You can only edit users in your own company.');
            Helpers::redirect('/users/' . $id . '/edit');
        }

        if (in_array($role, [ROLE_DRIVER, ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN], true)) {
            $departmentId = null;
        }

        // Handle profile photo upload
        $profilePhoto = $old['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploadSvc = new FileUploadService();
            if (!$uploadSvc->validate($_FILES['profile_photo'])) {
                Helpers::setFlash('error', 'Invalid photo — must be JPG or PNG under 2MB.');
                Helpers::redirect('/users/' . $id . '/edit');
            }
            if ($profilePhoto) {
                $uploadSvc->delete($profilePhoto, 'profile_photos');
            }
            $profilePhoto = $uploadSvc->store($_FILES['profile_photo'], 'profile_photos');
        }

        try {
            $userModel->update($id, [
                'company_id'    => $companyId,
                'department_id' => $departmentId,
                'role'          => $role,
                'employee_id'   => $employeeId,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'phone_number'  => $phone,
                'profile_photo' => $profilePhoto,
                'is_active'     => $isActive,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'A user with that email or employee ID already exists.');
                Helpers::redirect('/users/' . $id . '/edit');
            }
            throw $e;
        }

        // Optional password change
        if ($newPassword !== '') {
            $userModel->updatePassword($id, password_hash($newPassword, PASSWORD_BCRYPT));
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            'USER_UPDATED',
            'users',
            $id,
            ['email' => $old['email'], 'role' => $old['role'], 'is_active' => $old['is_active']],
            ['email' => $email, 'role' => $role, 'is_active' => $isActive]
        );

        Helpers::setFlash('success', $firstName . ' ' . $lastName . ' updated.');
        Helpers::redirect('/users');
    }

    // GET /users/{id}/driver-profile
    public function driverProfile(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $userModel = new UserModel();
        $user      = $userModel->findById($id);

        if (!$user || $user['role'] !== ROLE_DRIVER) {
            Helpers::setFlash('error', 'Driver not found.');
            Helpers::redirect('/users');
        }

        $driverModel = new DriverProfileModel();
        $profile     = $driverModel->findByUser($id);

        $this->render('driver_profile', [
            'page_title' => 'Driver Profile — ' . $user['first_name'] . ' ' . $user['last_name'],
            'user'       => $user,
            'profile'    => $profile,
        ]);
    }

    // POST /users/{id}/driver-profile
    public function updateDriverProfile(int $id): void
    {
        Auth::requireRole(ROLE_SUPER_ADMIN, ROLE_FLEET_ADMIN);

        $licenseNumber  = trim($_POST['license_number']   ?? '');
        $licenseType    = trim($_POST['license_type']     ?? '');
        $licenseExpiry  = trim($_POST['license_expiry']   ?? '');
        $restrictCodes  = trim($_POST['restriction_codes']?? '') ?: null;
        $status         = trim($_POST['status']           ?? 'available');

        if ($licenseNumber === '' || $licenseType === '' || $licenseExpiry === '') {
            Helpers::setFlash('error', 'License number, type, and expiry are required.');
            Helpers::redirect('/users/' . $id . '/driver-profile');
        }

        $driverModel  = new DriverProfileModel();
        $existing     = $driverModel->findByUser($id);

        // Handle license photo upload
        $licensePhoto = $existing['license_photo'] ?? null;
        if (!empty($_FILES['license_photo']['name'])) {
            $uploadSvc = new FileUploadService();
            if (!$uploadSvc->validate($_FILES['license_photo'])) {
                Helpers::setFlash('error', 'Invalid photo — must be JPG or PNG under 2MB.');
                Helpers::redirect('/users/' . $id . '/driver-profile');
            }
            if ($licensePhoto) {
                $uploadSvc->delete($licensePhoto, 'license_photos');
            }
            $licensePhoto = $uploadSvc->store($_FILES['license_photo'], 'license_photos');
        }

        $data = [
            'license_number'    => $licenseNumber,
            'license_type'      => $licenseType,
            'license_expiry'    => $licenseExpiry,
            'license_photo'     => $licensePhoto,
            'restriction_codes' => $restrictCodes,
            'status'            => $status,
        ];

        if ($existing) {
            $driverModel->update($id, $data);
            $action = 'DRIVER_PROFILE_UPDATED';
        } else {
            $driverModel->create($id, $data);
            $action = 'DRIVER_PROFILE_CREATED';
        }

        $auditModel = new AuditLogModel();
        $auditModel->log(
            (int) Auth::id(),
            $action,
            'driver_profiles',
            $id,
            null,
            ['license_number' => $licenseNumber, 'status' => $status]
        );

        Helpers::setFlash('success', 'Driver profile saved.');
        Helpers::redirect('/users/' . $id . '/driver-profile');
    }
}
