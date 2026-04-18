<?php

class Helpers
{
    public static function redirect(string $path): never
    {
        $url = '/lvms/index.php?url=' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    public static function url(string $path): string
    {
        return '/lvms/index.php?url=' . ltrim($path, '/');
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
