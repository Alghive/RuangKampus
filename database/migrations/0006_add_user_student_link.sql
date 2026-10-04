ALTER TABLE `users`
    ADD COLUMN `id_mahasiswa` INT NULL AFTER `role`,
    ADD UNIQUE KEY `uq_users_id_mahasiswa` (`id_mahasiswa`);

ALTER TABLE `users`
    ADD CONSTRAINT `fk_users_mahasiswa`
    FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;