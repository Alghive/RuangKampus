CREATE TABLE IF NOT EXISTS `krs` (
    `id_krs` INT NOT NULL AUTO_INCREMENT,
    `id_mahasiswa` INT NOT NULL,
    `id_tahun_akademik` INT NOT NULL,
    `status` ENUM('Draft', 'Diajukan', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Draft',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_krs`),
    UNIQUE KEY `uq_krs_mahasiswa_periode` (`id_mahasiswa`, `id_tahun_akademik`),
    KEY `idx_krs_tahun` (`id_tahun_akademik`),
    CONSTRAINT `fk_krs_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_krs_tahun` FOREIGN KEY (`id_tahun_akademik`) REFERENCES `tahun_akademik` (`id_tahun_akademik`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `krs_detail` (
    `id_krs_detail` INT NOT NULL AUTO_INCREMENT,
    `id_krs` INT NOT NULL,
    `id_kelas_kuliah` INT NOT NULL,
    `nilai_akhir` DECIMAL(5,2) NULL,
    PRIMARY KEY (`id_krs_detail`),
    UNIQUE KEY `uq_krs_detail_kelas` (`id_krs`, `id_kelas_kuliah`),
    KEY `idx_krs_detail_kelas` (`id_kelas_kuliah`),
    CONSTRAINT `fk_krs_detail_krs` FOREIGN KEY (`id_krs`) REFERENCES `krs` (`id_krs`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_krs_detail_kelas` FOREIGN KEY (`id_kelas_kuliah`) REFERENCES `kelas_kuliah` (`id_kelas_kuliah`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;