<?php

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
