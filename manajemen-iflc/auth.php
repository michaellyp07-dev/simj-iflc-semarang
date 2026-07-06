<?php
/**
 * manajemen-iflc/auth.php
 * Session guard — include di setiap halaman admin yang butuh proteksi.
 * Redirect ke login jika belum login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'manajemen-iflc/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
