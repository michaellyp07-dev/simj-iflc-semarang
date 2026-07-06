<?php
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Jadwal Pelayanan';
$activePage = 'jadwal';
$pdo = db();

$msg     = '';
$msgType = '';

// ── POST HANDLERS ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Tambah Jadwal ──
    if ($action === 'add_jadwal') {
        $tgl    = $_POST['tanggal']             ?? '';
        $wkt    = $_POST['waktu']               ?? '';
        $jenis  = $_POST['jenis_ibadah']        ?? '';
        $tema   = trim($_POST['tema']           ?? '') ?: null;
        $lok    = trim($_POST['lokasi']         ?? '') ?: null;
        $ptamu  = trim($_POST['pembicara_tamu'] ?? '') ?: null;
        $hadir  = max(0, (int)($_POST['jumlah_hadir'] ?? 0));

        if (!$tgl || !$wkt || !$jenis) {
            $msg = 'Tanggal, waktu, dan jenis ibadah wajib diisi.';
            $msgType = 'error';
        } else {
            $pdo->prepare('INSERT INTO jadwal_ibadah (tanggal,waktu,jenis_ibadah,tema,lokasi,pembicara_tamu,jumlah_hadir,status) VALUES (?,?,?,?,?,?,?,\'Mendatang\')')
                ->execute([$tgl, $wkt, $jenis, $tema, $lok, $ptamu, $hadir]);
            $msg = 'Jadwal berhasil ditambahkan.';
            $msgType = 'success';
        }

    // ── Edit Jadwal ──
    } elseif ($action === 'edit_jadwal') {
        $id    = (int)($_POST['id_jadwal']         ?? 0);
        $tgl   = $_POST['tanggal']                 ?? '';
        $wkt   = $_POST['waktu']                   ?? '';
        $jenis = $_POST['jenis_ibadah']            ?? '';
        $tema  = trim($_POST['tema']               ?? '') ?: null;
        $lok   = trim($_POST['lokasi']             ?? '') ?: null;
        $sts   = $_POST['status']                  ?? 'Mendatang';
        $ptamu = trim($_POST['pembicara_tamu']     ?? '') ?: null;
        $hadir = max(0, (int)($_POST['jumlah_hadir'] ?? 0));

        if (!$id || !$tgl || !$wkt || !$jenis) {
            $msg = 'Data tidak lengkap.'; $msgType = 'error';
        } else {
            $pdo->prepare('UPDATE jadwal_ibadah SET tanggal=?,waktu=?,jenis_ibadah=?,tema=?,lokasi=?,status=?,pembicara_tamu=?,jumlah_hadir=? WHERE id_jadwal=?')
                ->execute([$tgl, $wkt, $jenis, $tema, $lok, $sts, $ptamu, $hadir, $id]);
            $msg = 'Jadwal berhasil diperbarui.'; $msgType = 'success';
        }

    // ── Hapus Jadwal ──
    } elseif ($action === 'delete_jadwal') {
        $id = (int)($_POST['id_jadwal'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM jadwal_ibadah WHERE id_jadwal=?')->execute([$id]);
            $msg = 'Jadwal berhasil dihapus.'; $msgType = 'success';
        }

    // ── Tambah Petugas (dengan CLASH DETECTION) ──
    } elseif ($action === 'add_petugas') {
        $idJadwal  = (int)($_POST['id_jadwal']  ?? 0);
        $idJemaat  = (int)($_POST['id_jemaat']  ?? 0);
        $peran     = trim($_POST['peran']        ?? '') ?: null;

        if (!$idJadwal || !$idJemaat) {
            $msg = 'Pilih jadwal dan jemaat terlebih dahulu.'; $msgType = 'error';
        } else {
            // Ambil tanggal & waktu jadwal yang dituju
            $jadwalTarget = $pdo->prepare('SELECT tanggal, waktu FROM jadwal_ibadah WHERE id_jadwal = ?');
            $jadwalTarget->execute([$idJadwal]);
            $jadwalRow = $jadwalTarget->fetch();

            // ══════════════════════════════════════════
            //  CLASH DETECTION — Core Logic
            //  Cek apakah jemaat sudah bertugas di
            //  jadwal LAIN yang sama tanggal & waktunya
            // ══════════════════════════════════════════
            $clashStmt = $pdo->prepare('
                SELECT ji.jenis_ibadah, ji.tanggal, ji.waktu
                FROM detail_jadwal dj
                JOIN jadwal_ibadah ji ON ji.id_jadwal = dj.id_jadwal
                WHERE dj.id_jemaat  = ?
                  AND ji.tanggal    = ?
                  AND ji.waktu      = ?
                  AND dj.id_jadwal != ?
                LIMIT 1
            ');
            $clashStmt->execute([
                $idJemaat,
                $jadwalRow['tanggal'],
                $jadwalRow['waktu'],
                $idJadwal,
            ]);
            $clash = $clashStmt->fetch();

            if ($clash) {
                // ── CLASH TERDETEKSI ──
                $namaPetugas = $pdo->prepare('SELECT nama_lengkap FROM jemaat WHERE id_jemaat=?');
                $namaPetugas->execute([$idJemaat]);
                $nama = $namaPetugas->fetchColumn();
                $msg  = "⚠️ Peringatan: Petugas \"{$nama}\" sudah memiliki tugas di jam ini "
                      . "(" . date('d/m/Y', strtotime($clash['tanggal'])) . " " . substr($clash['waktu'],0,5)
                      . " — " . $clash['jenis_ibadah'] . "). Penugasan dibatalkan.";
                $msgType = 'error';

            } else {
                // Cek sudah ada di jadwal yang sama (duplicate)
                $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM detail_jadwal WHERE id_jadwal=? AND id_jemaat=?');
                $dupStmt->execute([$idJadwal, $idJemaat]);
                if ($dupStmt->fetchColumn() > 0) {
                    $msg = 'Petugas sudah ditugaskan di jadwal ini.'; $msgType = 'warning';
                } else {
                    $pdo->prepare('INSERT INTO detail_jadwal (id_jadwal,id_jemaat,peran) VALUES (?,?,?)')
                        ->execute([$idJadwal, $idJemaat, $peran]);
                    $msg = 'Petugas berhasil ditambahkan ke jadwal.'; $msgType = 'success';
                }
            }
        }
        // Redirect agar kembali ke detail view
        $redirectId = $idJadwal ?? 0;
        header("Location: jadwal.php?detail=$redirectId&msg=" . urlencode($msg) . "&type=$msgType");
        exit;

    // ── Hapus Petugas dari Jadwal ──
    } elseif ($action === 'remove_petugas') {
        $idDetail  = (int)($_POST['id_detail']  ?? 0);
        $idJadwal  = (int)($_POST['id_jadwal']  ?? 0);
        if ($idDetail > 0) {
            $pdo->prepare('DELETE FROM detail_jadwal WHERE id_detail=?')->execute([$idDetail]);
        }
        header("Location: jadwal.php?detail=$idJadwal&msg=" . urlencode('Petugas berhasil dihapus dari jadwal.') . "&type=success");
        exit;
    }
}

// Ambil msg dari redirect
if ($msg === '' && isset($_GET['msg'])) {
    $msg     = $_GET['msg'];
    $msgType = $_GET['type'] ?? 'success';
}

// ── FETCH DATA ───────────────────────────────────────
$detailId   = (int)($_GET['detail'] ?? 0);
$jemaatList = $pdo->query('SELECT id_jemaat, nama_lengkap FROM jemaat ORDER BY nama_lengkap')->fetchAll();

// Semua jadwal
$allJadwal  = $pdo->query('
    SELECT ji.*, COUNT(dj.id_detail) AS jml_petugas
    FROM jadwal_ibadah ji
    LEFT JOIN detail_jadwal dj ON dj.id_jadwal = ji.id_jadwal
    GROUP BY ji.id_jadwal
    ORDER BY ji.tanggal DESC, ji.waktu DESC
')->fetchAll();

// Jenis tetap (hardcode)
$jenisTetap = ['Ibadah Raya', 'Komsel', 'Ibadah Natal', 'Ibadah Paskah', 'KKR'];

// Jenis dinamis dari DB (hanya yang aktif)
$jenisDinamis = $pdo->query(
    "SELECT nama_jenis FROM jenis_kegiatan WHERE is_aktif = 1 ORDER BY nama_jenis ASC"
)->fetchAll(PDO::FETCH_COLUMN);

// Detail jadwal (kalau dipilih)
$detailJadwal   = null;
$petugasJadwal  = [];
if ($detailId > 0) {
    $s = $pdo->prepare('SELECT * FROM jadwal_ibadah WHERE id_jadwal=?');
    $s->execute([$detailId]);
    $detailJadwal = $s->fetch();

    $s2 = $pdo->prepare('
        SELECT dj.id_detail, dj.peran, j.nama_lengkap, d.nama_divisi
        FROM detail_jadwal dj
        JOIN jemaat j ON j.id_jemaat = dj.id_jemaat
        JOIN divisi d ON d.id_divisi  = j.id_divisi
        WHERE dj.id_jadwal = ?
        ORDER BY d.nama_divisi, j.nama_lengkap
    ');
    $s2->execute([$detailId]);
    $petugasJadwal = $s2->fetchAll();
}

function fmtTgl(string $d): string {
    $bln=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts=strtotime($d);
    return date('d',$ts).' '.$bln[(int)date('n',$ts)].' '.date('Y',$ts);
}

include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-black text-zinc-900 tracking-tight">Jadwal Pelayanan</h2>
        <p class="text-xs text-slate-500 mt-0.5"><?= count($allJadwal) ?> jadwal terdaftar</p>
    </div>
    <button onclick="openJadwalModal()" class="btn-primary flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Jadwal Baru
    </button>
</div>

<!-- ALERT -->
<?php if ($msg !== ''): ?>
<div class="flex items-start gap-2 px-4 py-3.5 rounded-xl mb-5 text-sm font-medium
            <?= $msgType==='success' ? 'alert-success' : ($msgType==='warning' ? 'alert-warning' : 'alert-error') ?>"
     data-auto-dismiss>
    <span class="flex-shrink-0 text-base"><?= $msgType==='success' ? '✅' : ($msgType==='warning' ? '⚠️' : '🚫') ?></span>
    <span><?= htmlspecialchars($msg) ?></span>
</div>
<?php endif; ?>

<?php if ($detailJadwal): ?>
<!-- ═══ DETAIL PANEL ═══ -->
<div class="glass-card rounded-2xl overflow-hidden mb-6 border border-indigo-500/20">

    <!-- Panel Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4
                bg-gradient-to-r from-indigo-500/10 to-transparent border-b border-slate-700/60">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-purple"><?= htmlspecialchars($detailJadwal['jenis_ibadah']) ?></span>
                <span class="badge badge-gray"><?= $detailJadwal['status'] ?></span>
            </div>
            <p class="font-bold text-zinc-900">
                <?= $detailJadwal['tema'] ? htmlspecialchars($detailJadwal['tema']) : 'Jadwal Ibadah' ?>
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
                <?= fmtTgl($detailJadwal['tanggal']) ?> · <?= substr($detailJadwal['waktu'],0,5) ?> WIB
                <?= $detailJadwal['lokasi'] ? ' · ' . htmlspecialchars($detailJadwal['lokasi']) : '' ?>
            </p>
        </div>
        <a href="jadwal.php" class="text-xs text-slate-500 hover:text-zinc-900 transition-colors">✕ Tutup Panel</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 md:divide-x divide-slate-700/60">

        <!-- Daftar Petugas -->
        <div class="p-5">
            <h3 class="text-sm font-black text-zinc-900 mb-3">
                Petugas Bertugas (<?= count($petugasJadwal) ?>)
            </h3>

            <?php if ($detailJadwal['pembicara_tamu']): ?>
            <div class="flex items-center gap-3 p-2.5 rounded-lg mb-3
                        bg-gradient-to-r from-amber-500/10 to-transparent
                        border border-amber-500/20">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500
                            flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                    <?= mb_strtoupper(mb_substr($detailJadwal['pembicara_tamu'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-amber-700 truncate">
                        <?= htmlspecialchars($detailJadwal['pembicara_tamu']) ?>
                    </p>
                    <p class="text-xs text-amber-500/70">Pembicara Tamu</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($petugasJadwal) && !$detailJadwal['pembicara_tamu']): ?>
            <p class="text-xs text-slate-500 italic">Belum ada petugas ditugaskan.</p>
            <?php elseif (!empty($petugasJadwal)): ?>
            <div class="space-y-2">
                <?php foreach ($petugasJadwal as $p): ?>
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-100">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center
                                text-xs font-bold text-white flex-shrink-0" style="background:#dc2626">
                        <?= mb_strtoupper(mb_substr($p['nama_lengkap'],0,1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-zinc-800 truncate"><?= htmlspecialchars($p['nama_lengkap']) ?></p>
                        <p class="text-xs text-slate-500">
                            <?= htmlspecialchars($p['peran'] ?: $p['nama_divisi']) ?>
                        </p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action"    value="remove_petugas" />
                        <input type="hidden" name="id_detail" value="<?= $p['id_detail'] ?>" />
                        <input type="hidden" name="id_jadwal" value="<?= $detailJadwal['id_jadwal'] ?>" />
                        <button type="submit" class="btn-danger !py-1 !px-2 !text-xs"
                                onclick="return confirm('Hapus petugas ini dari jadwal?')">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Form Tambah Petugas -->
        <div class="p-5">
            <h3 class="text-sm font-black text-zinc-900 mb-3">➕ Tambah Petugas</h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action"    value="add_petugas" />
                <input type="hidden" name="id_jadwal" value="<?= $detailJadwal['id_jadwal'] ?>" />

                <div>
                    <label class="form-label">Pilih Petugas <span class="text-red-400">*</span></label>
                    <select name="id_jemaat" class="form-select" required>
                        <option value="">— Pilih Nama Petugas Pelayanan —</option>
                        <?php foreach ($jemaatList as $j): ?>
                        <option value="<?= $j['id_jemaat'] ?>"><?= htmlspecialchars($j['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Peran <span class="text-slate-500">(opsional)</span></label>
                    <input type="text" name="peran" class="form-input"
                           placeholder="mis: Singer Lead, Operator Slide…" />
                </div>

                <button type="submit" class="btn-primary w-full mt-1">
                    Tugaskan Petugas
                </button>

                <p class="text-[11px] text-slate-500 text-center">
                    🛡️ Sistem akan otomatis menolak jika petugas sudah bertugas di jam yang sama
                </p>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ JADWAL LIST ═══ -->
<div class="glass-card rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-700/60">
        <h3 class="text-sm font-black text-zinc-900 tracking-tight">Semua Jadwal</h3>
    </div>

    <?php if (empty($allJadwal)): ?>
    <div class="p-10 text-center text-slate-500 text-sm">Belum ada jadwal. Klik "Buat Jadwal Baru".</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-5 py-3.5 text-left">Tanggal</th>
                    <th class="px-5 py-3.5 text-left">Waktu</th>
                    <th class="px-5 py-3.5 text-left">Jenis</th>
                    <th class="px-5 py-3.5 text-left">Tema</th>
                    <th class="px-5 py-3.5 text-center">Petugas</th>
                    <th class="px-5 py-3.5 text-center">Hadir</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allJadwal as $j): ?>
                <tr class="<?= $detailId===$j['id_jadwal'] ? 'bg-indigo-500/5' : '' ?>">
                    <td class="px-5 font-medium text-zinc-800"><?= fmtTgl($j['tanggal']) ?></td>
                    <td class="px-5 text-slate-600"><?= substr($j['waktu'],0,5) ?> WIB</td>
                    <td class="px-5">
                        <span class="badge badge-purple"><?= htmlspecialchars($j['jenis_ibadah']) ?></span>
                    </td>
                    <td class="px-5 text-slate-600 text-sm max-w-[180px] truncate">
                        <?= $j['tema'] ? htmlspecialchars($j['tema']) : '—' ?>
                    </td>
                    <td class="px-5 text-center">
                        <span class="badge <?= $j['jml_petugas']>0 ? 'badge-purple' : 'badge-gray' ?>">
                            <?= $j['jml_petugas'] ?>
                        </span>
                    </td>
                    <td class="px-5 text-center">
                        <?php if ($j['jumlah_hadir'] > 0): ?>
                            <span class="badge badge-amber" title="Jumlah hadir">
                                <?= number_format($j['jumlah_hadir']) ?>
                            </span>
                        <?php elseif ($j['status'] === 'Selesai'): ?>
                            <span class="badge badge-gray">—</span>
                        <?php else: ?>
                            <span class="text-slate-600 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 text-center">
                        <?php
                        $sc = ['Mendatang'=>'badge-green','Selesai'=>'badge-gray','Dibatalkan'=>'badge-red'];
                        ?>
                        <span class="badge <?= $sc[$j['status']] ?? 'badge-gray' ?>"><?= $j['status'] ?></span>
                    </td>
                    <td class="px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5 py-2 flex-wrap">
                            <a href="jadwal.php?detail=<?= $j['id_jadwal'] ?>"
                               class="btn-edit !py-1 !px-2.5 !text-xs">
                                Petugas
                            </a>
                            <button onclick='openEditJadwalModal(<?= htmlspecialchars(json_encode($j), ENT_QUOTES) ?>)'
                                    class="btn-edit !py-1 !px-2.5 !text-xs">Edit</button>
                            <form method="POST"
                                  onsubmit="return confirm('Hapus jadwal ini? Semua data petugas terkait akan ikut terhapus.')">
                                <input type="hidden" name="action"    value="delete_jadwal" />
                                <input type="hidden" name="id_jadwal" value="<?= $j['id_jadwal'] ?>" />
                                <button type="submit" class="btn-danger !py-1 !px-2.5 !text-xs">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ MODAL: Add / Edit Jadwal ═══ -->
<div id="jadwal-modal-overlay"
     class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl w-full max-w-lg p-6 sm:p-8 animate-fade-in"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-6">
            <h3 id="jadwal-modal-title" class="text-lg font-black text-zinc-900">Buat Jadwal Baru</h3>
            <button onclick="closeJadwalModal()" class="text-slate-400 hover:text-zinc-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action"    id="jadwal-action" value="add_jadwal" />
            <input type="hidden" name="id_jadwal" id="jadwal-id"     value="" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tanggal <span class="text-red-400">*</span></label>
                    <input type="date" name="tanggal" id="jadwal-tgl" class="form-input" required />
                </div>
                <div>
                    <label class="form-label">Waktu <span class="text-red-400">*</span></label>
                    <input type="time" name="waktu" id="jadwal-wkt" class="form-input" required />
                </div>
                <div>
                    <label class="form-label">Jenis Kegiatan <span class="text-red-400">*</span></label>
                    <select name="jenis_ibadah" id="jadwal-jenis" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Ibadah Raya">Ibadah Raya</option>
                        <option value="Komsel">Komsel</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" id="jadwal-status" class="form-select">
                        <option value="Mendatang">Mendatang</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Jumlah Hadir <span class="text-slate-500">(opsional)</span></label>
                    <input type="number" name="jumlah_hadir" id="jadwal-hadir" class="form-input"
                           min="0" placeholder="mis: 150" value="0" />
                    <p class="text-[11px] text-slate-500 mt-1">Isi setelah ibadah selesai</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Tema / Judul</label>
                    <input type="text" name="tema" id="jadwal-tema" class="form-input"
                           placeholder="mis: Kasih yang Tak Berkesudahan" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" id="jadwal-lokasi" class="form-input"
                           placeholder="mis: Gedung Utama IFLC" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Pembicara Tamu <span class="text-slate-500">(opsional — untuk pembicara dari luar)</span></label>
                    <input type="text" name="pembicara_tamu" id="jadwal-ptamu" class="form-input"
                           placeholder="mis: Pdt. Yohanes Surya" />
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeJadwalModal()"
                        class="flex-1 py-2.5 rounded-lg border border-slate-300 text-slate-600
                               hover:border-slate-400 transition-all text-sm font-medium">
                    Batal
                </button>
                <button type="submit" id="jadwal-submit" class="btn-primary flex-1">
                    Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const jOverlay = document.getElementById('jadwal-modal-overlay');

function openJadwalModal() {
    document.getElementById('jadwal-modal-title').textContent = 'Buat Jadwal Baru';
    document.getElementById('jadwal-action').value  = 'add_jadwal';
    document.getElementById('jadwal-id').value      = '';
    document.getElementById('jadwal-tgl').value     = '';
    document.getElementById('jadwal-wkt').value     = '';
    document.getElementById('jadwal-jenis').value   = '';
    if (document.getElementById('jadwal-ptamu')) document.getElementById('jadwal-ptamu').value = '';
    document.getElementById('jadwal-status').value  = 'Mendatang';
    document.getElementById('jadwal-tema').value    = '';
    document.getElementById('jadwal-lokasi').value  = '';
    document.getElementById('jadwal-submit').textContent = 'Simpan Jadwal';
    jOverlay.classList.replace('hidden','flex');
}

function openEditJadwalModal(data) {
    document.getElementById('jadwal-modal-title').textContent = 'Edit Jadwal';
    document.getElementById('jadwal-action').value  = 'edit_jadwal';
    document.getElementById('jadwal-id').value      = data.id_jadwal;
    document.getElementById('jadwal-tgl').value     = data.tanggal;
    document.getElementById('jadwal-wkt').value     = data.waktu;
    document.getElementById('jadwal-jenis').value   = data.jenis_ibadah;
    document.getElementById('jadwal-hadir').value   = data.jumlah_hadir ?? 0;
    document.getElementById('jadwal-status').value  = data.status;
    document.getElementById('jadwal-tema').value    = data.tema ?? '';
    document.getElementById('jadwal-lokasi').value  = data.lokasi ?? '';
    document.getElementById('jadwal-ptamu').value   = data.pembicara_tamu ?? '';
    document.getElementById('jadwal-submit').textContent = 'Simpan Perubahan';
    jOverlay.classList.replace('hidden','flex');
}

function closeJadwalModal() {
    jOverlay.classList.replace('flex','hidden');
}

jOverlay.addEventListener('click', e => { if (e.target === jOverlay) closeJadwalModal(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
