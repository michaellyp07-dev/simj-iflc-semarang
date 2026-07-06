-- ============================================================
--  MIGRATION V2: Laporan Pelayanan & Kegiatan
--  Jalankan di phpMyAdmin → database iflc (atau iflc_db)
--  Tanggal: 2026-06-27
-- ============================================================

-- 1. Ubah kolom jenis_ibadah dari ENUM kaku menjadi VARCHAR bebas
--    Ini memungkinkan admin mengetik jenis kegiatan apa saja
--    (mis: Ibadah Padang, Retreat, Doa Semalam, Natal, Paskah, dll.)

ALTER TABLE `jadwal_ibadah`
    MODIFY COLUMN `jenis_ibadah` VARCHAR(100) NOT NULL DEFAULT 'Ibadah Raya';

-- 2. (Opsional) Update view laporan bulanan agar tidak hardcode jenis ibadah
--    Drop & recreate view yang sudah ada
DROP VIEW IF EXISTS `v_laporan_bulanan`;

CREATE VIEW `v_laporan_bulanan` AS
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
