-- ============================================================
--  MIGRATION: Tambah kolom jumlah_hadir
--  Jalankan file ini di phpMyAdmin atau MySQL CLI
-- ============================================================

USE `iflc`;

ALTER TABLE `jadwal_ibadah`
    ADD COLUMN `jumlah_hadir` INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Total jemaat yang hadir pada ibadah ini'
    AFTER `pembicara_tamu`;
