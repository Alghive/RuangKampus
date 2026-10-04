<?php
namespace App\Models;

use Throwable;

final class KrsModel extends BaseModel
{
    protected const TABLE = 'krs';
    protected const PRIMARY_KEY = 'id_krs';

    public static function findActiveYear(): ?array
    {
        $statement = self::connection()->prepare(
            'SELECT * FROM `tahun_akademik` WHERE `status` = ? ORDER BY `id_tahun_akademik` DESC LIMIT 1'
        );
        $status = 'Aktif';
        $statement->bind_param('s', $status);
        $statement->execute();
        $year = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $year;
    }

    public static function findByStudentAndYear(int $studentId, int $academicYearId): ?array
    {
        if ($studentId <= 0 || $academicYearId <= 0) {
            return null;
        }

        $statement = self::connection()->prepare(
            'SELECT * FROM `krs` WHERE `id_mahasiswa` = ? AND `id_tahun_akademik` = ? LIMIT 1'
        );
        $statement->bind_param('ii', $studentId, $academicYearId);
        $statement->execute();
        $krs = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $krs;
    }

    public static function ensureForStudentYear(int $studentId, int $academicYearId): int
    {
        $existing = self::findByStudentAndYear($studentId, $academicYearId);
        if ($existing !== null) {
            return (int) $existing['id_krs'];
        }

        $statement = self::connection()->prepare(
            'INSERT INTO `krs` (`id_mahasiswa`, `id_tahun_akademik`, `status`) VALUES (?, ?, ?)' 
        );
        $status = 'Draft';
        $statement->bind_param('iis', $studentId, $academicYearId, $status);
        $statement->execute();
        $id = (int) self::connection()->insert_id;
        $statement->close();

        return $id;
    }

