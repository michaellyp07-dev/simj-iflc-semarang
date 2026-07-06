-- ============================================================
--  MIGRATION: Tambah Fitur Data Jemaat Umum
--  Jalankan di phpMyAdmin → database iflc_db
-- ============================================================

USE `iflc`;

-- 1. Tabel jemaat umum (semua anggota gereja)
CREATE TABLE IF NOT EXISTS `jemaat_umum` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nama_lengkap`  VARCHAR(150)  NOT NULL,
    `tempat_lahir`  VARCHAR(100)  DEFAULT NULL,
    `tanggal_lahir` DATE          DEFAULT NULL,
    `no_hp`         VARCHAR(20)   DEFAULT NULL,
    `alamat`        TEXT          DEFAULT NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tambah kolom relasi ke tabel jemaat (petugas pelayanan)
ALTER TABLE `jemaat`
    ADD COLUMN `id_jemaat_umum` INT UNSIGNED DEFAULT NULL AFTER `id_jemaat`,
    ADD CONSTRAINT `fk_jemaat_umum`
        FOREIGN KEY (`id_jemaat_umum`) REFERENCES `jemaat_umum` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL;
