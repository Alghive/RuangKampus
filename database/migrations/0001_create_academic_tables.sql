CREATE TABLE IF NOT EXISTS `mahasiswa` (
    `id_mahasiswa` INT(11) NOT NULL,
    `npm` VARCHAR(20) NOT NULL,
    `nama_mahasiswa` VARCHAR(100) NOT NULL,
    `jenis_kelamin` CHAR(1) DEFAULT NULL,
    `program_studi` VARCHAR(100) DEFAULT NULL,
    `angkatan` INT(11) DEFAULT NULL,
    `agama` ENUM('Islam', 'Protestan', 'Kristen', 'Budha', 'Hindu', 'Konghucu', 'Aliran Lain') DEFAULT NULL,
    PRIMARY KEY (`id_mahasiswa`),
    UNIQUE KEY `uq_mahasiswa_npm` (`npm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `program_studi` (
    `id_program_studi` INT NOT NULL AUTO_INCREMENT,
    `kode_prodi` VARCHAR(20) NULL,
    `nama_prodi` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_program_studi`),
    UNIQUE KEY `uq_program_studi_kode` (`kode_prodi`),
    UNIQUE KEY `uq_program_studi_nama` (`nama_prodi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dosen` (
    `id_dosen` INT NOT NULL AUTO_INCREMENT,
    `nidn` VARCHAR(20) NULL,
    `nama_dosen` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_dosen`),
    UNIQUE KEY `uq_dosen_nidn` (`nidn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mata_kuliah` (
    `id_mata_kuliah` INT NOT NULL AUTO_INCREMENT,
    `kode_mk` VARCHAR(20) NOT NULL,
    `nama_mk` VARCHAR(120) NOT NULL,
    `sks` TINYINT UNSIGNED NOT NULL,
    `id_program_studi` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_mata_kuliah`),
    UNIQUE KEY `uq_mata_kuliah_kode` (`kode_mk`),
    KEY `idx_mata_kuliah_program_studi` (`id_program_studi`),
    CONSTRAINT `fk_mata_kuliah_program_studi` FOREIGN KEY (`id_program_studi`) REFERENCES `program_studi` (`id_program_studi`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tahun_akademik` (
    `id_tahun_akademik` INT NOT NULL AUTO_INCREMENT,
    `tahun_akademik` VARCHAR(9) NOT NULL,
    `semester` ENUM('Ganjil', 'Genap', 'Pendek') NOT NULL,
    `status` ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Nonaktif',
    PRIMARY KEY (`id_tahun_akademik`),
    UNIQUE KEY `uq_tahun_akademik_semester` (`tahun_akademik`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ruang` (
    `id_ruang` INT NOT NULL AUTO_INCREMENT,
    `kode_ruang` VARCHAR(30) NOT NULL,
    `nama_ruang` VARCHAR(100) NOT NULL,
    `kapasitas` SMALLINT UNSIGNED NULL,
    PRIMARY KEY (`id_ruang`),
    UNIQUE KEY `uq_ruang_kode` (`kode_ruang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `kelas_kuliah` (
    `id_kelas_kuliah` INT NOT NULL AUTO_INCREMENT,
    `id_mata_kuliah` INT NOT NULL,
    `id_tahun_akademik` INT NOT NULL,
    `id_dosen` INT NOT NULL,
    `kode_kelas` VARCHAR(10) NOT NULL,
    `kuota` SMALLINT UNSIGNED NULL,
    PRIMARY KEY (`id_kelas_kuliah`),
    UNIQUE KEY `uq_kelas_kuliah_periode` (`id_mata_kuliah`, `id_tahun_akademik`, `kode_kelas`),
    KEY `idx_kelas_kuliah_tahun` (`id_tahun_akademik`),
    KEY `idx_kelas_kuliah_dosen` (`id_dosen`),
    CONSTRAINT `fk_kelas_kuliah_mata_kuliah` FOREIGN KEY (`id_mata_kuliah`) REFERENCES `mata_kuliah` (`id_mata_kuliah`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_kelas_kuliah_tahun` FOREIGN KEY (`id_tahun_akademik`) REFERENCES `tahun_akademik` (`id_tahun_akademik`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_kelas_kuliah_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `jadwal_kuliah` (
    `id_jadwal_kuliah` INT NOT NULL AUTO_INCREMENT,
    `id_kelas_kuliah` INT NOT NULL,
    `id_ruang` INT NOT NULL,
    `hari` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    `jam_mulai` TIME NOT NULL,
    `jam_selesai` TIME NOT NULL,
    PRIMARY KEY (`id_jadwal_kuliah`),
    KEY `idx_jadwal_kuliah_kelas` (`id_kelas_kuliah`),
    KEY `idx_jadwal_kuliah_ruang` (`id_ruang`),
    CONSTRAINT `fk_jadwal_kuliah_kelas` FOREIGN KEY (`id_kelas_kuliah`) REFERENCES `kelas_kuliah` (`id_kelas_kuliah`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_jadwal_kuliah_ruang` FOREIGN KEY (`id_ruang`) REFERENCES `ruang` (`id_ruang`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

