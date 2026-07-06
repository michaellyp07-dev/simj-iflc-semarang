<?php
/**
 * admin/jemaat_umum.php — CRUD Data Jemaat Umum
 * Data lengkap seluruh anggota jemaat IFLC (tanpa divisi pelayanan).
 */
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Data Jemaat Umum';
$activePage = 'jemaat_umum';
$pdo = db();

$msg     = '';
$msgType = '';

// ──────────────────────────────────────────────
//  POST HANDLERS
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama    = trim($_POST['nama_lengkap']  ?? '');
        $tempat  = trim($_POST['tempat_lahir']  ?? '') ?: null;
        $tgl     = trim($_POST['tanggal_lahir'] ?? '') ?: null;
        $hp      = trim($_POST['no_hp']         ?? '') ?: null;
        $alamat  = trim($_POST['alamat']         ?? '') ?: null;

        if ($nama === '') {
            $msg     = 'Nama lengkap wajib diisi.';
            $msgType = 'error';
        } else {
            $pdo->prepare('INSERT INTO jemaat_umum (nama_lengkap, tempat_lahir, tanggal_lahir, no_hp, alamat)
                           VALUES (?,?,?,?,?)')
                ->execute([$nama, $tempat, $tgl, $hp, $alamat]);
            $msg     = 'Jemaat "' . htmlspecialchars($nama) . '" berhasil ditambahkan.';
            $msgType = 'success';
        }

    } elseif ($action === 'edit') {
        $id     = (int)($_POST['id']            ?? 0);
        $nama   = trim($_POST['nama_lengkap']   ?? '');
        $tempat = trim($_POST['tempat_lahir']   ?? '') ?: null;
        $tgl    = trim($_POST['tanggal_lahir']  ?? '') ?: null;
        $hp     = trim($_POST['no_hp']          ?? '') ?: null;
        $alamat = trim($_POST['alamat']          ?? '') ?: null;

        if ($id === 0 || $nama === '') {
            $msg     = 'Data tidak valid atau tidak lengkap.';
            $msgType = 'error';
        } else {
            $pdo->prepare('UPDATE jemaat_umum
                           SET nama_lengkap=?, tempat_lahir=?, tanggal_lahir=?, no_hp=?, alamat=?
                           WHERE id=?')
                ->execute([$nama, $tempat, $tgl, $hp, $alamat, $id]);

            // Sinkronkan nama di tabel jemaat pelayanan jika ada relasi
            $pdo->prepare('UPDATE jemaat SET nama_lengkap=? WHERE id_jemaat_umum=?')
                ->execute([$nama, $id]);

            $msg     = 'Data jemaat berhasil diperbarui.';
            $msgType = 'success';
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Cek apakah sudah terdaftar sebagai petugas pelayanan
            $chk = $pdo->prepare('SELECT COUNT(*) FROM jemaat WHERE id_jemaat_umum = ?');
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) {
                $msg     = 'Gagal menghapus: jemaat ini terdaftar sebagai petugas pelayanan.';
                $msgType = 'warning';
            } else {
                $pdo->prepare('DELETE FROM jemaat_umum WHERE id = ?')->execute([$id]);
                $msg     = 'Data jemaat berhasil dihapus.';
                $msgType = 'success';
            }
        }
    }
}

// ──────────────────────────────────────────────
//  FETCH DATA
// ──────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '1=1';