    public static function updateStatus(int $krsId, string $status): bool
    {
        if ($krsId <= 0 || !in_array($status, ['Draft', 'Diajukan', 'Disetujui'], true)) {
            return false;
        }

        try {
            $statement = self::connection()->prepare(
                'UPDATE `krs` SET `status` = ? WHERE `id_krs` = ?'
            );
            $statement->bind_param('si', $status, $krsId);
            $statement->execute();
            $updated = $statement->affected_rows === 1;
            $statement->close();

            return $updated;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isEditable(int $krsId): bool
    {
        if ($krsId <= 0) {
            return false;
        }

        $statement = self::connection()->prepare('SELECT `status` FROM `krs` WHERE `id_krs` = ? LIMIT 1');
        $statement->bind_param('i', $krsId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row !== null && ($row['status'] ?? 'Draft') === 'Draft';
    }

    public static function submitForApproval(int $studentId, int $academicYearId): bool
    {
        $krs = self::findByStudentAndYear($studentId, $academicYearId);
        if ($krs === null) {
            return false;
        }

        $classes = self::getStudentClasses($studentId, $academicYearId);
        if ($classes === []) {
            return false;
        }

        $status = $krs['status'] ?? 'Draft';
        if ($status === 'Disetujui') {
            return false;
        }

        return self::updateStatus((int) $krs['id_krs'], 'Diajukan');
    }

    public static function allForReview(): array
    {
        $result = self::connection()->query(
            'SELECT k.`id_krs`, k.`id_mahasiswa`, k.`id_tahun_akademik`, k.`status`, ' .
            'm.`npm`, m.`nama_mahasiswa`, ta.`tahun_akademik`, ta.`semester`, ' .
            'COUNT(kd.`id_krs_detail`) AS `jumlah_kelas` ' .
            'FROM `krs` AS k ' .
            'JOIN `mahasiswa` AS m ON m.`id_mahasiswa` = k.`id_mahasiswa` ' .
            'JOIN `tahun_akademik` AS ta ON ta.`id_tahun_akademik` = k.`id_tahun_akademik` ' .
            'LEFT JOIN `krs_detail` AS kd ON kd.`id_krs` = k.`id_krs` ' .
            'GROUP BY k.`id_krs`, k.`id_mahasiswa`, k.`id_tahun_akademik`, k.`status`, m.`npm`, m.`nama_mahasiswa`, ta.`tahun_akademik`, ta.`semester` ' .
            'ORDER BY CASE k.`status` WHEN "Diajukan" THEN 0 WHEN "Draft" THEN 1 ELSE 2 END, ta.`tahun_akademik` DESC, m.`nama_mahasiswa`'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function addClass(int $studentId, int $academicYearId, int $classId): bool
    {
        if ($studentId <= 0 || $academicYearId <= 0 || $classId <= 0) {
            return false;
        }

        $krs = self::findByStudentAndYear($studentId, $academicYearId);
        if ($krs !== null && !self::isEditable((int) $krs['id_krs'])) {
            return false;
        }

        $krsId = self::ensureForStudentYear($studentId, $academicYearId);
        if ($krsId <= 0) {
            return false;
        }

        if (!self::isEditable($krsId)) {
            return false;
        }

        $classStatement = self::connection()->prepare(
            'SELECT k.`id_kelas_kuliah`, k.`id_tahun_akademik`, k.`kuota`, ' .
            'mk.`nama_mk`, mk.`kode_mk`, ' .
            'COUNT(kd.`id_krs_detail`) AS `selected_count` ' .
            'FROM `kelas_kuliah` AS k ' .
            'LEFT JOIN `mata_kuliah` AS mk ON mk.`id_mata_kuliah` = k.`id_mata_kuliah` ' .
            'LEFT JOIN `krs_detail` AS kd ON kd.`id_kelas_kuliah` = k.`id_kelas_kuliah` ' .
            'WHERE k.`id_kelas_kuliah` = ? ' .
            'GROUP BY k.`id_kelas_kuliah`, k.`id_tahun_akademik`, k.`kuota`, mk.`nama_mk`, mk.`kode_mk`'
        );
        $classStatement->bind_param('i', $classId);
        $classStatement->execute();
        $classRow = $classStatement->get_result()->fetch_assoc();
        $classStatement->close();

        if ($classRow === null || (int) $classRow['id_tahun_akademik'] !== $academicYearId) {
            return false;
        }

        if (KrsDetailModel::hasClass($krsId, $classId)) {
            return false;
        }

        if ($classRow['kuota'] !== null) {
            $selectedCount = (int) $classRow['selected_count'];
            if ($selectedCount >= (int) $classRow['kuota']) {
                return false;
            }
        }

        if (self::hasScheduleConflict($krsId, $classId)) {
            return false;
        }

        return KrsDetailModel::addClass($krsId, $classId);
    }

    public static function removeClass(int $krsId, int $classId): bool
    {
        if ($krsId <= 0 || $classId <= 0 || !self::isEditable($krsId)) {
            return false;
        }

        return KrsDetailModel::removeClass($krsId, $classId);
    }

    public static function getStudentClasses(int $studentId, int $academicYearId): array
    {
        if ($studentId <= 0 || $academicYearId <= 0) {
            return [];
        }

        $krs = self::findByStudentAndYear($studentId, $academicYearId);
        if ($krs === null) {
            return [];
        }

        $statement = self::connection()->prepare(
            'SELECT kd.`id_krs_detail`, kd.`id_kelas_kuliah`, k.`kode_kelas`, mk.`kode_mk`, mk.`nama_mk`, d.`nama_dosen`, ' .
            'ta.`tahun_akademik`, ta.`semester`, ' .
            'COALESCE((SELECT COUNT(*) FROM `krs_detail` AS kd2 WHERE kd2.`id_kelas_kuliah` = k.`id_kelas_kuliah`), 0) AS `terdaftar` ' .
            'FROM `krs_detail` AS kd ' .
            'JOIN `kelas_kuliah` AS k ON k.`id_kelas_kuliah` = kd.`id_kelas_kuliah` ' .
            'JOIN `mata_kuliah` AS mk ON mk.`id_mata_kuliah` = k.`id_mata_kuliah` ' .
            'JOIN `dosen` AS d ON d.`id_dosen` = k.`id_dosen` ' .
            'JOIN `tahun_akademik` AS ta ON ta.`id_tahun_akademik` = k.`id_tahun_akademik` ' .
            'WHERE kd.`id_krs` = ? ' .
            'ORDER BY mk.`kode_mk`, k.`kode_kelas`'
        );
        $statement->bind_param('i', $krs['id_krs']);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public static function getAvailableClasses(int $studentId, int $academicYearId): array
    {
        if ($studentId <= 0 || $academicYearId <= 0) {
            return [];
        }

        $krs = self::findByStudentAndYear($studentId, $academicYearId);

        $sql = 'SELECT k.`id_kelas_kuliah`, k.`kode_kelas`, mk.`kode_mk`, mk.`nama_mk`, d.`nama_dosen`, k.`kuota`, ' .
            'ta.`tahun_akademik`, ta.`semester`, ' .
            'COALESCE((SELECT COUNT(*) FROM `krs_detail` WHERE `id_kelas_kuliah` = k.`id_kelas_kuliah`), 0) AS `terdaftar` ' .
            'FROM `kelas_kuliah` AS k ' .
            'JOIN `mata_kuliah` AS mk ON mk.`id_mata_kuliah` = k.`id_mata_kuliah` ' .
            'JOIN `dosen` AS d ON d.`id_dosen` = k.`id_dosen` ' .
            'JOIN `tahun_akademik` AS ta ON ta.`id_tahun_akademik` = k.`id_tahun_akademik` ' .
            'WHERE k.`id_tahun_akademik` = ?';

        $params = [$academicYearId];
        $types = 'i';

        if ($krs !== null) {
            $sql .= ' AND k.`id_kelas_kuliah` NOT IN (SELECT `id_kelas_kuliah` FROM `krs_detail` WHERE `id_krs` = ?)';
            $params[] = $krs['id_krs'];
            $types .= 'i';
        }

        $sql .= ' ORDER BY mk.`kode_mk`, k.`kode_kelas`';

        $statement = self::connection()->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    private static function hasScheduleConflict(int $krsId, int $classId): bool
    {
        $newClassStatement = self::connection()->prepare(
            'SELECT j.`hari`, j.`jam_mulai`, j.`jam_selesai` ' .
            'FROM `jadwal_kuliah` AS j WHERE j.`id_kelas_kuliah` = ?'
        );
        $newClassStatement->bind_param('i', $classId);
        $newClassStatement->execute();
        $newScheduleRows = $newClassStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $newClassStatement->close();

        if ($newScheduleRows === []) {
            return false;
        }

        $statement = self::connection()->prepare(
            'SELECT j.`hari`, j.`jam_mulai`, j.`jam_selesai` ' .
            'FROM `krs_detail` AS kd ' .
            'JOIN `kelas_kuliah` AS k ON k.`id_kelas_kuliah` = kd.`id_kelas_kuliah` ' .
            'JOIN `jadwal_kuliah` AS j ON j.`id_kelas_kuliah` = k.`id_kelas_kuliah` ' .
            'WHERE kd.`id_krs` = ?'
        );
        $statement->bind_param('i', $krsId);
        $statement->execute();
        $selectedSchedules = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        foreach ($newScheduleRows as $newRow) {
            foreach ($selectedSchedules as $selectedRow) {
                if ($newRow['hari'] !== $selectedRow['hari']) {
                    continue;
                }

                $newStart = self::toMinutes($newRow['jam_mulai']);
                $newEnd = self::toMinutes($newRow['jam_selesai']);
                $selectedStart = self::toMinutes($selectedRow['jam_mulai']);
                $selectedEnd = self::toMinutes($selectedRow['jam_selesai']);

                if ($newStart < $selectedEnd && $newEnd > $selectedStart) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function toMinutes(string $time): int
    {
        $parts = array_map('intval', explode(':', trim($time)));
        $hours = $parts[0] ?? 0;
        $minutes = $parts[1] ?? 0;

        return ($hours * 60) + $minutes;
    }
}