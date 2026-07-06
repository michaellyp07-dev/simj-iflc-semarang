<?php
/**
 * admin/jenis_kegiatan.php — Master Data Jenis Kegiatan Dinamis
 * Sistem Informasi Manajemen Jemaat IFLC
 */
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';

$pageTitle  = 'Master Jenis Kegiatan';
$activePage = 'jenis_kegiatan';
$pdo = db();

$msg     = '';
$msgType = '';

// ── POST HANDLERS ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Tambah ──
    if ($action === 'add') {
        $nama = trim($_POST['nama_jenis'] ?? '');
        $desk = trim($_POST['deskripsi']  ?? '') ?: null;

        if ($nama === '') {
            $msg = 'Nama jenis kegiatan wajib diisi.';
            $msgType = 'error';
        } else {
            try {
                $pdo->prepare('INSERT INTO jenis_kegiatan (nama_jenis, deskripsi) VALUES (?, ?)')
                    ->execute([$nama, $desk]);
                $msg = "Jenis kegiatan \"{$nama}\" berhasil ditambahkan.";
                $msgType = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $msg = "Jenis kegiatan \"{$nama}\" sudah ada.";
                    $msgType = 'error';
                } else {
                    throw $e;
                }
            }
        }

    // ── Edit ──
    } elseif ($action === 'edit') {
        $id   = (int)($_POST['id']          ?? 0);
        $nama = trim($_POST['nama_jenis']   ?? '');
        $desk = trim($_POST['deskripsi']    ?? '') ?: null;

        if (!$id || $nama === '') {
            $msg = 'Data tidak lengkap.'; $msgType = 'error';
        } else {
            try {
                $pdo->prepare('UPDATE jenis_kegiatan SET nama_jenis=?, deskripsi=? WHERE id=?')
                    ->execute([$nama, $desk, $id]);
                $msg = 'Jenis kegiatan berhasil diperbarui.'; $msgType = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $msg = "Nama \"{$nama}\" sudah digunakan."; $msgType = 'error';
                } else { throw $e; }
            }
        }

    // ── Toggle Aktif ──
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('UPDATE jenis_kegiatan SET is_aktif = NOT is_aktif WHERE id = ?')->execute([$id]);
            $msg = 'Status berhasil diubah.'; $msgType = 'success';
        }

    // ── Hapus ──
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $row = $pdo->prepare('SELECT nama_jenis FROM jenis_kegiatan WHERE id=?');
            $row->execute([$id]);
            $nama = $row->fetchColumn();
            $pdo->prepare('DELETE FROM jenis_kegiatan WHERE id=?')->execute([$id]);
            $msg = "Jenis kegiatan \"{$nama}\" berhasil dihapus."; $msgType = 'success';
        }
    }
}

// ── FETCH DATA ────────────────────────────────────────────
$list = $pdo->query('SELECT * FROM jenis_kegiatan ORDER BY is_aktif DESC, nama_jenis ASC')->fetchAll();

$totalAktif   = count(array_filter($list, fn($r) => $r['is_aktif']));
$totalNonaktif = count(array_filter($list, fn($r) => !$r['is_aktif']));

// Jenis tetap (hardcode) — tidak bisa diubah via halaman ini
$jenisTetap = ['Ibadah Raya', 'Komsel', 'Ibadah Natal', 'Ibadah Paskah', 'KKR'];

include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-black text-zinc-900 tracking-tight">Master Jenis Kegiatan</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Kelola jenis kegiatan tambahan di luar jenis tetap sistem
        </p>
    </div>
    <button onclick="openAddModal()" class="btn-primary flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Jenis Kegiatan
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