if ($search !== '') {
    $where    = '(nama_lengkap LIKE ? OR no_hp LIKE ? OR alamat LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT ju.*,
           (SELECT COUNT(*) FROM jemaat j WHERE j.id_jemaat_umum = ju.id) AS is_petugas
    FROM jemaat_umum ju
    WHERE $where
    ORDER BY ju.nama_lengkap ASC
");
$stmt->execute($params);
$jemaatList = $stmt->fetchAll();
$totalAll   = $pdo->query('SELECT COUNT(*) FROM jemaat_umum')->fetchColumn();

// Helper format tanggal Indonesia
function tglLahirIndo(?string $d): string {
    if (!$d) return '—';
    $bln = ['','Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember'];
    $ts  = strtotime($d);
    return date('j', $ts) . ' ' . $bln[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-black text-zinc-900 tracking-tight">Data Jemaat Umum</h2>
        <p class="text-xs text-slate-500 mt-0.5">Total <?= $totalAll ?> anggota terdaftar</p>
    </div>
    <button onclick="openModal()" id="btn-add-jemaat-umum"
            class="btn-primary flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Jemaat
    </button>
</div>

<!-- ALERT -->
<?php if ($msg !== ''): ?>
<div class="flex items-start gap-2 px-4 py-3 rounded-xl mb-5 text-sm
            <?= $msgType==='success' ? 'alert-success' : ($msgType==='warning' ? 'alert-warning' : 'alert-error') ?>"
     data-auto-dismiss>
    <?= $msgType==='success' ? '✅' : ($msgType==='warning' ? '⚠️' : '❌') ?>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- SEARCH -->
<div class="glass-card rounded-xl p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Cari nama, no. HP, atau alamat…"
               class="form-input flex-1 min-w-[200px] !py-2" />
        <button type="submit" class="btn-primary !py-2 !px-5">Cari</button>
        <?php if ($search): ?>
        <a href="jemaat_umum.php" class="btn-edit !py-2 !px-5">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABLE -->
<div class="glass-card rounded-2xl overflow-hidden">
    <?php if (empty($jemaatList)): ?>
    <div class="p-12 text-center">
        <div class="text-5xl mb-3">👤</div>
        <p class="text-slate-400 text-sm">
            <?= $search ? 'Tidak ada jemaat yang cocok dengan pencarian.' : 'Belum ada data jemaat.' ?>
        </p>
        <?php if ($search): ?>
        <a href="jemaat_umum.php" class="mt-3 inline-block text-xs text-red-600 hover:text-red-800">Hapus filter →</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-5 py-3.5 text-left w-10">#</th>
                    <th class="px-5 py-3.5 text-left">Nama Lengkap</th>
                    <th class="px-5 py-3.5 text-left">Tempat, Tgl Lahir</th>
                    <th class="px-5 py-3.5 text-left">No. HP / WA</th>
                    <th class="px-5 py-3.5 text-left">Alamat</th>
                    <th class="px-5 py-3.5 text-center">Pelayanan</th>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($jemaatList as $i => $j): ?>
                <tr>
                    <td class="px-5 text-slate-500 text-sm"><?= $i + 1 ?></td>
                    <td class="px-5">
                        <div class="flex items-center gap-3">

                            <span class="font-semibold text-zinc-800">
                                <?= htmlspecialchars($j['nama_lengkap']) ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-5 text-slate-400 text-sm">
                        <?php if ($j['tempat_lahir'] || $j['tanggal_lahir']): ?>
                            <?= htmlspecialchars($j['tempat_lahir'] ?? '') ?>
                            <?= ($j['tempat_lahir'] && $j['tanggal_lahir']) ? ', ' : '' ?>
                            <?= tglLahirIndo($j['tanggal_lahir']) ?>
                        <?php else: ?>
                            <span class="text-slate-600">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 text-slate-400 text-sm">
                        <?php if ($j['no_hp']): ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $j['no_hp']) ?>"
                               target="_blank"
                               class="text-green-600 hover:text-green-700 transition-colors">
                                <?= htmlspecialchars($j['no_hp']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-slate-600">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 text-slate-400 text-sm max-w-[180px]">
                        <span class="line-clamp-1">
                            <?= $j['alamat'] ? htmlspecialchars($j['alamat']) : '<span class="text-slate-600">—</span>' ?>
                        </span>
                    </td>
                    <td class="px-5 text-center">
                        <?php if ($j['is_petugas'] > 0): ?>
                            <span class="badge badge-purple">✓ Petugas</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Umum</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 text-center">
                        <div class="flex items-center justify-center gap-2 py-2">
                            <button type="button"
                                    onclick='openEditModal(<?= htmlspecialchars(json_encode($j), ENT_QUOTES) ?>)'
                                    class="btn-edit">Edit</button>
                            <form method="POST"
                                  onsubmit="return confirm('Hapus jemaat <?= htmlspecialchars(addslashes($j['nama_lengkap'])) ?>? Tindakan ini tidak bisa dibatalkan.')">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id"     value="<?= $j['id'] ?>" />
                                <button type="submit" class="btn-danger">Hapus</button>
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

<!-- ═══ MODAL: Add / Edit ═══ -->
<div id="modal-overlay"
     class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl w-full max-w-lg p-6 sm:p-8 animate-fade-in"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-6">
            <h3 id="modal-title" class="text-lg font-black text-zinc-900">Tambah Jemaat</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-zinc-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" id="jemaat-umum-form">
            <input type="hidden" name="action" id="form-action" value="add" />
            <input type="hidden" name="id"     id="form-id"     value="" />

            <div class="space-y-4">
                <div>
                    <label class="form-label">Nama Lengkap <span class="text-red-400">*</span></label>
                    <input type="text" name="nama_lengkap" id="form-nama"
                           class="form-input" placeholder="Masukkan nama lengkap" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tempat Lahir <span class="text-slate-500">(opsional)</span></label>
                        <input type="text" name="tempat_lahir" id="form-tempat"
                               class="form-input" placeholder="Kota lahir" />
                    </div>
                    <div>
                        <label class="form-label">Tanggal Lahir <span class="text-slate-500">(opsional)</span></label>
                        <input type="date" name="tanggal_lahir" id="form-tgl"
                               class="form-input" />
                    </div>
                </div>

                <div>
                    <label class="form-label">No. HP / WA <span class="text-slate-500">(opsional)</span></label>
                    <input type="tel" name="no_hp" id="form-hp"
                           class="form-input" placeholder="08xxxxxxxxxx" />
                </div>

                <div>
                    <label class="form-label">Alamat <span class="text-slate-500">(opsional)</span></label>
                    <textarea name="alamat" id="form-alamat" rows="3"
                              class="form-input resize-none" placeholder="Masukkan alamat lengkap"></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-7">
                <button type="button" onclick="closeModal()"
                        class="flex-1 py-2.5 rounded-lg border border-slate-300 text-slate-600
                               hover:border-slate-400 hover:text-zinc-900 transition-all text-sm font-medium">
                    Batal
                </button>
                <button type="submit" id="form-submit" class="btn-primary flex-1">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const overlay = document.getElementById('modal-overlay');

function openModal() {
    document.getElementById('modal-title').textContent  = 'Tambah Jemaat Baru';
    document.getElementById('form-action').value        = 'add';
    document.getElementById('form-id').value            = '';
    document.getElementById('form-nama').value          = '';
    document.getElementById('form-tempat').value        = '';
    document.getElementById('form-tgl').value           = '';
    document.getElementById('form-hp').value            = '';
    document.getElementById('form-alamat').value        = '';
    document.getElementById('form-submit').textContent  = 'Tambah Jemaat';
    overlay.classList.replace('hidden', 'flex');
}

function openEditModal(data) {
    document.getElementById('modal-title').textContent  = 'Edit Data Jemaat';
    document.getElementById('form-action').value        = 'edit';
    document.getElementById('form-id').value            = data.id;
    document.getElementById('form-nama').value          = data.nama_lengkap;
    document.getElementById('form-tempat').value        = data.tempat_lahir ?? '';
    document.getElementById('form-tgl').value           = data.tanggal_lahir ?? '';
    document.getElementById('form-hp').value            = data.no_hp ?? '';
    document.getElementById('form-alamat').value        = data.alamat ?? '';
    document.getElementById('form-submit').textContent  = 'Simpan Perubahan';
    overlay.classList.replace('hidden', 'flex');
}

function closeModal() {
    overlay.classList.replace('flex', 'hidden');
}

overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
