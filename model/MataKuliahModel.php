<?php
namespace App\Models;

use Throwable;

final class MataKuliahModel extends BaseModel
{
    protected const TABLE = 'mata_kuliah';
    protected const PRIMARY_KEY = 'id_mata_kuliah';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT mk.`id_mata_kuliah`, mk.`kode_mk`, mk.`nama_mk`, mk.`sks`, ' .
            'mk.`id_program_studi`, ps.`nama_prodi` FROM `mata_kuliah` AS mk ' .
            'LEFT JOIN `program_studi` AS ps ON ps.`id_program_studi` = mk.`id_program_studi` ' .
            'ORDER BY mk.`kode_mk`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(string $code, string $name, int $credits, ?int $programId): bool
    {
        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `sks`, `id_program_studi`) VALUES (?, ?, ?, ?)'
            );
            $statement->bind_param('ssii', $code, $name, $credits, $programId);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, string $code, string $name, int $credits, ?int $programId): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `mata_kuliah` SET `kode_mk` = ?, `nama_mk` = ?, `sks` = ?, ' .
                '`id_program_studi` = ? WHERE `id_mata_kuliah` = ?'
            );
            $statement->bind_param('ssiii', $code, $name, $credits, $programId, $id);
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
            $statement = self::connection()->prepare(
                'DELETE FROM `mata_kuliah` WHERE `id_mata_kuliah` = ?'
            );
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