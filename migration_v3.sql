-- ============================================================
--  MIGRATION V3: Master Data Jenis Kegiatan Dinamis
--  Jalankan di phpMyAdmin → database iflc
--  Tanggal: 2026-06-27
-- ============================================================

USE `iflc`;

-- Tabel master jenis kegiatan (untuk jenis DINAMIS/custom)
-- Jenis TETAP (Ibadah Raya, Komsel, Ibadah Natal, Ibadah Paskah, KKR)
-- sudah hardcode di aplikasi, tidak perlu dimasukkan ke sini.

CREATE TABLE IF NOT EXISTS `jenis_kegiatan` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nama_jenis`  VARCHAR(100)  NOT NULL,
    `deskripsi`   TEXT          DEFAULT NULL COMMENT 'Keterangan/memo opsional untuk admin',
    `is_aktif`    TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '1=aktif muncul di dropdown, 0=nonaktif',
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nama_jenis` (`nama_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh data awal (kegiatan dinamis yang sering ada)
INSERT INTO `jenis_kegiatan` (`nama_jenis`, `deskripsi`, `is_aktif`) VALUES
('Ibadah Padang',       'Ibadah di luar gedung / alam terbuka',            1),
('Retreat',             'Kegiatan pendalaman rohani menginap',              1),
('Doa Semalam Suntuk',  'Ibadah doa bersama sepanjang malam',              1),
('Ibadah Pemuda',       'Ibadah khusus untuk jemaat muda',                 1),
('Ibadah Anak',         'Ibadah khusus untuk anak-anak',                   1);
