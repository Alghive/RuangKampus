<?php
namespace App\Models;

use Throwable;

final class ProgramStudiModel extends BaseModel
{
    protected const TABLE = 'program_studi';
    protected const PRIMARY_KEY = 'id_program_studi';

    public static function all(): array
    {
        $result = self::connection()->query('SELECT * FROM `program_studi` ORDER BY `nama_prodi`');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $statement = self::connection()->prepare(
            'SELECT `id_program_studi`, `kode_prodi`, `nama_prodi` ' .
            'FROM `program_studi` WHERE `id_program_studi` = ? LIMIT 1'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $program = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $program;
    }

    public static function create(?string $code, string $name): bool
    {
        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `program_studi` (`kode_prodi`, `nama_prodi`) VALUES (?, ?)'
            );
            $statement->bind_param('ss', $code, $name);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, ?string $code, string $name): bool
    {
        if ($id <= 0) {
            return false;
        }

        $connection = self::connection();
        $transactionStarted = false;

        try {
            $connection->begin_transaction();
            $transactionStarted = true;

            $current = $connection->prepare(
                'SELECT `nama_prodi` FROM `program_studi` WHERE `id_program_studi` = ? FOR UPDATE'
            );
            $current->bind_param('i', $id);
            $current->execute();
            $existingProgram = $current->get_result()->fetch_assoc();
            $current->close();
            if ($existingProgram === null) {
                $connection->rollback();
                $transactionStarted = false;
                return false;
            }

            $statement = $connection->prepare(
                'UPDATE `program_studi` SET `kode_prodi` = ?, `nama_prodi` = ? WHERE `id_program_studi` = ?'
            );
            $statement->bind_param('ssi', $code, $name, $id);
            $statement->execute();
            $statement->close();

            if ($existingProgram['nama_prodi'] !== $name) {
                $students = $connection->prepare(
                    'UPDATE `mahasiswa` SET `program_studi` = ? WHERE `id_program_studi` = ?'
                );
                $students->bind_param('si', $name, $id);
                $students->execute();
                $students->close();
            }

            $connection->commit();
            $transactionStarted = false;
            return true;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $connection->rollback();
            }
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
                'DELETE FROM `program_studi` WHERE `id_program_studi` = ?'
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

    public static function findByName(string $name): ?array
    {
        $statement = self::connection()->prepare(
            'SELECT `id_program_studi`, `kode_prodi`, `nama_prodi` ' .
            'FROM `program_studi` WHERE `nama_prodi` = ? LIMIT 1'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $program = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $program;
    }

    public static function findOrCreate(string $name): int
    {
        $statement = self::connection()->prepare(
            'INSERT INTO `program_studi` (`nama_prodi`) VALUES (?) ' .
            'ON DUPLICATE KEY UPDATE `id_program_studi` = LAST_INSERT_ID(`id_program_studi`)'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $id = (int) self::connection()->insert_id;
        $statement->close();

        return $id;
    }
}