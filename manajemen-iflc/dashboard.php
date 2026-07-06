<?php
/**
 * manajemen-iflc/dashboard.php — Halaman utama Admin Panel
 */

require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = db();

// ─────────────────────────────────────────────
//  STATS
// ─────────────────────────────────────────────

$totalJemaat = $pdo->query('SELECT COUNT(*) FROM jemaat_umum')->fetchColumn();

// Total jadwal bulan ini
$totalJadwalBulan = $pdo->query("
    SELECT COUNT(*) FROM jadwal_ibadah
    WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())
      AND YEAR(tanggal)  = YEAR(CURRENT_DATE())
")->fetchColumn();

// Jadwal mendatang (hari ini ke atas, status Aktif)
$totalMendatang = $pdo->query("
    SELECT COUNT(*) FROM jadwal_ibadah
    WHERE tanggal >= CURRENT_DATE() AND status = 'Mendatang'
")-> fetchColumn();

// Total divisi
$totalDivisi = $pdo->query('SELECT COUNT(*) FROM divisi')->fetchColumn();

// Total penugasan bulan ini
$totalPenugasan = $pdo->query("
    SELECT COUNT(dj.id_detail)
    FROM detail_jadwal dj
    JOIN jadwal_ibadah ji ON ji.id_jadwal = dj.id_jadwal
    WHERE MONTH(ji.tanggal) = MONTH(CURRENT_DATE())
      AND YEAR(ji.tanggal)  = YEAR(CURRENT_DATE())
")->fetchColumn();

// ─────────────────────────────────────────────
//  5 Jadwal Mendatang
// ─────────────────────────────────────────────
$jadwalMendatang = $pdo->query("
    SELECT ji.id_jadwal, ji.tanggal, ji.waktu, ji.jenis_ibadah, ji.tema, ji.status,
           COUNT(dj.id_detail) AS petugas
    FROM jadwal_ibadah ji
    LEFT JOIN detail_jadwal dj ON dj.id_jadwal = ji.id_jadwal
    WHERE ji.tanggal >= CURRENT_DATE() AND ji.status = 'Mendatang'
    GROUP BY ji.id_jadwal
    ORDER BY ji.tanggal ASC, ji.waktu ASC
    LIMIT 5
")->fetchAll();

// ─────────────────────────────────────────────
//  Kegiatan bulan ini (untuk tabel laporan di dashboard)
// ─────────────────────────────────────────────
$kegiatanBulanIni = $pdo->query("
    SELECT
        ji.id_jadwal,
        ji.tanggal,
        ji.waktu,
        ji.jenis_ibadah,
        ji.tema,
        ji.pembicara_tamu,
        ji.status,
        ji.jumlah_hadir
    FROM jadwal_ibadah ji
    WHERE MONTH(ji.tanggal) = MONTH(CURRENT_DATE())
      AND YEAR(ji.tanggal)  = YEAR(CURRENT_DATE())
    ORDER BY ji.tanggal ASC, ji.waktu ASC
")->fetchAll();

// ─────────────────────────────────────────────
//  Distribusi jadwal 4 minggu terakhir (mini chart data)
// ─────────────────────────────────────────────
$distribusi = $pdo->query("
    SELECT jenis_ibadah, COUNT(*) AS total
    FROM jadwal_ibadah
    WHERE tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY jenis_ibadah
")->fetchAll(PDO::FETCH_KEY_PAIR);

$ibadahRayaCount = $distribusi['Ibadah Raya'] ?? 0;
$komselCount     = $distribusi['Komsel']      ?? 0;
$totalDistribusi = $ibadahRayaCount + $komselCount ?: 1;
$irPct           = round(($ibadahRayaCount / $totalDistribusi) * 100);
$ksPct           = 100 - $irPct;



// ─────────────────────────────────────────────
//  Helper
// ─────────────────────────────────────────────
function fmtTgl(string $date): string
{
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts  = strtotime($date);
    return date('d', $ts) . ' ' . $bln[(int)date('n', $ts)];
}

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════════════════════
     STAT CARDS
════════════════════════════════════════════ -->
<section class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <?php
    $stats = [
        [
            'label'   => 'Total Jemaat',
            'value'   => $totalJemaat,
            'sub'     => 'Terdaftar di sistem',
            'color'   => 'slate',
            'bg'      => 'from-white to-slate-50',
            'border'  => 'border-slate-200',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                                   M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                                   m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        ],
        [
            'label'   => 'Jadwal Bulan Ini',
            'value'   => $totalJadwalBulan,
            'sub'     => date('F Y'),
            'color'   => 'slate',
            'bg'      => 'from-white to-slate-50',
            'border'  => 'border-slate-200',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2
                                   0 00-2 2v12a2 2 0 002 2z"/>',
        ],
        [
            'label'   => 'Jadwal Mendatang',
            'value'   => $totalMendatang,
            'sub'     => 'Status Mendatang',
            'color'   => 'red',
            'bg'      => 'from-red-600 to-red-700',
            'border'  => 'border-red-700',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
    ];
    foreach ($stats as $i => $s):
    ?>
    <div class="rounded-xl p-5 bg-white border border-slate-200 border-l-4 <?= $s['color']==='red' ? 'border-l-red-600' : 'border-l-slate-400' ?> shadow-sm transition-transform hover:-translate-y-1
                animate-fade-in" style="animation-delay:<?= $i * .07 ?>s">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-bold <?= $s['color']==='red' ? 'text-red-600' : 'text-zinc-500' ?> uppercase tracking-wider truncate">
                    <?= $s['label'] ?>
                </p>
                <p class="text-3xl font-black text-zinc-900 mt-1"><?= $s['value'] ?></p>
                <p class="text-xs text-zinc-400 mt-1 truncate"><?= $s['sub'] ?></p>
            </div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                        <?= $s['color']==='red' ? 'bg-red-50 text-red-600' : 'bg-slate-50 text-slate-500' ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?= $s['icon'] ?>
                </svg>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<!-- ═══════════════════════════════════════════
     MAIN GRID: Jadwal + Distribusi + Top Petugas
════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    <!-- Jadwal Mendatang (2/3) -->
    <div class="lg:col-span-2 glass-card rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-black text-zinc-900 tracking-tight"> Jadwal Mendatang</h2>
                <p class="text-xs text-slate-500 mt-0.5">5 jadwal aktif terdekat</p>
            </div>
            <a href="<?= BASE_URL ?>manajemen-iflc/jadwal.php"
               class="text-xs font-bold text-red-600 hover:text-red-800 transition-colors">
                Lihat Semua →
            </a>
        </div>

        <?php if (empty($jadwalMendatang)): ?>
        <div class="p-8 text-center text-slate-500 text-sm">
            Tidak ada jadwal aktif mendatang.
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-50">
            <?php foreach ($jadwalMendatang as $j): ?>
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-red-50 transition-colors">
                <!-- Date box -->
                <div class="flex-shrink-0 w-11 text-center">
                    <div class="text-2xl font-black text-zinc-900 leading-none">
                        <?= date('d', strtotime($j['tanggal'])) ?>
                    </div>
                    <div class="text-[9px] font-semibold text-slate-500 uppercase tracking-widest">
                        <?= date('M', strtotime($j['tanggal'])) ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if ($j['jenis_ibadah'] === 'Ibadah Raya'): ?>
                        <span class="badge badge-purple"> Ibadah Raya</span>
                        <?php else: ?>
                        <span class="badge badge-amber">Komsel</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm font-semibold text-zinc-800 mt-1 truncate">
                        <?= $j['tema'] ? htmlspecialchars($j['tema']) : '—' ?>
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?= substr($j['waktu'], 0, 5) ?> WIB
                    </p>
                </div>

                <!-- Petugas count -->
                <div class="flex-shrink-0 text-right">
                    <span class="text-sm font-black text-red-600"><?= $j['petugas'] ?></span>
                    <p class="text-[10px] text-slate-500">petugas</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right col: Distribusi + Quick Actions -->
    <div class="space-y-4">



        <!-- Quick Actions -->
        <div class="glass-card rounded-2xl p-5">
            <h2 class="text-sm font-black text-zinc-900 tracking-tight mb-4"> Aksi Cepat</h2>
            <div class="space-y-2">
                <a href="<?= BASE_URL ?>manajemen-iflc/jemaat.php?action=add"
                   class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-lg
                          bg-zinc-900 border border-zinc-800 text-zinc-300
                          hover:bg-red-600 hover:text-white hover:border-red-600 transition-all text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Jemaat Baru
                </a>
                <a href="<?= BASE_URL ?>manajemen-iflc/jadwal.php?action=add"
                   class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-lg
                          bg-zinc-900 border border-zinc-800 text-zinc-300
                          hover:bg-zinc-800 hover:text-white hover:border-zinc-700 transition-all text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Buat Jadwal Baru
                </a>
                <a href="<?= BASE_URL ?>manajemen-iflc/laporan.php"
                   class="flex items-center gap-3 w-full text-left px-4 py-3 rounded-lg
                          bg-zinc-900 border border-zinc-800 text-zinc-300
                          hover:bg-zinc-800 hover:text-white hover:border-zinc-700 transition-all text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                                 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Lihat Laporan Bulanan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     LAPORAN KEGIATAN BULAN INI
══════════════════════════════════════════════ -->
<div class="glass-card rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div>
            <h2 class="text-sm font-black text-zinc-900 tracking-tight">Laporan Kegiatan Bulan Ini</h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar ibadah &amp; kehadiran jemaat &mdash; <?= date('F Y') ?></p>
        </div>
        <a href="<?= BASE_URL ?>manajemen-iflc/laporan.php"
           class="text-xs font-bold text-red-600 hover:text-red-800 transition-colors">
            Laporan Lengkap &rarr;
        </a>
    </div>

    <?php if (empty($kegiatanBulanIni)): ?>
    <div class="p-8 text-center text-slate-500 text-sm">
        Belum ada kegiatan untuk bulan ini.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left">Tanggal &amp; Waktu</th>
                    <th class="px-5 py-3 text-left">Jenis Kegiatan</th>
                    <th class="px-5 py-3 text-left">Tema / Pembicara</th>
                    <th class="px-5 py-3 text-center">Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kegiatanBulanIni as $k): ?>
                <tr class="<?= $k['status'] === 'Dibatalkan' ? 'opacity-40' : '' ?>">

                    <!-- Tanggal & Waktu -->
                    <td class="px-5">
                        <?php
                        $bln  = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        $hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                        $ts   = strtotime($k['tanggal']);
                        $tgl  = $hari[date('w',$ts)] . ', ' . date('d',$ts) . ' ' . $bln[(int)date('n',$ts)];
                        ?>
                        <div class="font-bold text-zinc-900 text-sm whitespace-nowrap"><?= $tgl ?></div>
                        <div class="text-xs text-slate-500 mt-0.5"><?= substr($k['waktu'], 0, 5) ?> WIB</div>
                    </td>

                    <!-- Jenis Kegiatan -->
                    <td class="px-5">
                        <span class="badge badge-purple"><?= htmlspecialchars($k['jenis_ibadah']) ?></span>
                    </td>

                    <!-- Tema / Pembicara -->
                    <td class="px-5">
                        <div class="text-sm text-zinc-600 max-w-[220px]">
                            <?= $k['tema'] ? htmlspecialchars($k['tema']) : '<span class="text-slate-600 italic">&mdash;</span>' ?>
                        </div>
                        <?php if ($k['pembicara_tamu']): ?>
                            <div class="text-xs text-amber-600 mt-0.5">
                                <?= htmlspecialchars($k['pembicara_tamu']) ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Kehadiran -->
                    <td class="px-5 text-center">
                        <?php if ($k['jumlah_hadir'] > 0): ?>
                            <span class="badge badge-amber"><?= number_format($k['jumlah_hadir']) ?></span>
                        <?php elseif ($k['status'] === 'Selesai'): ?>
                            <span class="text-xs text-slate-500 italic">Belum diisi</span>
                        <?php else: ?>
                            <span class="text-slate-600 text-xs">&mdash;</span>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>


<?php include __DIR__ . '/includes/footer.php'; ?>
