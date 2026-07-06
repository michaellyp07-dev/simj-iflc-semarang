<?php
/**
 * index.php — Halaman Publik Jemaat (Tanpa Login)
 * Sistem Informasi Manajemen Jemaat IFLC
 */
require_once __DIR__ . '/koneksi.php';

// ── FILTER ────────────────────────────────────────
$allowed = ['semua', 'Ibadah Raya', 'Komsel'];
$filter = $_GET['filter'] ?? 'semua';
if (!in_array($filter, $allowed, true))
    $filter = 'semua';

// ── QUERY JADWAL ──────────────────────────────────
$pdo = db();
$params = [];
$extra = '';
if ($filter !== 'semua') {
    $extra = 'AND ji.jenis_ibadah = ?';
    $params[] = $filter;
}

// FIX: Ubah filter tanggal jadi strictly nampilin status 'Mendatang' aja
$stmt = $pdo->prepare("
    SELECT ji.id_jadwal, ji.tanggal, ji.waktu, ji.jenis_ibadah,
           ji.tema, ji.lokasi, ji.status, ji.pembicara_tamu,
           COUNT(dj.id_detail) AS jml_petugas
    FROM jadwal_ibadah ji
    LEFT JOIN detail_jadwal dj ON dj.id_jadwal = ji.id_jadwal
    WHERE ji.status = 'Mendatang'
      $extra
    GROUP BY ji.id_jadwal
    ORDER BY ji.tanggal ASC, ji.waktu ASC
");
$stmt->execute($params);
$jadwals = $stmt->fetchAll();

// ── PETUGAS PER JADWAL ────────────────────────────
$petugasMap = [];
if (!empty($jadwals)) {
    $ids = array_column($jadwals, 'id_jadwal');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sp = $pdo->prepare("
        SELECT dj.id_jadwal, j.nama_lengkap, d.nama_divisi, dj.peran
        FROM detail_jadwal dj
        JOIN jemaat j ON j.id_jemaat = dj.id_jemaat
        JOIN divisi d ON d.id_divisi  = j.id_divisi
        WHERE dj.id_jadwal IN ($in)
        ORDER BY d.nama_divisi, j.nama_lengkap
    ");
    $sp->execute($ids);
    foreach ($sp->fetchAll() as $row) {
        $petugasMap[$row['id_jadwal']][] = $row;
    }
}

// ── HELPERS ───────────────────────────────────────
function tglIndo(string $d): string
{
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bln = [
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $ts = strtotime($d);
    return $hari[date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $bln[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
function wib(string $t): string
{
    return substr($t, 0, 5) . ' WIB';
}
function isToday(string $d): bool
{
    return $d === date('Y-m-d');
}
function isFuture(string $d): bool
{
    return strtotime($d) > strtotime('today');
}

// Counts for stats
$totalIR = count(array_filter($jadwals, fn($j) => $j['jenis_ibadah'] === 'Ibadah Raya'));
$totalKS = count(array_filter($jadwals, fn($j) => $j['jenis_ibadah'] === 'Komsel'));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jadwal Ibadah IFLC — International Father Love Church</title>
    <meta name="description"
        content="Jadwal Ibadah Raya dan Komsel IFLC terkini. Lihat daftar petugas dan informasi pelayanan jemaat International Father Love Church." />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'fade-up': 'fadeUp .5s ease both',
                        'fade-in': 'fadeIn .4s ease both',
                        'pulse-dot': 'pulse 2s cubic-bezier(.4,0,.6,1) infinite',
                    },
                    keyframes: {
                        fadeUp: { from: { opacity: 0, transform: 'translateY(20px)' }, to: { opacity: 1, transform: 'translateY(0)' } },
                        fadeIn: { from: { opacity: 0 }, to: { opacity: 1 } },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet" />

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        /* Hero */
        .hero {
            background: linear-gradient(160deg, #fff1f2 0%, #f8fafc 50%, #fff7ed 100%);
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid #e2e8f0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .12;
            pointer-events: none;
        }

        /* Card */
        .schedule-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .schedule-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(15, 23, 42, .08);
            border-color: #cbd5e1;
        }

        .card-accent-ir {
            background: linear-gradient(90deg, #dc2626, #f43f5e);
        }

        .card-accent-ks {
            background: linear-gradient(90deg, #f97316, #fbbf24);
        }

        /* Filter btn */
        .filter-btn {
            padding: .55rem 1.25rem;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: #ffffff;
            text-decoration: none;
            transition: all .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }

        .filter-btn:hover {
            border-color: #dc2626;
            color: #dc2626;
            box-shadow: 0 2px 8px rgba(220,38,38,.1);
        }

        .filter-btn.active {
            background: #dc2626;
            color: #fff;
            border-color: #dc2626;
            box-shadow: 0 4px 14px rgba(220, 38, 38, .3);
        }

        .filter-btn.active-ks {
            background: #f97316;
            color: #fff;
            border-color: #f97316;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .6rem;
            border-radius: 999px;
            letter-spacing: .04em;
        }

        .badge-ir {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-ks {
            background: #ffedd5;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .badge-today {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-soon {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-done {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* Pembicara highlight */
        .pembicara-card {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: .75rem;
            padding: .65rem 1rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        /* Petugas chip */
        .petugas-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            padding: .3rem .6rem;
            font-size: .72rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        /* Stagger */
        .s1 {
            animation-delay: .05s
        }

        .s2 {
            animation-delay: .1s
        }

        .s3 {
            animation-delay: .15s
        }

        .s4 {
            animation-delay: .2s
        }

        .s5 {
            animation-delay: .25s
        }

        .s6 {
            animation-delay: .3s
        }
    </style>
</head>

<body class="min-h-screen">

    <header class="hero py-14 sm:py-20 px-4 text-center">
        <div class="orb w-96 h-96 bg-red-400 -top-32 -left-20"></div>
        <div class="orb w-72 h-72 bg-orange-300 top-0 right-0"></div>
        <div class="orb w-56 h-56 bg-rose-300 bottom-0 left-1/3"></div>

        <div class="relative z-10 max-w-2xl mx-auto animate-fade-in">


            <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-3">
                Jadwal Ibadah<br><span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-rose-600">IFLC</span>
            </h1>
            <p class="text-slate-500 text-base sm:text-lg max-w-md mx-auto mb-6">
                International Father Love Church
            </p>

            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-slate-200 text-xs text-slate-500 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative h-2 w-2 rounded-full bg-green-500"></span>
                </span>
                Informasi diperbarui real-time
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-10">

        <nav aria-label="Filter jadwal" class="flex flex-wrap justify-center gap-3 mb-8 animate-fade-in">
            <a href="index.php" id="filter-semua" class="filter-btn <?= $filter === 'semua' ? 'active' : '' ?>">
                Semua</a>
            <a href="index.php?filter=Ibadah+Raya" id="filter-ibadah-raya"
                class="filter-btn <?= $filter === 'Ibadah Raya' ? 'active' : '' ?>"> Ibadah Raya</a>
            <a href="index.php?filter=Komsel" id="filter-komsel"
                class="filter-btn <?= $filter === 'Komsel' ? 'active active-ks' : '' ?>"> Komsel</a>
        </nav>

        <div class="grid grid-cols-3 gap-3 mb-8 animate-fade-in">
            <?php foreach ([
                ['val' => count($jadwals), 'label' => 'Total Jadwal', 'icon' => ''],
                ['val' => $totalIR, 'label' => 'Ibadah Raya', 'icon' => ''],
                ['val' => $totalKS, 'label' => 'Komsel', 'icon' => ''],
            ] as $s): ?>
                <div class="schedule-card text-center py-4 px-2">
                    <div class="text-xl mb-1"><?= $s['icon'] ?></div>
                    <div class="text-2xl font-extrabold text-slate-900"><?= $s['val'] ?></div>
                    <div class="text-[10px] text-slate-400 mt-0.5"><?= $s['label'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($jadwals)): ?>
            <div class="schedule-card p-14 text-center animate-fade-up">

                <h2 class="text-xl font-bold text-slate-900 mb-2">Belum Ada Jadwal</h2>
                <p class="text-slate-400 text-sm">
                    <?= $filter !== 'semua'
                        ? 'Tidak ada jadwal <strong class="text-red-400">' . $filter . '</strong> mendatang yang tersedia.'
                        : 'Jadwal ibadah mendatang belum ditambahkan.' ?>
                </p>
            </div>

        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($jadwals as $idx => $j):
                    $delay = 's' . min($idx + 1, 6);
                    $petugas = $petugasMap[$j['id_jadwal']] ?? [];
                    $isIR = $j['jenis_ibadah'] === 'Ibadah Raya';
                    $today = isToday($j['tanggal']);
                    $future = isFuture($j['tanggal']);
                    ?>
                    <article class="schedule-card animate-fade-up <?= $delay ?>" id="card-jadwal-<?= $j['id_jadwal'] ?>">

                        <div class="h-1 <?= $isIR ? 'card-accent-ir' : 'card-accent-ks' ?>"></div>

                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="badge <?= $isIR ? 'badge-ir' : 'badge-ks' ?>">
                                        <?= $isIR ? 'Ibadah Raya' : 'Komsel' ?>
                                    </span>
                                    <?php if ($today): ?>
                                        <span class="badge badge-today">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse-dot"></span>
                                            Hari Ini
                                        </span>
                                    <?php elseif ($future): ?>
                                        <span class="badge badge-soon">Mendatang</span>
                                    <?php else: ?>
                                        <span class="badge badge-done">Selesai</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($j['tema']): ?>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 leading-snug mb-3">
                                <?= htmlspecialchars($j['tema']) ?>
                            </h2>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-xs text-slate-500 mb-4">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2" />
                                        <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <?= htmlspecialchars(tglIndo($j['tanggal'])) ?>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <circle cx="12" cy="12" r="9" stroke-width="2" />
                                        <path d="M12 7v5l3.5 2" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                    <?= htmlspecialchars(wib($j['waktu'])) ?>
                                </span>
                                <?php if ($j['lokasi']): ?>
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"
                                                stroke-width="2" />
                                            <circle cx="12" cy="9" r="2.5" stroke-width="2" />
                                        </svg>
                                        <?= htmlspecialchars($j['lokasi']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($petugas)): ?>
                                <button class="toggle-petugas w-full flex items-center justify-between
                           text-xs font-semibold text-slate-400 hover:text-red-600
                           transition-colors py-2 border-t border-slate-100" data-target="p-<?= $j['id_jadwal'] ?>"
                                    aria-expanded="false">
                                    <span>Lihat Petugas (<?= count($petugas) ?>)</span>
                                    <svg class="chevron w-4 h-4 transition-transform flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div id="p-<?= $j['id_jadwal'] ?>" class="hidden mt-3">
                                    <?php
                                        $pembicara = array_filter($petugas, fn($p) => $p['nama_divisi'] === 'Pembicara / Pendeta / Pastor');
                                        $petugas_lain = array_filter($petugas, fn($p) => $p['nama_divisi'] !== 'Pembicara / Pendeta / Pastor');
                                        $hasPembicara = !empty($pembicara) || !empty($j['pembicara_tamu']);
                                    ?>

                                    <?php if ($hasPembicara): ?>
                                        <div class="mb-3">
                                            <div class="text-[10px] font-bold text-amber-600 uppercase tracking-widest mb-2">Pembicara</div>
                                            <div class="flex flex-wrap gap-2">
                                                <?php if (!empty($j['pembicara_tamu'])): ?>
                                                <div class="pembicara-card">
                                                    <div>
                                                        <div class="text-slate-900 font-bold text-sm leading-none">
                                                            <?= htmlspecialchars($j['pembicara_tamu']) ?>
                                                        </div>
                                                        <div class="text-amber-600 text-[10px] mt-0.5">
                                                            Pembicara Tamu
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php foreach ($pembicara as $p): ?>
                                                <div class="pembicara-card">
                                                    <div>
                                                        <div class="text-slate-900 font-bold text-sm leading-none">
                                                            <?= htmlspecialchars($p['nama_lengkap']) ?>
                                                        </div>
                                                        <div class="text-amber-600 text-[10px] mt-0.5">
                                                            <?= htmlspecialchars($p['peran'] ?: $p['nama_divisi']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($petugas_lain)): ?>
                                        <?php if ($hasPembicara): ?>
                                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Petugas</div>
                                        <?php endif; ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($petugas_lain as $p): ?>
                                                <div class="petugas-chip">
                                                    <div>
                                                        <div class="text-slate-800 font-semibold leading-none">
                                                            <?= htmlspecialchars($p['nama_lengkap']) ?>
                                                        </div>
                                                        <div class="text-slate-400 text-[10px] mt-0.5">
                                                            <?= htmlspecialchars($p['peran'] ?: $p['nama_divisi']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="border-t border-slate-100 pt-3 mt-1">
                                    <p class="text-xs text-slate-400 italic">Petugas belum ditetapkan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="mt-16 border-t border-slate-200 py-8 px-4 text-center bg-white">
        <p class="text-sm font-semibold text-slate-600 mb-1">IFLC — International Father Love Church</p>
        <p class="text-xs text-slate-400">Sistem Informasi Manajemen Jemaat &copy; <?= date('Y') ?></p>
    </footer>

    <script>
        document.querySelectorAll('.toggle-petugas').forEach(btn => {
            btn.addEventListener('click', () => {
                const panel = document.getElementById(btn.dataset.target);
                const chevron = btn.querySelector('.chevron');
                const open = !panel.classList.contains('hidden');
                panel.classList.toggle('hidden', open);
                chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
                btn.setAttribute('aria-expanded', String(!open));
            });
        });

        // Auto-open today's card
        document.querySelectorAll('.badge-today').forEach(badge => {
            const card = badge.closest('article');
            if (card) {
                const btn = card.querySelector('.toggle-petugas');
                if (btn) btn.click();
            }
        });
    </script>
</body>

</html>