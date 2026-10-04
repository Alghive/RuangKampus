<?php
namespace App\Models;

use Throwable;

final class JadwalKuliahModel extends BaseModel
{
    protected const TABLE = 'jadwal_kuliah';
    protected const PRIMARY_KEY = 'id_jadwal_kuliah';

    public static function all(): array
    {
        $result = self::connection()->query(
            'SELECT j.`id_jadwal_kuliah`, j.`id_kelas_kuliah`, j.`id_ruang`, j.`hari`, j.`jam_mulai`, j.`jam_selesai`, ' .
            'k.`kode_kelas`, mk.`kode_mk`, mk.`nama_mk`, d.`nama_dosen`, ' .
            'r.`kode_ruang`, r.`nama_ruang`, ta.`tahun_akademik`, ta.`semester` ' .
            'FROM `jadwal_kuliah` AS j ' .
            'JOIN `kelas_kuliah` AS k ON k.`id_kelas_kuliah` = j.`id_kelas_kuliah` ' .
            'JOIN `mata_kuliah` AS mk ON mk.`id_mata_kuliah` = k.`id_mata_kuliah` ' .
            'JOIN `dosen` AS d ON d.`id_dosen` = k.`id_dosen` ' .
            'JOIN `ruang` AS r ON r.`id_ruang` = j.`id_ruang` ' .
            'JOIN `tahun_akademik` AS ta ON ta.`id_tahun_akademik` = k.`id_tahun_akademik` ' .
            'ORDER BY ta.`tahun_akademik` DESC, ta.`semester`, mk.`kode_mk`, j.`hari`, j.`jam_mulai`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function create(int $classId, int $roomId, string $day, string $startTime, string $endTime): bool
    {
        if ($classId <= 0 || $roomId <= 0 || !self::isValidDay($day) || !self::isValidTimeRange($startTime, $endTime)) {
            return false;
        }

        if (self::hasConflict($classId, $roomId, $day, $startTime, $endTime, 0)) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'INSERT INTO `jadwal_kuliah` (`id_kelas_kuliah`, `id_ruang`, `hari`, `jam_mulai`, `jam_selesai`) ' .
                'VALUES (?, ?, ?, ?, ?)'
            );
            $statement->bind_param('iisss', $classId, $roomId, $day, $startTime, $endTime);
            $statement->execute();
            $statement->close();

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function update(int $id, int $classId, int $roomId, string $day, string $startTime, string $endTime): bool
    {
        if ($id <= 0 || $classId <= 0 || $roomId <= 0 || !self::isValidDay($day) || !self::isValidTimeRange($startTime, $endTime)) {
            return false;
        }

        if (self::hasConflict($classId, $roomId, $day, $startTime, $endTime, $id)) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `jadwal_kuliah` SET `id_kelas_kuliah` = ?, `id_ruang` = ?, `hari` = ?, `jam_mulai` = ?, `jam_selesai` = ? WHERE `id_jadwal_kuliah` = ?'
            );
            $statement->bind_param('iisssi', $classId, $roomId, $day, $startTime, $endTime, $id);
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
            $statement = self::connection()->prepare('DELETE FROM `jadwal_kuliah` WHERE `id_jadwal_kuliah` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();

            return $deleted;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function hasConflict(int $classId, int $roomId, string $day, string $startTime, string $endTime, int $ignoreId): bool
    {
        $classStatement = self::connection()->prepare(
            'SELECT `id_dosen` FROM `kelas_kuliah` WHERE `id_kelas_kuliah` = ? LIMIT 1'
        );
        $classStatement->bind_param('i', $classId);
        $classStatement->execute();
        $classRow = $classStatement->get_result()->fetch_assoc();
        $classStatement->close();

        if ($classRow === null) {
            return true;
        }

        $lecturerId = (int) $classRow['id_dosen'];
        $startMinutes = self::toMinutes($startTime);
        $endMinutes = self::toMinutes($endTime);

        $roomStatement = self::connection()->prepare(
            'SELECT `jam_mulai`, `jam_selesai` FROM `jadwal_kuliah` WHERE `id_ruang` = ? AND `hari` = ? AND `id_jadwal_kuliah` <> ?'
        );
        $roomStatement->bind_param('isi', $roomId, $day, $ignoreId);
        $roomStatement->execute();
        $roomRows = $roomStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $roomStatement->close();

        foreach ($roomRows as $row) {
            if (self::rangesOverlap($startMinutes, $endMinutes, self::toMinutes($row['jam_mulai']), self::toMinutes($row['jam_selesai']))) {
                return true;
            }
        }

        $lecturerStatement = self::connection()->prepare(
            'SELECT j.`jam_mulai`, j.`jam_selesai` ' .
            'FROM `jadwal_kuliah` AS j ' .
            'JOIN `kelas_kuliah` AS k ON k.`id_kelas_kuliah` = j.`id_kelas_kuliah` ' .
            'WHERE k.`id_dosen` = ? AND j.`hari` = ? AND j.`id_jadwal_kuliah` <> ?'
        );
        $lecturerStatement->bind_param('isi', $lecturerId, $day, $ignoreId);
        $lecturerStatement->execute();
        $lecturerRows = $lecturerStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $lecturerStatement->close();

        foreach ($lecturerRows as $row) {
            if (self::rangesOverlap($startMinutes, $endMinutes, self::toMinutes($row['jam_mulai']), self::toMinutes($row['jam_selesai']))) {
                return true;
            }
        }

        return false;
    }

    private static function isValidDay(string $day): bool
    {
        return in_array($day, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true);
    }

    private static function isValidTimeRange(string $startTime, string $endTime): bool
    {
        if (!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $startTime) || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $endTime)) {
            return false;
        }

        $startMinutes = self::toMinutes($startTime);
        $endMinutes = self::toMinutes($endTime);

        return $startMinutes < $endMinutes;
    }

    private static function rangesOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private static function toMinutes(string $time): int
    {
        $parts = array_map('intval', explode(':', trim($time)));
        $hours = $parts[0] ?? 0;
        $minutes = $parts[1] ?? 0;

        return ($hours * 60) + $minutes;
    }
}