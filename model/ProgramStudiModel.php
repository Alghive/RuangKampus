<?php
namespace App\Models;

final class ProgramStudiModel extends BaseModel
{
    protected const TABLE = 'program_studi';
    protected const PRIMARY_KEY = 'id_program_studi';

    public static function all(): array
    {
        $result = self::connection()->query('SELECT * FROM `program_studi` ORDER BY `nama_prodi`');
        return $result->fetch_all(MYSQLI_ASSOC);
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