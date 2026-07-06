<?php
/**
 * manajemen-iflc/logout.php — Hancurkan sesi dan redirect ke login.
 */

require_once dirname(__DIR__) . '/koneksi.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: ' . BASE_URL . 'manajemen-iflc/login.php');
exit;
