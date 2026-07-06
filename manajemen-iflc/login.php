<?php
/**
 * manajemen-iflc/login.php — Halaman Login Admin
 */

require_once dirname(__DIR__) . '/koneksi.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'manajemen-iflc/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $stmt = db()->prepare('SELECT id_admin, username, password FROM admin WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];

            $redirect = $_GET['redirect'] ?? BASE_URL . 'manajemen-iflc/dashboard.php';
            header('Location: ' . $redirect);
            exit;
        }

        $error = 'Username atau password salah. Silakan coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Admin — SIMJ IFLC</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* ── Subtle background decoration ── */
        body::before {
            content: '';
            position: fixed;
            top: -40%;
            right: -20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(99,102,241,.07) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            left: -15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* ── Card ── */
        .login-card {
            position: relative;
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 1.25rem;
            padding: 2.5rem 2.25rem 2rem;
            box-shadow:
                0 1px 3px rgba(0,0,0,.04),
                0 8px 32px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Header ── */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .subtitle {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: #6366f1;
            margin-bottom: .5rem;
        }
        .login-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.02em;
        }
        .login-header .desc {
            font-size: .82rem;
            color: #94a3b8;
            margin-top: .35rem;
        }
        .header-line {
            width: 36px;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #818cf8);
            border-radius: 99px;
            margin: .85rem auto 0;
        }

        /* ── Form ── */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: .45rem;
            letter-spacing: .01em;
        }
        .form-input {
            width: 100%;
            padding: .72rem 1rem;
            font-size: .88rem;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: .65rem;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }
        .form-input:hover {
            border-color: #cbd5e1;
        }
        .form-input:focus {
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(99,102,241,.1);
        }
        .form-input::placeholder {
            color: #94a3b8;
        }

        /* ── Password wrapper ── */
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .form-input {
            padding-right: 2.8rem;
        }
        .eye-btn {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: .2rem;
            border-radius: .3rem;
            transition: color .15s, background .15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .eye-btn:hover {
            color: #6366f1;
            background: rgba(99,102,241,.06);
        }

        /* ── Button ── */
        .btn-login {
            width: 100%;
            padding: .78rem;
            font-size: .9rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: .65rem;
            cursor: pointer;
            transition: all .2s ease;
            box-shadow: 0 4px 14px rgba(99,102,241,.3);
            letter-spacing: .01em;
            margin-top: .25rem;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(99,102,241,.38);
            filter: brightness(1.05);
        }
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(99,102,241,.25);
        }
        .btn-login:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Error alert ── */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            padding: .7rem 1rem;
            margin-bottom: 1.25rem;
            font-size: .82rem;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: .6rem;
            animation: fadeIn .3s ease both;
        }
        .alert-error svg {
            flex-shrink: 0;
            margin-top: .1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── Footer ── */
        .login-footer {
            text-align: center;
            font-size: .72rem;
            color: #94a3b8;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            font-size: .78rem;
            color: #94a3b8;
            text-decoration: none;
            transition: color .2s;
        }
        .back-link:hover {
            color: #6366f1;
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div style="width:100%; max-width:400px;">
        <!-- Login Card -->
        <div class="login-card">

            <!-- Header -->
            <div class="login-header">
                <p class="subtitle">IFLC Satelit Semarang</p>
                <h1>Admin Login</h1>
                <p class="desc">Panel Manajemen Jemaat</p>
                <div class="header-line"></div>
            </div>

            <!-- Error Alert -->
            <?php if ($error !== ''): ?>
            <div class="alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" novalidate>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-input"
                           placeholder="Masukkan username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username"
                           required />
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Masukkan password"
                               autocomplete="current-password"
                               required />
                        <button type="button" class="eye-btn" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btn-login">
                    Masuk ke Dashboard
                </button>
            </form>

            <div class="login-footer">
                &copy; <?= date('Y') ?> IFLC — Sistem Informasi Manajemen Jemaat
            </div>
        </div>

        <!-- Back to public -->
        <a href="<?= BASE_URL ?>index.php" class="back-link">
            ← Kembali ke Halaman Publik
        </a>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>`;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>`;
            }
        }

        // Prevent double-submit
        document.querySelector('form').addEventListener('submit', function() {
            var btn = document.getElementById('btn-login');
            btn.disabled    = true;
            btn.textContent = 'Memproses…';
        });
    </script>
</body>
</html>
