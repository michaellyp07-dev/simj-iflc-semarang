<?php
/**
 * koneksi.php
 * Koneksi database untuk Sistem Informasi Manajemen Jemaat IFLC.
 * Menggunakan PDO dengan prepared statements.
 */

// ── Konstanta Aplikasi ──────────────────────────
define('BASE_URL', '/workshop/iflc/');
define('APP_NAME', 'Sistem Informasi Manajemen Jemaat IFLC');
define('APP_SHORT', 'SIMJ IFLC');

// ── Konfigurasi Database ────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'iflc');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

// ── Fungsi Koneksi PDO (Singleton) ─────────────
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHAR
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(503);
            die('Koneksi database gagal. Silakan hubungi administrator.');
        }
    }

    return $pdo;
}

// Alias singkat — panggil db() di file mana saja
function db(): PDO
{
    return getPDO();
}
