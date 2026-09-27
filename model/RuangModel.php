<?php
namespace App\Models;

use Throwable;

final class RuangModel extends BaseModel
{
    protected const TABLE = 'ruang';
    protected const PRIMARY_KEY = 'id_ruang';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT `id_ruang`, `kode_ruang`, `nama_ruang`, `kapasitas` ' .
            'FROM `ruang` ORDER BY `kode_ruang`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(string $code, string $name, ?int $capacity): bool
    {
        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `ruang` (`kode_ruang`, `nama_ruang`, `kapasitas`) VALUES (?, ?, ?)'
            );
            $statement->bind_param('ssi', $code, $name, $capacity);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, string $code, string $name, ?int $capacity): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `ruang` SET `kode_ruang` = ?, `nama_ruang` = ?, `kapasitas` = ? WHERE `id_ruang` = ?'
            );
            $statement->bind_param('ssii', $code, $name, $capacity, $id);
            $statement->execute();
            $updated = $statement->affected_rows === 1 || self::findById($id) !== null;
            $statement->close();

            return $updated;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare('DELETE FROM `ruang` WHERE `id_ruang` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();

            return $deleted;
        } catch (Throwable $exception) {
            return false;
        }
    }
}