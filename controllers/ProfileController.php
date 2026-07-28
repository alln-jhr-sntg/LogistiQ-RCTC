<?php

class ProfileController
{
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $content_view = __DIR__ . '/../views/profile/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/main.php';
    }

    // GET /profile
    public function index(): void
    {
        Auth::requireLogin();

        $userModel = new UserModel();
        $user      = $userModel->findById((int) Auth::id());

        if (!$user) {
            Auth::logout();
            Helpers::redirect('/login');
        }

        $this->render('index', [
            'page_title' => 'My Profile',
            'user'       => $user,
        ]);
    }

    // POST /profile
    // Dispatches to either _handleProfileUpdate() or _handlePasswordChange()
    // based on the hidden _section field submitted by each form.
    public function update(): void
    {
        Auth::requireLogin();

        $section = $_POST['_section'] ?? 'profile';

        if ($section === 'password') {
            $this->_handlePasswordChange();
        } else {
            $this->_handleProfileUpdate();
        }
    }

    // ── Profile info + photo ──────────────────────────────────────

    private function _handleProfileUpdate(): void
    {
        $firstName = trim($_POST['first_name']   ?? '');
        $lastName  = trim($_POST['last_name']    ?? '');
        $email     = trim($_POST['email']        ?? '');
        $phone     = trim($_POST['phone_number'] ?? '') ?: null;

        if ($firstName === '' || $lastName === '' || $email === '') {
            Helpers::setFlash('error', 'First name, last name, and email are required.');
            Helpers::redirect('/profile');
        }

        $userId    = (int) Auth::id();
        $userModel = new UserModel();
        $user      = $userModel->findById($userId);

        // Handle profile photo upload
        $oldPhoto     = $user['profile_photo'];
        $profilePhoto = $oldPhoto;
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploadSvc = new FileUploadService();
            if (!$uploadSvc->validate($_FILES['profile_photo'])) {
                Helpers::setFlash('error', 'Invalid photo — must be JPG or PNG under 2MB.');
                Helpers::redirect('/profile');
            }
            if ($profilePhoto) {
                $uploadSvc->delete($profilePhoto, 'profile_photos');
            }
            $profilePhoto = $uploadSvc->store($_FILES['profile_photo'], 'profile_photos');
        }

        try {
            $userModel->update($userId, [
                'company_id'    => $user['company_id'],
                'department_id' => $user['department_id'],
                'role'          => $user['role'],
                'employee_id'   => $user['employee_id'],
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $email,
                'phone_number'  => $phone,
                'profile_photo' => $profilePhoto,
                'is_active'     => $user['is_active'],
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Helpers::setFlash('error', 'That email address is already in use by another account.');
                Helpers::redirect('/profile');
            }
            throw $e;
        }

        // Refresh topbar name immediately if it changed
        $_SESSION['full_name']     = $firstName . ' ' . $lastName;
        $_SESSION['profile_photo'] = $profilePhoto;

        $auditModel = new AuditLogModel();
        $auditModel->log(
            $userId,
            'PROFILE_UPDATED',
            'users',
            $userId,
            ['first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'email' => $user['email']],
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email]
        );

        // Separate audit entry when photo was actually replaced
        if ($profilePhoto !== $oldPhoto) {
            $auditModel->log(
                $userId,
                'PHOTO_CHANGED',
                'users',
                $userId,
                ['profile_photo' => $oldPhoto],
                ['profile_photo' => $profilePhoto]
            );
        }

        Helpers::setFlash('success', 'Profile updated.');
        Helpers::redirect('/profile');
    }

    // ── Password change ───────────────────────────────────────────

    private function _handlePasswordChange(): void
    {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password']     ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
            Helpers::setFlash('error', 'All three password fields are required.');
            Helpers::redirect('/profile');
        }

        $userId    = (int) Auth::id();
        $userModel = new UserModel();
        $user      = $userModel->findById($userId);

        if (!password_verify($currentPass, $user['password_hash'])) {
            Helpers::setFlash('error', 'Current password is incorrect.');
            Helpers::redirect('/profile');
        }

        if ($newPass !== $confirmPass) {
            Helpers::setFlash('error', 'New passwords do not match.');
            Helpers::redirect('/profile');
        }

        if (strlen($newPass) < 8) {
            Helpers::setFlash('error', 'New password must be at least 8 characters.');
            Helpers::redirect('/profile');
        }

        $userModel->updatePassword($userId, password_hash($newPass, PASSWORD_BCRYPT));

        // Log password change separately — no old/new values for security
        $auditModel = new AuditLogModel();
        $auditModel->log(
            $userId,
            'PASSWORD_CHANGED',
            'users',
            $userId
        );

        Helpers::setFlash('success', 'Password changed successfully.');
        Helpers::redirect('/profile');
    }
}
