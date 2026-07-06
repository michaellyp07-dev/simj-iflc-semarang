<?php
/**
 * admin/laporan.php — Laporan Pelayanan & Kegiatan
 * Sistem Informasi Manajemen Jemaat IFLC
 */
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Laporan Pelayanan & Kegiatan';
$activePage = 'laporan';
$pdo = db();

// ── FILTER ─────────────────────────────────────────────────
$bulan = (int) ($_GET['bulan'] ?? date('n'));
$tahun = (int) ($_GET['tahun'] ?? date('Y'));
$bulan = max(1, min(12, $bulan));
$tahun = max(2020, min(2099, $tahun));
$filterJenis = trim($_GET['jenis'] ?? '');

$namaBulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];



// ── QUERY: Daftar jenis kegiatan unik (untuk filter dropdown) ──
$jenisUnikStmt = $pdo->prepare("
    SELECT DISTINCT jenis_ibadah
    FROM jadwal_ibadah
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?
    ORDER BY jenis_ibadah
");
$jenisUnikStmt->execute([$bulan, $tahun]);
$jenisUnik = $jenisUnikStmt->fetchAll(PDO::FETCH_COLUMN);

// ── QUERY: Daftar kegiatan bulan ini ────────────────────────
$params = [$bulan, $tahun];
$whereJenis = '';
if ($filterJenis !== '') {
    $whereJenis = 'AND ji.jenis_ibadah = ?';
    $params[] = $filterJenis;
}

$stmt = $pdo->prepare("
    SELECT
        ji.id_jadwal,
        ji.tanggal,
        ji.waktu,
        ji.jenis_ibadah,
        ji.tema,
        ji.lokasi,
        ji.status,
        ji.pembicara_tamu,
        ji.jumlah_hadir,
        COUNT(dj.id_detail) AS jml_petugas
    FROM jadwal_ibadah ji
    LEFT JOIN detail_jadwal dj ON dj.id_jadwal = ji.id_jadwal
    WHERE MONTH(ji.tanggal) = ? AND YEAR(ji.tanggal) = ?
    $whereJenis
    GROUP BY ji.id_jadwal
    ORDER BY ji.tanggal ASC, ji.waktu ASC
");
$stmt->execute($params);
$kegiatan = $stmt->fetchAll();

// ── QUERY: Petugas per kegiatan ─────────────────────────────
$petugasMap = [];
if (!empty($kegiatan)) {
    $ids = array_column($kegiatan, 'id_jadwal');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sp  = $pdo->prepare("
        SELECT dj.id_jadwal, j.nama_lengkap, dj.peran, d.nama_divisi
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

// ── SUMMARY STATS ────────────────────────────────────────────
$totalKegiatan = count($kegiatan);
$totalSelesai  = count(array_filter($kegiatan, fn($k) => $k['status'] === 'Selesai'));
$totalAktif    = count(array_filter($kegiatan, fn($k) => $k['status'] === 'Mendatang'));
$totalDibatal  = count(array_filter($kegiatan, fn($k) => $k['status'] === 'Dibatalkan'));
$totalHadir    = array_sum(array_column($kegiatan, 'jumlah_hadir'));

// ── HELPERS ────────────────────────────────────────────────
function tglIndo(string $d): string {
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts  = strtotime($d);
    $hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    return $hari[date('w',$ts)] . ', ' . date('d',$ts) . ' ' . $bln[(int)date('n',$ts)] . ' ' . date('Y',$ts);
}



include __DIR__ . '/includes/header.php';
?>

<style>
    /* ── PRINT STYLES ─────────────────────────────────── */
    @media print {
        html, body { height: auto !important; }
        #sidebar, #topbar, .no-print { display: none !important; }
        #main-content { margin-left: 0 !important; }
        body, .glass-card, .overflow-hidden, .overflow-x-auto {
            background: #fff !important;
            color: #000 !important;
            border: none !important;
            box-shadow: none !important;
            overflow: visible !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
        }
        tfoot { display: table-row-group !important; }
        .print-header { display: block !important; }
        .data-table thead th {
            background: #f3f4f6 !important;
            color: #111 !important;
        }
        .data-table tbody tr { 
            border-bottom: 1px solid #e5e7eb !important; 
            page-break-inside: avoid !important; 
            break-inside: avoid !important; 
        }
        .data-table tbody td { color: #111 !important; }
        .badge { border: 1px solid #ccc !important; color: #333 !important; background: #f9f9f9 !important; }
        .summary-cards { display: none !important; }
    }

    .print-header { display: none; }

    /* Petugas pill dalam tabel */
    .petugas-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(99,102,241,0.12);
        border: 1px solid rgba(99,102,241,0.25);
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 11px;
        color: #a5b4fc;
        margin: 2px;
        white-space: nowrap;
    }
    .petugas-pill .peran-tag {
        color: #6b7280;
        font-size: 10px;
    }

    /* Status badge colors */
    .status-aktif     { background: rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3); }
    .status-selesai   { background: rgba(100,116,139,0.15); color:#94a3b8; border:1px solid rgba(100,116,139,0.3); }
    .status-dibatalkan{ background: rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
</style>

<!-- ═══ PRINT HEADER ═══ -->
<div class="print-header text-center mb-6">
    <h1 class="text-xl font-bold">Laporan Pelayanan &amp; Kegiatan</h1>
    <p class="text-sm">Sistem Informasi Manajemen Jemaat IFLC</p>
    <p class="text-sm">Periode: <?= $namaBulan[$bulan] . ' ' . $tahun ?></p>
    <p class="text-xs text-gray-500">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
</div>

<!-- ═══ PAGE HEADER ═══ -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-print">
    <div>
        <h2 class="text-lg font-black text-zinc-900 tracking-tight">Laporan Pelayanan &amp; Kegiatan</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Rekap seluruh aktivitas pelayanan — <?= $namaBulan[$bulan] . ' ' . $tahun ?>
        </p>
    </div>
    <!-- Tombol Export -->
    <div class="flex items-center gap-2">

        <button id="btn-print" onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm
                       btn-primary no-print">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            PDF / Cetak
        </button>
    </div>
</div>

<!-- ═══ FILTER FORM ═══ -->
<div class="glass-card rounded-xl p-4 mb-5 no-print">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select w-40 !py-2">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>>
                        <?= $namaBulan[$m] ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select w-28 !py-2">
                <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php if (!empty($jenisUnik)): ?>
        <div>
            <label class="form-label">Jenis Kegiatan</label>
            <select name="jenis" class="form-select !py-2">
                <option value="">— Semua Jenis —</option>
                <?php foreach ($jenisUnik as $jn): ?>
                    <option value="<?= htmlspecialchars($jn) ?>" <?= $filterJenis === $jn ? 'selected' : '' ?>>
                        <?= htmlspecialchars($jn) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary !py-2 !px-5">Tampilkan</button>
        <?php if ($filterJenis !== ''): ?>
            <a href="laporan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>"
               class="text-xs text-slate-500 hover:text-zinc-900 transition-colors self-center">✕ Reset Filter</a>
        <?php endif; ?>
    </form>
</div>

<!-- ═══ SUMMARY CARDS ═══ -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6 summary-cards">
    <?php
    $cards = [
        ['label'=>'Total Kegiatan','value'=>$totalKegiatan,'sub'=>'Bulan ini','c'=>'indigo'],
        ['label'=>'Selesai','value'=>$totalSelesai,'sub'=>'Kegiatan selesai','c'=>'emerald'],
        ['label'=>'Akan Datang','value'=>$totalAktif,'sub'=>'Masih mendatang / terjadwal','c'=>'violet'],
    ];
    foreach ($cards as $c): ?>
        <div class="rounded-xl p-4 bg-white border border-slate-200 border-l-4 border-l-red-600 shadow-sm transition-transform hover:-translate-y-1">
            <div class="text-3xl font-black text-zinc-900"><?= $c['value'] ?></div>
            <div class="text-xs font-bold text-zinc-500 mt-1 tracking-wide uppercase"><?= $c['label'] ?></div>
            <div class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars($c['sub']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ═══ TABEL LAPORAN KEGIATAN ═══ -->
<div class="glass-card rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-700/60 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-black text-zinc-900 tracking-tight">Detail Laporan Kegiatan</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <?= $namaBulan[$bulan] . ' ' . $tahun ?>
                <?= $filterJenis !== '' ? ' · Filter: ' . htmlspecialchars($filterJenis) : '' ?>
                · <?= $totalKegiatan ?> kegiatan
            </p>
        </div>
        <?php if ($totalDibatal > 0): ?>
            <span class="badge badge-red"><?= $totalDibatal ?> dibatalkan</span>
        <?php endif; ?>
    </div>

    <?php if (empty($kegiatan)): ?>
        <div class="p-14 text-center">
            <div class="text-4xl mb-3">📭</div>
            <p class="text-slate-400 font-medium">Tidak ada kegiatan pada periode ini</p>
            <p class="text-xs text-slate-600 mt-1">Coba pilih bulan/tahun yang berbeda</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead>
                    <tr>
                    <th class="px-4 py-3.5 text-left w-10">No</th>
                    <th class="px-4 py-3.5 text-left">Tanggal &amp; Waktu</th>
                    <th class="px-4 py-3.5 text-left">Jenis Kegiatan</th>
                    <th class="px-4 py-3.5 text-left">Tema / Judul</th>
                    <th class="px-4 py-3.5 text-left">Lokasi</th>
                    <th class="px-4 py-3.5 text-center">Kehadiran</th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($kegiatan as $i => $k): ?>
                    <tr class="kegiatan-row <?= $k['status'] === 'Dibatalkan' ? 'opacity-50' : '' ?>">
                        <td class="px-4 text-slate-500 text-sm"><?= $i + 1 ?></td>

                        <!-- Tanggal & Waktu -->
                        <td class="px-4">
                            <div class="font-semibold text-zinc-800 text-sm whitespace-nowrap">
                                <?= tglIndo($k['tanggal']) ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <?= substr($k['waktu'], 0, 5) ?> WIB
                            </div>
                        </td>

                        <!-- Jenis Kegiatan -->
                        <td class="px-4">
                            <span class="badge badge-purple"><?= htmlspecialchars($k['jenis_ibadah']) ?></span>
                        </td>

                        <!-- Tema -->
                        <td class="px-4">
                            <div class="text-sm text-zinc-700 max-w-[200px]">
                                <?= $k['tema'] ? htmlspecialchars($k['tema']) : '<span class="text-slate-600 italic">—</span>' ?>
                            </div>
                            <?php if ($k['pembicara_tamu']): ?>
                                <div class="text-xs text-amber-600 mt-0.5">
                                    <?= htmlspecialchars($k['pembicara_tamu']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Lokasi -->
                        <td class="px-4 text-slate-600 text-sm max-w-[150px]">
                            <?= $k['lokasi'] ? htmlspecialchars($k['lokasi']) : '<span class="text-slate-600 italic">—</span>' ?>
                        </td>

                        <!-- Kehadiran -->
                        <td class="px-4 text-center" style="min-width:120px;">
                            <?php if ($k['jumlah_hadir'] > 0): ?>
                                <span class="badge badge-amber">
                                    <?= number_format($k['jumlah_hadir']) ?>
                                </span>
                            <?php elseif ($k['status'] === 'Selesai'): ?>
                                <span class="text-xs text-slate-500 italic">Belum diisi</span>
                            <?php else: ?>
                                <span class="text-slate-600 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                <!-- Footer Summary (dipindah ke dalam tbody agar tidak mengacaukan urutan cetak) -->
                    <tr class="border-t-2 border-slate-600">
                        <td colspan="2" class="px-4 py-3 text-sm font-bold text-zinc-900">
                            TOTAL
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-700">
                            <?= $totalKegiatan ?> kegiatan
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>