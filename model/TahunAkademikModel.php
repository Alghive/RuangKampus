<?php
namespace App\Models;

use Throwable;

final class TahunAkademikModel extends BaseModel
{
    protected const TABLE = 'tahun_akademik';
    protected const PRIMARY_KEY = 'id_tahun_akademik';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT `id_tahun_akademik`, `tahun_akademik`, `semester`, `status` ' .
            'FROM `tahun_akademik` ORDER BY `tahun_akademik` DESC, `semester`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(string $year, string $semester, string $status): bool
    {
        if (!self::validSemesterAndStatus($semester, $status)) {
            return false;
        }

        return self::save(null, $year, $semester, $status);
    }

    public static function update(int $id, string $year, string $semester, string $status): bool
    {
        if ($id <= 0 || !self::validSemesterAndStatus($semester, $status)) {
            return false;
        }

        return self::save($id, $year, $semester, $status);
    }

    private static function validSemesterAndStatus(string $semester, string $status): bool
    {
        return in_array($semester, ['Ganjil', 'Genap', 'Pendek'], true) &&
            in_array($status, ['Aktif', 'Nonaktif'], true);
    }

    private static function save(?int $id, string $year, string $semester, string $status): bool
    {
        $connection = self::connection();
        $lockAcquired = false;
        $transactionStarted = false;

        try {
            $lockResult = $connection->query("SELECT GET_LOCK('ruangkampus_active_academic_year', 10) AS lock_acquired");
            $lock = $lockResult->fetch_assoc();
            if ((int) ($lock['lock_acquired'] ?? 0) !== 1) {
                return false;
            }
            $lockAcquired = true;
            $connection->begin_transaction();
            $transactionStarted = true;

            if ($id !== null) {
                $existing = $connection->prepare(
                    'SELECT `id_tahun_akademik` FROM `tahun_akademik` WHERE `id_tahun_akademik` = ? FOR UPDATE'
                );
                $existing->bind_param('i', $id);
                $existing->execute();
                $found = $existing->get_result()->fetch_assoc();
                $existing->close();
                if ($found === null) {
                    $connection->rollback();
                    $transactionStarted = false;
                    return false;
                }
            }

            if ($status === 'Aktif') {
                $connection->query("UPDATE `tahun_akademik` SET `status` = 'Nonaktif' WHERE `status` = 'Aktif'");
            }

            if ($id === null) {
                $statement = $connection->prepare(
                    'INSERT INTO `tahun_akademik` (`tahun_akademik`, `semester`, `status`) VALUES (?, ?, ?)'
                );
                $statement->bind_param('sss', $year, $semester, $status);
            } else {
                $statement = $connection->prepare(
                    'UPDATE `tahun_akademik` SET `tahun_akademik` = ?, `semester` = ?, `status` = ? ' .
                    'WHERE `id_tahun_akademik` = ?'
                );
                $statement->bind_param('sssi', $year, $semester, $status, $id);
            }
            $statement->execute();
            $statement->close();
            $connection->commit();
            $transactionStarted = false;

            return true;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $connection->rollback();
            }
            return false;
        } finally {
            if ($lockAcquired) {
                $connection->query("SELECT RELEASE_LOCK('ruangkampus_active_academic_year')");
            }
        }
    }

    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'DELETE FROM `tahun_akademik` WHERE `id_tahun_akademik` = ? AND `status` = \'Nonaktif\''
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