-- ============================================================
--  DATABASE: Sistem Informasi Manajemen Jemaat IFLC
--  Engine   : MySQL 5.7+ / MariaDB
--  Encoding : UTF-8 (utf8mb4)
-- ============================================================



-- ------------------------------------------------------------
-- 1. ADMIN
--    Menyimpan akun admin yang bisa login ke dashboard.
-- ------------------------------------------------------------
CREATE TABLE `admin` (
    `id_admin`   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(60)     NOT NULL UNIQUE,
    `password`   VARCHAR(255)    NOT NULL COMMENT 'Bcrypt hash',
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin → username: admin | password: admin123
-- (hash dihasilkan dengan password_hash('admin123', PASSWORD_BCRYPT))
INSERT INTO `admin` (`username`, `password`) VALUES
('admin', '$2y$12$YK3lA3HkGWZTJ8FwJsJqZeX4rSoaXGkPq7Vc0RdIfNwRlJ7F9K8bO');

-- ------------------------------------------------------------
-- 2. DIVISI
--    Kelompok / divisi pelayanan dalam gereja.
-- ------------------------------------------------------------
CREATE TABLE `divisi` (
    `id_divisi`   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nama_divisi` VARCHAR(100)    NOT NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_divisi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `divisi` (`nama_divisi`) VALUES
('Worship'),
('Multimedia'),
('Penyambut Tamu'),
('Doa & Syafaat'),
('Anak & Remaja'),
('Keamanan'),
('Pendeta / Hamba Tuhan');

-- ------------------------------------------------------------
-- 3. JEMAAT
--    Data anggota / petugas jemaat.
-- ------------------------------------------------------------
CREATE TABLE `jemaat` (
    `id_jemaat`    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `id_divisi`    INT UNSIGNED    NOT NULL,
    `nama_lengkap` VARCHAR(150)    NOT NULL,
    `no_telepon`   VARCHAR(20)     DEFAULT NULL,
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_jemaat`),
    INDEX `idx_jemaat_divisi` (`id_divisi`),
    CONSTRAINT `fk_jemaat_divisi`
        FOREIGN KEY (`id_divisi`) REFERENCES `divisi` (`id_divisi`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jemaat` (`id_divisi`, `nama_lengkap`, `no_telepon`) VALUES
(1, 'Samuel Kristian',    '081234567801'),
(1, 'Debora Manullang',   '081234567802'),
(2, 'Yohanes Pratama',    '081234567803'),
(2, 'Ruth Susanti',       '081234567804'),
(3, 'Agus Setiawan',      '081234567805'),
(3, 'Maria Lestari',      '081234567806'),
(4, 'Daniel Wahyu',       '081234567807'),
(5, 'Esther Rahayu',      '081234567808'),
(6, 'Petrus Gunawan',     '081234567809'),
(7, 'Pdt. Hendra Putra',  '081234567810');

-- ------------------------------------------------------------
-- 4. JADWAL_IBADAH
--    Header jadwal ibadah (Ibadah Raya atau Komsel).
-- ------------------------------------------------------------
CREATE TABLE `jadwal_ibadah` (
    `id_jadwal`    INT UNSIGNED                         NOT NULL AUTO_INCREMENT,
    `tanggal`      DATE                                 NOT NULL,
    `waktu`        TIME                                 NOT NULL,
    `jenis_ibadah` ENUM('Ibadah Raya','Komsel')         NOT NULL,
    `tema`         VARCHAR(200)                         DEFAULT NULL COMMENT 'Tema khotbah / komsel',
    `lokasi`       VARCHAR(200)                         DEFAULT NULL,
    `status`       ENUM('Aktif','Selesai','Dibatalkan') NOT NULL DEFAULT 'Aktif',
    `jumlah_hadir` INT UNSIGNED                         NOT NULL DEFAULT 0 COMMENT 'Total jemaat yang hadir',
    `created_at`   TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_jadwal`),
    INDEX `idx_jadwal_tanggal`  (`tanggal`),
    INDEX `idx_jadwal_jenis`    (`jenis_ibadah`),
    INDEX `idx_jadwal_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jadwal_ibadah` (`tanggal`, `waktu`, `jenis_ibadah`, `tema`, `lokasi`, `status`) VALUES
('2026-05-04', '08:00:00', 'Ibadah Raya', 'Kasih yang Tak Berkesudahan', 'Gedung Utama IFLC',       'Aktif'),
('2026-05-04', '10:30:00', 'Ibadah Raya', 'Berjalan Dalam Iman',         'Gedung Utama IFLC',       'Aktif'),
('2026-05-06', '19:00:00', 'Komsel',      'Pemuridan dalam Keluarga',    'Rumah Bpk. Agus',         'Aktif'),
('2026-05-08', '19:00:00', 'Komsel',      'Doa Bersama',                 'Rumah Ibu Maria',         'Aktif'),
('2026-05-11', '08:00:00', 'Ibadah Raya', 'Hidup dalam Roh',             'Gedung Utama IFLC',       'Aktif'),
('2026-04-27', '08:00:00', 'Ibadah Raya', 'Kesetiaan Tuhan',             'Gedung Utama IFLC',       'Selesai');

-- ------------------------------------------------------------
-- 5. DETAIL_JADWAL
--    Penugasan petugas (jemaat) ke sebuah jadwal ibadah.
--    Constraint UNIQUE (id_jadwal, id_jemaat) → satu orang
--    tidak bisa ditugaskan dua kali di jadwal yang sama.
-- ------------------------------------------------------------
CREATE TABLE `detail_jadwal` (
    `id_detail`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_jadwal`  INT UNSIGNED NOT NULL,
    `id_jemaat`  INT UNSIGNED NOT NULL,
    `peran`      VARCHAR(100) DEFAULT NULL COMMENT 'Peran spesifik, mis: Singer, Operator Slide',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_detail`),
    UNIQUE  KEY `uq_detail_jadwal_jemaat` (`id_jadwal`, `id_jemaat`),
    INDEX   `idx_detail_jadwal`  (`id_jadwal`),
    INDEX   `idx_detail_jemaat`  (`id_jemaat`),
    CONSTRAINT `fk_detail_jadwal`
        FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_ibadah` (`id_jadwal`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_detail_jemaat`
        FOREIGN KEY (`id_jemaat`) REFERENCES `jemaat` (`id_jemaat`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `detail_jadwal` (`id_jadwal`, `id_jemaat`, `peran`) VALUES
(1, 1,  'Singer Lead'),
(1, 2,  'Singer Backing'),
(1, 3,  'Operator Slide'),
(1, 5,  'Penyambut Tamu'),
(1, 9,  'Keamanan'),
(1, 10, 'Pengkhotbah'),
(2, 1,  'Singer Lead'),
(2, 4,  'Operator Slide'),
(2, 6,  'Penyambut Tamu'),
(2, 10, 'Pengkhotbah'),
(3, 7,  'Pemimpin Komsel'),
(3, 8,  'Notulen'),
(4, 7,  'Pemimpin Doa'),
(6, 1,  'Singer Lead');

-- ============================================================
--  VIEW HELPER: v_laporan_bulanan
--  Menampilkan frekuensi pelayanan per jemaat bulan berjalan.
--  Digunakan oleh halaman Laporan di admin.
-- ============================================================
CREATE OR REPLACE VIEW `v_laporan_bulanan` AS
SELECT
    j.id_jemaat,
    j.nama_lengkap,
    d.nama_divisi,
    COUNT(dj.id_detail)              AS total_pelayanan,
    GROUP_CONCAT(
        CONCAT(ji.tanggal, ' (', ji.jenis_ibadah, ')')
        ORDER BY ji.tanggal
        SEPARATOR ' | '
    )                                AS riwayat_jadwal
FROM `jemaat`        j
JOIN `divisi`        d  ON d.id_divisi  = j.id_divisi
LEFT JOIN `detail_jadwal`  dj ON dj.id_jemaat = j.id_jemaat
LEFT JOIN `jadwal_ibadah`  ji ON ji.id_jadwal  = dj.id_jadwal
                              AND MONTH(ji.tanggal)  = MONTH(CURRENT_DATE())
                              AND YEAR(ji.tanggal)   = YEAR(CURRENT_DATE())
GROUP BY j.id_jemaat, j.nama_lengkap, d.nama_divisi
ORDER BY total_pelayanan DESC, j.nama_lengkap;
