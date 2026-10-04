<?php
namespace App\Models;

use Throwable;

final class MahasiswaModel extends BaseModel
{
    protected const TABLE = 'mahasiswa';
    protected const PRIMARY_KEY = 'id_mahasiswa';


public static function update(array $data): bool
{
    $connection = self::connection();
    $statement = null;

    try {
        $programName = trim($data['program_studi']);
        $programId = ProgramStudiModel::findOrCreate($programName);

        $id = (int) $data['id_mahasiswa'];
        $npm = trim($data['npm']);
        $name = trim($data['nama_mahasiswa']);
        $gender = $data['jenis_kelamin'];
        $year = (int) $data['angkatan'];
        $religion = trim($data['agama']);

        // Validasi agama sesuai ENUM database
        $allowedReligions = [
            'Islam',
            'Kristen',
            'Katolik',
            'Hindu',
            'Buddha',
            'Konghucu',
            'Aliran Lain'
        ];

        if (!in_array($religion, $allowedReligions, true)) {
            return false;
        }

        $statement = $connection->prepare(
            'UPDATE `mahasiswa` SET
                `npm` = ?,
                `nama_mahasiswa` = ?,
                `jenis_kelamin` = ?,
                `program_studi` = ?,
                `id_program_studi` = ?,
                `angkatan` = ?,
                `agama` = ?
             WHERE `id_mahasiswa` = ?'
        );

        // s = string, i = integer
        $statement->bind_param(
            'ssssiisi',
            $npm,
            $name,
            $gender,
            $programName,
            $programId,
            $year,
            $religion,
            $id
        );

        $statement->execute();

        return $statement->affected_rows === 1
            || self::findById($id) !== null;

    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        return false;

    } finally {
        if ($statement !== null) {
            $statement->close();
        }
    }
}



    public static function delete(int $id): bool
    {
        if ($id < 0) {
            return false;
        }

        try {
            $statement = self::connection()->prepare('DELETE FROM `mahasiswa` WHERE `id_mahasiswa` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();

            return $deleted;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function insertMany(array $rows): int|false
    {
        if ($rows === []) {
            return false;
        }

        $connection = self::connection();
        $statement = null;
        $transactionStarted = false;

        try {
            $connection->begin_transaction();
            $transactionStarted = true;
            $statement = $connection->prepare(
                'INSERT INTO `mahasiswa` (`npm`, `nama_mahasiswa`, `jenis_kelamin`, `program_studi`, ' .
                '`id_program_studi`, `angkatan`, `agama`) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($rows as $row) {
                $programName = trim($row['program_studi']);
                $programId = ProgramStudiModel::findOrCreate($programName);
                $npm = trim($row['npm']);
                $name = trim($row['nama_mahasiswa']);
                $gender = $row['jenis_kelamin'];
                $year = (int) $row['angkatan'];
                $religion = $row['agama'];
                $statement->bind_param('ssssiis', $npm, $name, $gender, $programName, $programId, $year, $religion);
                $statement->execute();
            }

            $connection->commit();
            $transactionStarted = false;

            return count($rows);
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $connection->rollback();
            }
            return false;
        } finally {
            if ($statement !== null) {
                $statement->close();
            }
        }
    }
}