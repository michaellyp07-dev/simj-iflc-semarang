<?php
/**
 * manajemen-iflc/includes/header.php
 * Shared <head> + opens <body> + top bar for all admin pages.
 *
 * Expects these variables to be set before including:
 *   $pageTitle  — string, shown in <title> and top bar
 *   $activePage — string, matches sidebar menu keys
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$adminUser = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — SIMJ IFLC</title>
    <meta name="robots" content="noindex, nofollow" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:  { DEFAULT:'#dc2626', light:'#ef4444', dark:'#b91c1c' },
                        surface:  '#f8fafc',
                        card:     '#ffffff',
                        sidebar:  '#09090b',
                        muted:    '#f1f5f9',
                    },
                    fontFamily: { sans: ['Inter','sans-serif'] },
                    animation: {
                        'fade-in':  'fadeIn .35s ease both',
                        'slide-in': 'slideIn .4s ease both',
                    },
                    keyframes: {
                        fadeIn:  { from:{opacity:0}, to:{opacity:1} },
                        slideIn: { from:{opacity:0,transform:'translateX(-16px)'}, to:{opacity:1,transform:'translateX(0)'} },
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body { font-family:'Inter',sans-serif; background:#f8fafc; color:#09090b; }

        /* Scrollbar */
        ::-webkit-scrollbar       { width:5px; height:5px; }
        ::-webkit-scrollbar-track { background:#f1f5f9; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:3px; }
        ::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

        /* Glass card — putih bersih, shadow tajam */
        .glass-card {
            background:#ffffff;
            border:1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }
        .glass-card-hover { transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease; }
        .glass-card-hover:hover {
            transform:translateY(-3px);
            box-shadow:0 10px 25px rgba(0,0,0,.1);
            border-color:#cbd5e1;
        }

        /* Sidebar — hitam pekat */
        #sidebar { width:260px; min-width:260px; }
        @media(max-width:768px){
            #sidebar { position:fixed; left:-260px; z-index:50; height:100%; transition:left .3s ease; }
            #sidebar.open { left:0; }
            #sidebar-overlay { display:none; }
            #sidebar.open ~ #sidebar-overlay { display:block; }
        }

        /* Nav item — di atas sidebar hitam */
        .nav-item {
            display:flex; align-items:center; gap:.75rem;
            padding:.625rem 1rem; border-radius:.75rem;
            font-size:.875rem; font-weight:500; color:#71717a;
            transition:all .18s ease; text-decoration:none;
            border:1px solid transparent;
        }
        .nav-item:hover { background:#18181b; color:#e4e4e7; }
        .nav-item.active {
            background:#dc2626;
            color:#ffffff;
            border-color:#b91c1c;
            box-shadow: 0 4px 12px rgba(220,38,38,.35);
        }
        .nav-item .nav-icon { width:1.1rem; height:1.1rem; flex-shrink:0; }

        /* Top bar — putih bersih, garis bawah tipis */
        #topbar { height:64px; }

        /* Main content offset */
        #main-content { margin-left:260px; }
        @media(max-width:768px){ #main-content { margin-left:0; } }

        /* Alert */
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
        .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
        .alert-warning { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }

        /* Table */
        .data-table thead th {
            background:#f8fafc; font-size:.75rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.06em; color:#374151;
            border-bottom:2px solid #09090b;
        }
        .data-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .15s; }
        .data-table tbody tr:hover { background:#fef2f2; }
        .data-table tbody td { padding:.75rem 1rem; font-size:.875rem; color:#111827; }

        /* Badge */
        .badge { display:inline-flex;align-items:center;gap:.25rem;
            font-size:.7rem;font-weight:700;padding:.2rem .6rem;
            border-radius:4px;letter-spacing:.04em; }
        .badge-purple { background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
        .badge-amber  { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .badge-green  { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .badge-gray   { background:#f9fafb; color:#6b7280; border:1px solid #e5e7eb; }
        .badge-red    { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        /* Tombol utama — merah bold */
        .btn-primary {
            background:#dc2626; color:#fff;
            border:none; padding:.55rem 1.25rem; border-radius:.5rem; font-size:.875rem;
            font-weight:700; cursor:pointer; transition:all .18s ease;
            box-shadow:0 4px 12px rgba(220,38,38,.3);
            letter-spacing:.01em;
        }
        .btn-primary:hover { background:#b91c1c; transform:translateY(-1px); box-shadow:0 6px 16px rgba(220,38,38,.4); }
        .btn-danger {
            background:#fff; color:#dc2626;
            border:1.5px solid #dc2626; padding:.45rem .85rem;
            border-radius:.5rem; font-size:.8rem; font-weight:600;
            cursor:pointer; transition:all .18s ease;
        }
        .btn-danger:hover  { background:#fef2f2; }
        .btn-edit {
            background:#fff; color:#374151;
            border:1.5px solid #d1d5db; padding:.45rem .85rem;
            border-radius:.5rem; font-size:.8rem; font-weight:600;
            cursor:pointer; transition:all .18s ease; text-decoration:none;
        }
        .btn-edit:hover { background:#f9fafb; border-color:#9ca3af; }

        /* Form input */
        .form-input, .form-select {
            width:100%; background:#fff; border:1.5px solid #d1d5db; color:#111827;
            border-radius:.5rem; padding:.6rem .9rem; font-size:.875rem;
            transition:border-color .18s,box-shadow .18s; font-family:'Inter',sans-serif;
        }
        .form-input:focus, .form-select:focus {
            outline:none; border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,.12);
        }
        .form-label { display:block; font-size:.8rem; font-weight:700;
            color:#374151; margin-bottom:.4rem; letter-spacing:.02em; text-transform:uppercase; }
    </style>
</head>
<body class="h-full flex overflow-hidden">

<!-- ═══ SIDEBAR OVERLAY (mobile) ═══ -->
<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden"
     onclick="closeSidebar()"></div>

<!-- ═══════════════════════════════════════
     SIDEBAR
════════════════════════════════════════ -->
<aside id="sidebar"
       class="h-full flex flex-col bg-black border-r border-zinc-800 overflow-y-auto">



    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1">
        <p class="px-3 text-[10px] font-semibold text-zinc-600 uppercase tracking-widest mb-2">
            Menu Utama
        </p>

        <?php
        $menus = [
            'dashboard' => [
                'href'  => BASE_URL . 'manajemen-iflc/dashboard.php',
                'label' => 'Dashboard',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            ],
            'jemaat_umum' => [
                'href'  => BASE_URL . 'manajemen-iflc/jemaat_umum.php',
                'label' => 'Data Jemaat Umum',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            ],
            'jemaat' => [
                'href'  => BASE_URL . 'manajemen-iflc/jemaat.php',
                'label' => 'Petugas Pelayanan',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
            ],
            'jadwal' => [
                'href'  => BASE_URL . 'manajemen-iflc/jadwal.php',
                'label' => 'Jadwal Pelayanan',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            ],

            'laporan' => [
                'href'  => BASE_URL . 'manajemen-iflc/laporan.php',
                'label' => 'Laporan Kegiatan',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            ],
        ];

        $active = $activePage ?? 'dashboard';
        foreach ($menus as $key => $menu):
            $isActive = ($active === $key) ? 'active' : '';
        ?>
        <a href="<?= $menu['href'] ?>" class="nav-item <?= $isActive ?>">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <?= $menu['icon'] ?>
            </svg>
            <?= $menu['label'] ?>
            <?php if ($isActive): ?>
            <span class="ml-auto w-2 h-2 rounded-full bg-dc2626" style="background:#dc2626"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <div class="border-t border-zinc-800 pt-3 mt-3">
            <p class="px-3 text-[10px] font-semibold text-zinc-600 uppercase tracking-widest mb-2">
                Lainnya
            </p>
            <a href="<?= BASE_URL ?>index.php" target="_blank" class="nav-item">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Halaman Publik
            </a>
            <a href="<?= BASE_URL ?>manajemen-iflc/logout.php" class="nav-item text-red-400 hover:text-red-300 hover:bg-red-500/10">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </nav>

    <!-- Admin info -->
    <div class="px-4 py-3 border-t border-zinc-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                 style="background:#dc2626">
                <?= mb_strtoupper(mb_substr($adminUser, 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-zinc-200 truncate"><?= htmlspecialchars($adminUser) ?></div>
                <div class="text-[10px] text-zinc-500">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- ═══════════════════════════════════════
     RIGHT SIDE WRAPPER
════════════════════════════════════════ -->
<div id="main-content" class="flex-1 flex flex-col min-w-0 overflow-hidden">

    <!-- TOP BAR -->
    <header id="topbar"
            class="flex-shrink-0 flex items-center justify-between px-5
                   border-b-2 border-zinc-900 bg-white">
        <div class="flex items-center gap-3">
            <!-- Hamburger (mobile) -->
            <button id="hamburger" onclick="openSidebar()"
                    class="md:hidden p-2 rounded-lg text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="text-base font-black text-zinc-900 tracking-tight"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                <p class="text-[11px] text-slate-500 hidden sm:block">
                    <?= date('l, d F Y') ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Notification placeholder -->
            <div class="w-8 h-8 rounded-lg flex items-center justify-center
                        bg-zinc-100 text-zinc-500 cursor-pointer hover:bg-zinc-200 hover:text-zinc-900 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <a href="<?= BASE_URL ?>manajemen-iflc/logout.php"
               class="hidden sm:flex items-center gap-1.5 text-xs font-medium text-slate-400
                      hover:text-red-400 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </header>

    <!-- PAGE CONTENT starts here (closed in footer.php) -->
    <main class="flex-1 overflow-y-auto p-5 sm:p-6 animate-fade-in">
