<?php
/**
 * admin/jemaat.php — CRUD Data Jemaat
 */
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Data Petugas';
$activePage = 'jemaat';
$pdo = db();

$msg     = '';
$msgType = '';

// ──────────────────────────────────────────────
//  POST HANDLERS
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $idUmum = (int)($_POST['id_jemaat_umum'] ?? 0);
        $divisi = (int)($_POST['id_divisi']      ?? 0);

        if ($idUmum === 0 || $divisi === 0) {
            $msg     = 'Pilih nama jemaat pelayanan dan divisi terlebih dahulu.';
            $msgType = 'error';
        } else {
            // Ambil nama & no HP dari jemaat_umum
            $ju = $pdo->prepare('SELECT nama_lengkap, no_hp FROM jemaat_umum WHERE id = ?');
            $ju->execute([$idUmum]);
            $juData = $ju->fetch();

            if (!$juData) {
                $msg     = 'Data jemaat pelayanan tidak ditemukan.';
                $msgType = 'error';
            } else {
                // Cek apakah sudah terdaftar sebagai petugas
                $cek = $pdo->prepare('SELECT COUNT(*) FROM jemaat WHERE id_jemaat_umum = ?');
                $cek->execute([$idUmum]);
                if ($cek->fetchColumn() > 0) {
                    $msg     = 'Jemaat pelayanan ini sudah terdaftar sebagai petugas pelayanan.';
                    $msgType = 'warning';
                } else {
                    $pdo->prepare('INSERT INTO jemaat (id_jemaat_umum, id_divisi, nama_lengkap, no_telepon) VALUES (?,?,?,?)')
                        ->execute([$idUmum, $divisi, $juData['nama_lengkap'], $juData['no_hp']]);
                    $msg     = 'Jemaat "' . htmlspecialchars($juData['nama_lengkap']) . '" berhasil ditambahkan.';
                    $msgType = 'success';
                }
            }
        }

    } elseif ($action === 'edit') {
        $id     = (int)($_POST['id_jemaat']     ?? 0);
        $divisi = (int)($_POST['id_divisi']     ?? 0);

        if ($id === 0 || $divisi === 0) {
            $msg     = 'Data tidak valid atau tidak lengkap.';
            $msgType = 'error';
        } else {
            // Hanya update divisi; nama & no_telepon mengikuti jemaat_umum
            $pdo->prepare('UPDATE jemaat SET id_divisi=? WHERE id_jemaat=?')
                ->execute([$divisi, $id]);
            $msg     = 'Data jemaat pelayanan berhasil diperbarui.';
            $msgType = 'success';
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_jemaat'] ?? 0);
        if ($id > 0) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM detail_jadwal WHERE id_jemaat = ?');
            $chk->execute([$id]);
            if ($chk->fetchColumn() > 0) {
                $msg = 'Gagal menghapus: jemaat ini masih memiliki riwayat penugasan.';
                $msgType = 'warning';
            } else {
                $pdo->prepare('DELETE FROM jemaat WHERE id_jemaat = ?')->execute([$id]);
                $msg = 'Data jemaat pelayanan berhasil dihapus.';
                $msgType = 'success';
            }
        }
    }
}

