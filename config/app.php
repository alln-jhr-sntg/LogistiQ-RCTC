<?php

// ── Global error/exception handling ─────────────────────────────
// Registered before session/constants/database so a fatal anywhere in
// bootstrap is also caught. display_errors is always off — raw PHP
// notices printed ahead of the <!DOCTYPE> are what drop the browser
// into Quirks Mode, so nothing PHP-native may echo directly. Detail
// goes to error_log() always, and to the rendered page only when
// DEBUG is true. ob_start() means a throw mid-render can be fully
// discarded and replaced with a complete page instead of shipping a
// truncated one.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

// Error types PHP treats as fatal — set_error_handler() below never
// receives these, so register_shutdown_function() is the only way to
// catch and render them.
const FATAL_ERROR_TYPES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

// True for the Android API (api/index.php sets its JSON header before
// requiring this file) and for the web router's own JSON endpoints
// (NotificationController::unreadCount, GpsController feed), so those
// callers get {"error": "..."} instead of an HTML error page.
function request_wants_json(): bool
{
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        return true;
    }
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'application/json') !== false) {
            return true;
        }
    }
    return false;
}

// Discards any partial output and emits a complete response — JSON for
// API callers, otherwise the standalone views/errors/error.php page.
// $detail (exception message/file/line/trace) is shown only when DEBUG
// is true; production always gets the generic $message.
function render_error_page(int $code, string $title, string $message, string $detail = '', bool $showLink = false): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $debug = defined('DEBUG') && DEBUG === true;

    if (request_wants_json()) {
        http_response_code($code);
        header('Content-Type: application/json');
        $payload = ['error' => $message];
        if ($debug && $detail !== '') {
            $payload['detail'] = $detail;
        }
        echo json_encode($payload);
        return;
    }

    if (!headers_sent()) {
        http_response_code($code);
        require __DIR__ . '/../views/errors/error.php';
        return;
    }

    // Fallback only — with ob_start() above, headers should never
    // already be sent by the time this runs. Close whatever tags may
    // be open so the page never ends mid-JavaScript.
    echo '</script></style></div></div>';
    echo '<div class="flash flash-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    if ($debug && $detail !== '') {
        echo '<pre class="flash flash-error">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        // Respects both the configured level and the @ operator.
        return true;
    }
    error_log(sprintf(
        '[LVMS] PHP error [%d]: %s in %s:%d | url=%s',
        $errno,
        $errstr,
        $errfile,
        $errline,
        $_GET['url'] ?? ''
    ));
    return true; // handled — never let PHP echo it inline
});

set_exception_handler(function (Throwable $e): void {
    error_log(sprintf(
        '[LVMS] Uncaught %s: %s in %s:%d | url=%s | trace=%s',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $_GET['url'] ?? '',
        $e->getTraceAsString()
    ));

    $detail = sprintf(
        "%s: %s\nin %s:%d\n\n%s",
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    render_error_page(
        500,
        'Something went wrong',
        'An unexpected error occurred. Please try again, or contact support if the problem persists.',
        $detail
    );
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], FATAL_ERROR_TYPES, true)) {
        return;
    }

    error_log(sprintf(
        '[LVMS] Fatal error: %s in %s:%d | url=%s',
        $error['message'],
        $error['file'],
        $error['line'],
        $_GET['url'] ?? ''
    ));

    $detail = sprintf("%s\nin %s:%d", $error['message'], $error['file'], $error['line']);

    render_error_page(
        500,
        'Something went wrong',
        'An unexpected error occurred. Please try again, or contact support if the problem persists.',
        $detail
    );
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser caching of protected pages
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

spl_autoload_register(function (string $class): void {
    $dirs = [
        __DIR__ . '/../core/',
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