<!-- SUMMARY CARDS -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <!-- Jenis Tetap -->
    <div class="rounded-xl p-4 bg-white border border-slate-200 border-l-4 border-l-red-600 shadow-sm transition-transform hover:-translate-y-1">
        <div class="text-2xl mb-2 opacity-80 grayscale">🔒</div>
        <div class="text-3xl font-black text-zinc-900"><?= count($jenisTetap) ?></div>
        <div class="text-xs font-bold text-zinc-500 mt-1 tracking-wide uppercase">Jenis Tetap</div>
        <div class="text-[10px] text-zinc-400 mt-0.5">Sudah bawaan sistem</div>
    </div>
    <!-- Jenis Aktif -->
    <div class="rounded-xl p-4 bg-white border border-slate-200 border-l-4 border-l-red-600 shadow-sm transition-transform hover:-translate-y-1 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-full"></div>
        <div class="text-2xl mb-2 opacity-80 grayscale relative z-10">✅</div>
        <div class="text-3xl font-black text-zinc-900 relative z-10"><?= $totalAktif ?></div>
        <div class="text-xs font-bold text-zinc-500 mt-1 tracking-wide uppercase relative z-10">Jenis Dinamis Aktif</div>
        <div class="text-[10px] text-zinc-400 mt-0.5 relative z-10">Muncul di dropdown jadwal</div>
    </div>
    <!-- Nonaktif -->
    <div class="rounded-xl p-4 bg-slate-50 border border-slate-200 border-l-4 border-l-slate-400 shadow-sm transition-transform hover:-translate-y-1">
        <div class="text-2xl mb-2 opacity-40 grayscale">🔕</div>
        <div class="text-3xl font-black text-zinc-400"><?= $totalNonaktif ?></div>
        <div class="text-xs font-bold text-zinc-400 mt-1 tracking-wide uppercase">Nonaktif</div>
        <div class="text-[10px] text-zinc-400 mt-0.5">Disembunyikan dari dropdown</div>
    </div>
</div>

<!-- ═══ JENIS TETAP (informasi) ═══ -->
<div class="glass-card rounded-2xl overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-slate-700/60 flex items-center gap-2">
        <span class="text-base">🔒</span>
        <div>
            <h3 class="text-sm font-black text-zinc-900">Jenis Kegiatan Tetap</h3>
            <p class="text-xs text-slate-500">Bawaan sistem — tidak dapat diubah atau dihapus</p>
        </div>
    </div>
    <div class="p-4">
        <div class="flex flex-wrap gap-2">
            <?php foreach ($jenisTetap as $jt): ?>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/40">
                <div class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></div>
                <span class="text-sm font-semibold text-zinc-800"><?= htmlspecialchars($jt) ?></span>
                <span class="badge badge-purple text-[10px]">Tetap</span>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-[11px] text-slate-600 mt-3 italic">
            * Untuk menambah kegiatan di luar daftar di atas, gunakan tombol "Tambah Jenis Kegiatan" di halaman ini.
        </p>
    </div>
</div>

