<?php

require_once __DIR__ . '/BaseApiController.php';

/**
 * AuthApiController
 *
 * Handles driver authentication for the Android app.
 * Issues a bearer token on successful login.
 *
 * POST /api/auth/login
 *   Body: {"email": "driver@lvms.test", "password": "password"}
 *   Success 200: {token, user_id, first_name, last_name, role}
 *   Error 401: {error: "Invalid credentials"}
 *   Error 403: {error: "Driver role required"}
 */
class AuthApiController extends BaseApiController
{
    public function login(): void
    {
        $body     = $this->body();
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->error(400, 'Email and password are required');
        }

        $userModel = new UserModel();
        $auditModel = new AuditLogModel();
        $user      = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $auditModel->log(
                null,
                'LOGIN_FAILED',
                'users',
                $user ? (int) $user['user_id'] : null,
                null,
                ['email' => $email, 'reason' => 'invalid credentials', 'source' => 'android']
            );
            $this->error(401, 'Invalid credentials');
        }

        if (!(bool) $user['is_active']) {
            $auditModel->log(
                null,
                'LOGIN_FAILED',
                'users',
                (int) $user['user_id'],
                null,
                ['email' => $email, 'reason' => 'account inactive', 'source' => 'android']
            );
            $this->error(401, 'Account is inactive');
        }

        // The Android app is for drivers only.
        // Admins and employees use the web interface.
        if ($user['role'] !== ROLE_DRIVER) {
            $auditModel->log(
                null,
                'LOGIN_FAILED',
                'users',
                (int) $user['user_id'],
                null,
                ['email' => $email, 'reason' => 'wrong role for this app', 'source' => 'android']
            );
            $this->error(403, 'This app is for drivers only');
        }

        // Generate a new bearer token — 32 bytes = 64 hex chars.
        // Replaces any previous token for this user.
        $token = bin2hex(random_bytes(32));
        $userModel->updateApiToken((int) $user['user_id'], $token);
        $userModel->updateLastLogin((int) $user['user_id']);
        $auditModel->log(
            (int) $user['user_id'],
            'LOGIN_SUCCESS',
            'users',
            (int) $user['user_id'],
            null,
            ['source' => 'android']
        );

        $this->json([
            'token'      => $token,
            'user_id'    => (int) $user['user_id'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'role'       => $user['role'],
        ]);
    }
}
