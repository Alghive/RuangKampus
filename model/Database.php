<?php
namespace App\Models;

use mysqli;

final class Database
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            self::$connection = new mysqli(
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                getenv('DB_NAME') ?: 'perkuliahan',
                (int) (getenv('DB_PORT') ?: 3306)
            );
            self::$connection->set_charset('utf8mb4');
        }

        return self::$connection;
    }
}