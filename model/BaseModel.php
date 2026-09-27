<?php
namespace App\Models;

use mysqli;

abstract class BaseModel
{
    protected const TABLE = '';
    protected const PRIMARY_KEY = 'id';

    protected static function connection(): mysqli
    {
        return Database::connection();
    }

    public static function all(): array
    {
        $result = self::connection()->query('SELECT * FROM `' . static::TABLE . '`');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        if ($id < 0) {
            return null;
        }

        $statement = self::connection()->prepare(
            'SELECT * FROM `' . static::TABLE . '` WHERE `' . static::PRIMARY_KEY . '` = ?'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $row;
    }
}