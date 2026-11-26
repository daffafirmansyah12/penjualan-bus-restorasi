<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$edit = $id > 0;
$l    = $edit ? db_row("SELECT * FROM layanan_restorasi WHERE id = ?", "i", [$id]) : null;
if ($edit && !$l) {
    set_flash("Layanan tidak ditemukan.", "danger");
    redirect('/admin/layanan/index.php');
}
$error = "";

if (isset($_POST['simpan'])) {
    $nama  = trim($_POST['nama_layanan'] ?? '');
    $desk  = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga_mulai'] ?? 0);
    $hari  = (int)($_POST['estimasi_hari'] ?? 0);

    if ($nama === '') {
        $error = "Nama layanan wajib diisi.";
    } elseif ($harga < 0) {
        $error = "Harga tidak boleh negatif.";
    } elseif ($hari < 1) {
        $error = "Estimasi minimal 1 hari.";
    } else {
        if ($edit) {
            db_exec("UPDATE layanan_restorasi SET nama_layanan=?, deskripsi=?, harga_mulai=?, estimasi_hari=? WHERE id=?",
                    "ssdii", [$nama, $desk, $harga, $hari, $id]);
        } else {
            db_exec("INSERT INTO layanan_restorasi (nama_layanan, deskripsi, harga_mulai, estimasi_hari) VALUES (?,?,?,?)",
                    "ssdi", [$nama, $desk, $harga, $hari]);
        }
        set_flash("Layanan berhasil disimpan.");
        redirect('/admin/layanan/index.php');
    }
    $l = ['nama_layanan' => $nama, 'deskripsi' => $desk, 'harga_mulai' => $harga, 'estimasi_hari' => $hari];
}

$judul = $edit ? "Edit Layanan" : "Tambah Layanan";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'layanan';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3"><?= e($judul) ?></h3>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="POST" class="card card-body" style="max-width:720px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="mb-3"><label class="form-label">Nama layanan</label>
        <input type="text" name="nama_layanan" class="form-control" value="<?= e($l['nama_layanan'] ?? '') ?>" required></div>
    <div class="mb-3"><label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" rows="3" class="form-control"><?= e($l['deskripsi'] ?? '') ?></textarea></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Harga mulai dari (Rp)</label>
            <input type="number" name="harga_mulai" min="0" class="form-control" value="<?= e((int)($l['harga_mulai'] ?? 0)) ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Estimasi pengerjaan (hari)</label>
            <input type="number" name="estimasi_hari" min="1" class="form-control" value="<?= e($l['estimasi_hari'] ?? 7) ?>" required></div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" name="simpan" class="btn btn-dark">Simpan</button>
        <a href="<?= BASE_URL ?>/admin/layanan/index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