<!-- ═══ JENIS DINAMIS (CRUD) ═══ -->
<div class="glass-card rounded-2xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-700/60 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-base">✏️</span>
            <div>
                <h3 class="text-sm font-black text-zinc-900">Jenis Kegiatan Dinamis</h3>
                <p class="text-xs text-slate-500"><?= count($list) ?> jenis terdaftar</p>
            </div>
        </div>
        <?php if ($totalNonaktif > 0): ?>
            <span class="badge badge-gray"><?= $totalNonaktif ?> nonaktif</span>
        <?php endif; ?>
    </div>

    <?php if (empty($list)): ?>
    <div class="p-14 text-center">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-slate-400 font-medium">Belum ada jenis kegiatan dinamis</p>
        <p class="text-xs text-slate-600 mt-1">Klik "Tambah Jenis Kegiatan" untuk mulai menambahkan</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-5 py-3.5 text-left w-10">No</th>
                    <th class="px-5 py-3.5 text-left">Nama Jenis Kegiatan</th>
                    <th class="px-5 py-3.5 text-left">Deskripsi</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $i => $item): ?>
                <tr class="<?= !$item['is_aktif'] ? 'opacity-50' : '' ?>">
                    <td class="px-5 text-slate-500 text-sm"><?= $i + 1 ?></td>

                    <!-- Nama -->
                    <td class="px-5">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full flex-shrink-0
                                <?= $item['is_aktif'] ? 'bg-emerald-400' : 'bg-slate-600' ?>"></div>
                            <span class="font-semibold text-zinc-800">
                                <?= htmlspecialchars($item['nama_jenis']) ?>
                            </span>
                            <span class="badge badge-amber text-[10px]">Dinamis</span>
                        </div>
                    </td>

                    <!-- Deskripsi -->
                    <td class="px-5 text-slate-600 text-sm max-w-[280px]">
                        <?= $item['deskripsi']
                            ? htmlspecialchars($item['deskripsi'])
                            : '<span class="italic text-slate-600">—</span>' ?>
                    </td>

                    <!-- Status -->
                    <td class="px-5 text-center">
                        <?php if ($item['is_aktif']): ?>
                            <span class="badge badge-green">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Nonaktif</span>
                        <?php endif; ?>
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap py-1">
                            <!-- Edit -->
                            <button onclick='openEditModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>)'
                                    class="btn-edit !py-1 !px-2.5 !text-xs">Edit</button>

                            <!-- Toggle Aktif -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit"
                                        class="<?= $item['is_aktif']
                                            ? 'bg-amber-500/15 text-amber-400 border border-amber-500/25 hover:bg-amber-500/28'
                                            : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 hover:bg-emerald-500/28'
                                        ?> !py-1 !px-2.5 !text-xs rounded-lg font-semibold text-xs transition-all cursor-pointer">
                                    <?= $item['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>

                            <!-- Hapus -->
                            <form method="POST" onsubmit="return confirm('Hapus jenis kegiatan ini?\nData jadwal yang sudah menggunakan jenis ini tidak akan terpengaruh.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
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

<!-- ═══ MODAL: Add / Edit ═══ -->
<div id="modal-overlay"
     class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl w-full max-w-md p-6 sm:p-8 animate-fade-in"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-6">
            <h3 id="modal-title" class="text-lg font-black text-zinc-900">Tambah Jenis Kegiatan</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-zinc-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" id="modal-action" value="add">
            <input type="hidden" name="id"     id="modal-id"     value="">

            <div>
                <label class="form-label">Nama Jenis Kegiatan <span class="text-red-400">*</span></label>
                <input type="text" name="nama_jenis" id="modal-nama" class="form-input"
                       placeholder="mis: Ibadah Padang, Retreat, Doa Semalam…"
                       maxlength="100" required />
                <p class="text-[11px] text-slate-600 mt-1">Pastikan tidak duplikat dengan jenis tetap</p>
            </div>

            <div>
                <label class="form-label">Deskripsi <span class="text-slate-500">(opsional)</span></label>
                <textarea name="deskripsi" id="modal-deskripsi" class="form-input !h-24 resize-none"
                          placeholder="Keterangan singkat tentang kegiatan ini…"
                          maxlength="500"></textarea>
            </div>

            <!-- Info: toggle ada di tabel setelah simpan -->
            <div class="flex items-start gap-2 p-3 rounded-lg bg-indigo-500/8 border border-indigo-500/20 text-xs text-slate-400">
                <span>ℹ️</span>
                <span>Jenis kegiatan baru otomatis <strong class="text-indigo-400">Aktif</strong>
                      dan langsung muncul di dropdown jadwal. Kamu bisa nonaktifkan kapan saja dari tabel.</span>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeModal()"
                        class="flex-1 py-2.5 rounded-lg border border-slate-300 text-slate-600
                               hover:border-slate-400 transition-all text-sm font-medium">
                    Batal
                </button>
                <button type="submit" id="modal-submit" class="btn-primary flex-1">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const overlay = document.getElementById('modal-overlay');

function openAddModal() {
    document.getElementById('modal-title').textContent  = 'Tambah Jenis Kegiatan';
    document.getElementById('modal-action').value       = 'add';
    document.getElementById('modal-id').value           = '';
    document.getElementById('modal-nama').value         = '';
    document.getElementById('modal-deskripsi').value    = '';
    document.getElementById('modal-submit').textContent = 'Simpan';
    overlay.classList.replace('hidden', 'flex');
    setTimeout(() => document.getElementById('modal-nama').focus(), 100);
}

function openEditModal(data) {
    document.getElementById('modal-title').textContent  = 'Edit Jenis Kegiatan';
    document.getElementById('modal-action').value       = 'edit';
    document.getElementById('modal-id').value           = data.id;
    document.getElementById('modal-nama').value         = data.nama_jenis;
    document.getElementById('modal-deskripsi').value    = data.deskripsi ?? '';
    document.getElementById('modal-submit').textContent = 'Simpan Perubahan';
    overlay.classList.replace('hidden', 'flex');
    setTimeout(() => document.getElementById('modal-nama').focus(), 100);
}

function closeModal() { overlay.classList.replace('flex', 'hidden'); }
overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });

// Auto dismiss alert
document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4500);
    el.style.transition = 'opacity .5s ease';
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
