<?php

/**
 * BaseApiController
 *
 * Shared helpers for all API controllers.
 * Provides JSON response methods and request body parsing.
 *
 * Usage:
 *   class FooApiController extends BaseApiController { ... }
 *   $this->json(['key' => 'value']);
 *   $this->error(401, 'Unauthorized');
 *   $body = $this->body(); // parsed JSON request body
 */
abstract class BaseApiController
{
    /** Authenticated user context from api/index.php middleware */
    protected array $ctx;

    public function __construct(array $ctx = [])
    {
        $this->ctx = $ctx;
    }

    /**
     * Return authenticated user_id from the request context.
     */
    protected function authUserId(): int
    {
        return (int) ($this->ctx['user_id'] ?? 0);
    }

    /**
     * Return authenticated user role from the request context.
     */
    protected function authRole(): string
    {
        return (string) ($this->ctx['role'] ?? '');
    }

    /**
     * Abort with a JSON error response.
     * Terminates execution.
     *
     * @param int    $code    HTTP status code
     * @param string $message Human-readable error message
     */
    protected function error(int $code, string $message): never
    {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }

    /**
     * Send a successful JSON response.
     * Terminates execution.
     *
     * @param array<string, mixed> $data
     * @param int                  $code  HTTP status code (default 200)
     */
    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    /**
     * Parse the JSON request body.
     * Returns an empty array on invalid or missing JSON.
     *
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        return json_decode($raw, true) ?? [];
    }

    /**
     * Require the authenticated user to have a specific role.
     * Aborts with 403 if the role doesn't match.
     */
    protected function requireRole(string $role): void
    {
        if ($this->authRole() !== $role) {
            $this->error(403, 'Forbidden — this endpoint requires role: ' . $role);
        }
    }
}
