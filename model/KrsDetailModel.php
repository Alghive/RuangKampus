<?php
namespace App\Models;

use Throwable;

final class KrsDetailModel extends BaseModel
{
    protected const TABLE = 'krs_detail';
    protected const PRIMARY_KEY = 'id_krs_detail';

    public static function hasClass(int $krsId, int $classId): bool
    {
        $statement = self::connection()->prepare(
            'SELECT 1 FROM `krs_detail` WHERE `id_krs` = ? AND `id_kelas_kuliah` = ? LIMIT 1'
        );
        $statement->bind_param('ii', $krsId, $classId);
        $statement->execute();
        $found = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $found;
    }

    public static function addClass(int $krsId, int $classId): bool
    {
        if ($krsId <= 0 || $classId <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `krs_detail` (`id_krs`, `id_kelas_kuliah`) VALUES (?, ?)'
            );
            $statement->bind_param('ii', $krsId, $classId);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function removeClass(int $krsId, int $classId): bool
    {
        if ($krsId <= 0 || $classId <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'DELETE FROM `krs_detail` WHERE `id_krs` = ? AND `id_kelas_kuliah` = ?'
            );
            $statement->bind_param('ii', $krsId, $classId);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();

            return $deleted;
        } catch (Throwable $exception) {
            return false;
        }
    }
}