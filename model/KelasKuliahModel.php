<?php
namespace App\Models;

use Throwable;

final class KelasKuliahModel extends BaseModel
{
    protected const TABLE = 'kelas_kuliah';
    protected const PRIMARY_KEY = 'id_kelas_kuliah';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT k.`id_kelas_kuliah`, k.`id_mata_kuliah`, k.`id_tahun_akademik`, k.`id_dosen`, ' .
            'k.`kode_kelas`, k.`kuota`, mk.`kode_mk`, mk.`nama_mk`, mk.`sks`, ' .
            'ta.`tahun_akademik`, ta.`semester`, d.`nama_dosen` ' .
            'FROM `kelas_kuliah` AS k ' .
            'JOIN `mata_kuliah` AS mk ON mk.`id_mata_kuliah` = k.`id_mata_kuliah` ' .
            'JOIN `tahun_akademik` AS ta ON ta.`id_tahun_akademik` = k.`id_tahun_akademik` ' .
            'JOIN `dosen` AS d ON d.`id_dosen` = k.`id_dosen` ' .
            'ORDER BY ta.`tahun_akademik` DESC, ta.`semester`, mk.`kode_mk`, k.`kode_kelas`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(int $courseId, int $academicYearId, int $lecturerId, string $sectionCode, ?int $quota): bool
    {
        if ($courseId <= 0 || $academicYearId <= 0 || $lecturerId <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `kelas_kuliah` (`id_mata_kuliah`, `id_tahun_akademik`, `id_dosen`, `kode_kelas`, `kuota`) ' .
                'VALUES (?, ?, ?, ?, ?)'
            );
            $statement->bind_param('iiisi', $courseId, $academicYearId, $lecturerId, $sectionCode, $quota);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, int $courseId, int $academicYearId, int $lecturerId, string $sectionCode, ?int $quota): bool
    {
        if ($id <= 0 || $courseId <= 0 || $academicYearId <= 0 || $lecturerId <= 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `kelas_kuliah` SET `id_mata_kuliah` = ?, `id_tahun_akademik` = ?, ' .
                '`id_dosen` = ?, `kode_kelas` = ?, `kuota` = ? WHERE `id_kelas_kuliah` = ?'
            );
            $statement->bind_param('iiisii', $courseId, $academicYearId, $lecturerId, $sectionCode, $quota, $id);
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

        $connection = self::connection();
        $transactionStarted = false;

        try {
            $connection->begin_transaction();
            $transactionStarted = true;

            $statement = $connection->prepare(
                'SELECT `id_kelas_kuliah` FROM `kelas_kuliah` WHERE `id_kelas_kuliah` = ? FOR UPDATE'
            );
            $statement->bind_param('i', $id);
            $statement->execute();
            $classExists = $statement->get_result()->fetch_assoc() !== null;
            $statement->close();
            if (!$classExists) {
                $connection->rollback();
                $transactionStarted = false;
                return false;
            }

            foreach (['jadwal_kuliah', 'krs_detail'] as $table) {
                $statement = $connection->prepare(
                    'SELECT COUNT(*) AS `reference_count` FROM `' . $table . '` WHERE `id_kelas_kuliah` = ?'
                );
                $statement->bind_param('i', $id);
                $statement->execute();
                $references = (int) $statement->get_result()->fetch_assoc()['reference_count'];
                $statement->close();

                if ($references > 0) {
                    $connection->rollback();
                    $transactionStarted = false;
                    return false;
                }
            }

            $statement = $connection->prepare(
                'DELETE FROM `kelas_kuliah` WHERE `id_kelas_kuliah` = ?'
            );
            $statement->bind_param('i', $id);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();
            $connection->commit();
            $transactionStarted = false;

            return $deleted;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $connection->rollback();
            }
            return false;
        }
    }
}