// ──────────────────────────────────────────────
//  FETCH DATA
// ──────────────────────────────────────────────
$divisiList    = $pdo->query('SELECT * FROM divisi ORDER BY nama_divisi')->fetchAll();
// Daftar jemaat umum yang belum jadi petugas (untuk dropdown tambah)
$jemaatUmumList = $pdo->query('
    SELECT ju.id, ju.nama_lengkap, ju.no_hp
    FROM jemaat_umum ju
    WHERE ju.id NOT IN (SELECT id_jemaat_umum FROM jemaat WHERE id_jemaat_umum IS NOT NULL)
    ORDER BY ju.nama_lengkap
')->fetchAll();
$filterDivisi = (int)($_GET['divisi'] ?? 0);
$search       = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterDivisi > 0) { $where[] = 'j.id_divisi = ?'; $params[] = $filterDivisi; }
if ($search !== '')    { $where[] = '(j.nama_lengkap LIKE ? OR j.no_telepon LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $pdo->prepare('
    SELECT j.id_jemaat, j.nama_lengkap, j.no_telepon, j.id_divisi, d.nama_divisi,
           COUNT(dj.id_detail) AS total_tugas
    FROM jemaat j
    JOIN divisi d ON d.id_divisi = j.id_divisi
    LEFT JOIN detail_jadwal dj ON dj.id_jemaat = j.id_jemaat
    WHERE ' . implode(' AND ', $where) . '
    GROUP BY j.id_jemaat
    ORDER BY d.nama_divisi, j.nama_lengkap
');
$stmt->execute($params);
$jemaatList = $stmt->fetchAll();
$totalAll   = $pdo->query('SELECT COUNT(*) FROM jemaat')->fetchColumn();

// ──────────────────────────────────────────────
//  RENDER
// ──────────────────────────────────────────────
include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-black text-zinc-900 tracking-tight">Data Petugas Pelayanan</h2>
        <p class="text-xs text-slate-500 mt-0.5">Total <?= $totalAll ?> anggota terdaftar</p>
    </div>
    <button onclick="openModal()" id="btn-add-jemaat"
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

<!-- FILTERS -->
<div class="glass-card rounded-xl p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Cari nama atau no. telepon…"
               class="form-input flex-1 min-w-[180px] !py-2" />
        <select name="divisi" class="form-select w-auto min-w-[160px] !py-2">
            <option value="0">Semua Divisi</option>
            <?php foreach ($divisiList as $d): ?>
            <option value="<?= $d['id_divisi'] ?>" <?= $filterDivisi===$d['id_divisi'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['nama_divisi']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary !py-2 !px-5">Filter</button>
        <?php if ($search || $filterDivisi): ?>
        <a href="jemaat.php" class="btn-edit !py-2 !px-5">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABLE -->
<div class="glass-card rounded-2xl overflow-hidden">
    <?php if (empty($jemaatList)): ?>
    <div class="p-12 text-center">
        <div class="text-5xl mb-3">👤</div>
        <p class="text-slate-400 text-sm">Tidak ada petugas pelayanan ditemukan.</p>
        <?php if ($search || $filterDivisi): ?>
        <a href="jemaat.php" class="mt-3 inline-block text-xs text-indigo-400 hover:text-indigo-300">Hapus filter →</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-5 py-3.5 text-left w-10">#</th>
                    <th class="px-5 py-3.5 text-left">Nama Lengkap</th>
                    <th class="px-5 py-3.5 text-left">Divisi</th>
                    <th class="px-5 py-3.5 text-left">No. Telepon</th>

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
                    <td class="px-5">
                        <span class="badge badge-gray"><?= htmlspecialchars($j['nama_divisi']) ?></span>
                    </td>
                    <td class="px-5 text-slate-600 text-sm">
                        <?= $j['no_telepon'] ? htmlspecialchars($j['no_telepon']) : '—' ?>
                    </td>

                    <td class="px-5 text-center">
                        <div class="flex items-center justify-center gap-2 py-2">
                            <button type="button"
                                    onclick='openEditModal(<?= htmlspecialchars(json_encode($j), ENT_QUOTES) ?>)'
                                    class="btn-edit">Edit</button>
                            <form method="POST"
                                  onsubmit="return confirm('Hapus jemaat <?= htmlspecialchars(addslashes($j['nama_lengkap'])) ?>? Tindakan ini tidak bisa dibatalkan.')">
                                <input type="hidden" name="action"    value="delete" />
                                <input type="hidden" name="id_jemaat" value="<?= $j['id_jemaat'] ?>" />
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

<!-- ═══ MODAL: Add ═══ -->
<div id="modal-overlay"
     class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl w-full max-w-md p-6 sm:p-8 animate-fade-in"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-6">
            <h3 id="modal-title" class="text-lg font-black text-zinc-900">Tambah Petugas</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-zinc-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" id="jemaat-form">
            <input type="hidden" name="action"    id="form-action" value="add" />
            <input type="hidden" name="id_jemaat" id="form-id"     value="" />

            <div class="space-y-4">

                <!-- Tambah: pilih dari jemaat umum -->
                <div id="field-jemaat-umum">
                    <label class="form-label">Nama Jemaat <span class="text-red-400">*</span></label>
                    <?php if (empty($jemaatUmumList)): ?>
                        <div class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm alert-warning">
                            ⚠️ Belum ada jemaat umum yang tersedia.
                            <a href="jemaat_umum.php" class="underline text-amber-300 ml-1">Tambah di sini →</a>
                        </div>
                    <?php else: ?>
                        <select name="id_jemaat_umum" id="form-jemaat-umum" class="form-select">
                            <option value="">— Pilih Nama Jemaat —</option>
                            <?php foreach ($jemaatUmumList as $ju): ?>
                            <option value="<?= $ju['id'] ?>">
                                <?= htmlspecialchars($ju['nama_lengkap']) ?>
                                <?= $ju['no_hp'] ? ' · ' . htmlspecialchars($ju['no_hp']) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">Nama diambil dari Data Jemaat Umum</p>
                    <?php endif; ?>
                </div>

                <!-- Edit: tampilkan nama (read only) -->
                <div id="field-nama-readonly" class="hidden">
                    <label class="form-label">Nama Jemaat</label>
                    <input type="text" id="display-nama" class="form-input opacity-60" readonly />
                </div>

                <div>
                    <label class="form-label">Divisi <span class="text-red-400">*</span></label>
                    <select name="id_divisi" id="form-divisi" class="form-select" required>
                        <option value="">— Pilih Divisi —</option>
                        <?php foreach ($divisiList as $d): ?>
                        <option value="<?= $d['id_divisi'] ?>">
                            <?= htmlspecialchars($d['nama_divisi']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 mt-7">
                <button type="button" onclick="closeModal()"
                        class="flex-1 py-2.5 rounded-lg border border-slate-300 text-slate-600
                               hover:border-slate-400 hover:text-zinc-900 transition-all text-sm font-medium">
                    Batal
                </button>
                <button type="submit" id="form-submit"
                        class="btn-primary flex-1">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const overlay = document.getElementById('modal-overlay');

function openModal() {
    document.getElementById('modal-title').textContent = 'Tambah Petugas Pelayanan';
    document.getElementById('form-action').value       = 'add';
    document.getElementById('form-id').value           = '';
    document.getElementById('form-divisi').value       = '';
    document.getElementById('form-submit').textContent = 'Tambah Petugas';

    // Tampilkan dropdown jemaat umum, sembunyikan nama readonly
    document.getElementById('field-jemaat-umum').classList.remove('hidden');
    document.getElementById('field-nama-readonly').classList.add('hidden');
    const sel = document.getElementById('form-jemaat-umum');
    if (sel) sel.value = '';

    overlay.classList.replace('hidden', 'flex');
}

function openEditModal(data) {
    document.getElementById('modal-title').textContent  = 'Edit Divisi Petugas';
    document.getElementById('form-action').value        = 'edit';
    document.getElementById('form-id').value            = data.id_jemaat;
    document.getElementById('form-divisi').value        = data.id_divisi;
    document.getElementById('form-submit').textContent  = 'Simpan Perubahan';

    // Sembunyikan dropdown, tampilkan nama readonly
    document.getElementById('field-jemaat-umum').classList.add('hidden');
    document.getElementById('field-nama-readonly').classList.remove('hidden');
    document.getElementById('display-nama').value = data.nama_lengkap;

    overlay.classList.replace('hidden', 'flex');
}

function closeModal() {
    overlay.classList.replace('flex', 'hidden');
}

overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
