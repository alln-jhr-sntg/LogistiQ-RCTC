<?php

class Database
{
    private static ?PDO $instance = null;
    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = DB_HOST;
            $dbname  = DB_NAME;
            $user    = DB_USER;
            $pass    = DB_PASS;
            $charset = DB_CHARSET;
            $dsn     = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$instance = new PDO($dsn, $user, $pass, $options);
            // MySQL's CURRENT_TIMESTAMP/NOW() otherwise evaluate in the
            // server's default session timezone (UTC on Hostinger), which
            // is 8 hours behind Asia/Manila for every TIMESTAMP column
            // that relies on a DEFAULT CURRENT_TIMESTAMP (e.g. gps_tracking_logs.logged_at).
            self::$instance->exec("SET time_zone = '+08:00'");
        }
        return self::$instance;
    }
}
