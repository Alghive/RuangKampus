<?php
namespace App\Models;

use Throwable;

final class DosenModel extends BaseModel
{
    protected const TABLE = 'dosen';
    protected const PRIMARY_KEY = 'id_dosen';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT `id_dosen`, `nidn`, `nama_dosen`, `email` FROM `dosen` ORDER BY `nama_dosen`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(?string $nidn, string $name, ?string $email): bool
    {
        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `dosen` (`nidn`, `nama_dosen`, `email`) VALUES (?, ?, ?)'
            );
            $statement->bind_param('sss', $nidn, $name, $email);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, ?string $nidn, string $name, ?string $email): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `dosen` SET `nidn` = ?, `nama_dosen` = ?, `email` = ? WHERE `id_dosen` = ?'
            );
            $statement->bind_param('sssi', $nidn, $name, $email, $id);
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
            $statement = self::connection()->prepare('DELETE FROM `dosen` WHERE `id_dosen` = ?');
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