SET @next_mahasiswa_id = (SELECT COALESCE(MAX(`id_mahasiswa`), 0) + 1 FROM `mahasiswa`);
UPDATE `mahasiswa`
SET `id_mahasiswa` = @next_mahasiswa_id
WHERE `id_mahasiswa` = 0;

ALTER TABLE `mahasiswa`
    MODIFY COLUMN `id_mahasiswa` INT(11) NOT NULL AUTO_INCREMENT,
    ADD COLUMN IF NOT EXISTS `id_program_studi` INT NULL AFTER `program_studi`;

INSERT IGNORE INTO `program_studi` (`nama_prodi`)
SELECT DISTINCT TRIM(`program_studi`)
FROM `mahasiswa`
WHERE `program_studi` IS NOT NULL AND TRIM(`program_studi`) <> '';

UPDATE `mahasiswa` AS m
JOIN `program_studi` AS p ON p.`nama_prodi` = TRIM(m.`program_studi`)
SET m.`id_program_studi` = p.`id_program_studi`
WHERE m.`id_program_studi` IS NULL;

SET @program_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_mahasiswa_program_studi'
);
SET @program_fk_sql = IF(
    @program_fk_exists = 0,
    'ALTER TABLE `mahasiswa` ADD CONSTRAINT `fk_mahasiswa_program_studi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE program_fk_statement FROM @program_fk_sql;
EXECUTE program_fk_statement;
DEALLOCATE PREPARE program_fk_statement;