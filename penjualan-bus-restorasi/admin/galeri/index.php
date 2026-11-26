<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

if (isset($_POST['tambah'])) {
    $judul_g = trim($_POST['judul'] ?? '');
    $desk    = trim($_POST['deskripsi'] ?? '');
    if ($judul_g === '') {
        set_flash("Judul wajib diisi.", "danger");
    } else {
        $err = "";
        $before = upload_gambar($_FILES['foto_before'] ?? [], 'restorasi', $err);
        $after  = $before === false ? false : upload_gambar($_FILES['foto_after'] ?? [], 'restorasi', $err);
        if ($before === false || $after === false) {
            if ($before) hapus_file('restorasi', $before);
            set_flash($err, "danger");
        } else {
            db_exec("INSERT INTO galeri_restorasi (judul, foto_before, foto_after, deskripsi) VALUES (?, ?, ?, ?)",
                    "ssss", [$judul_g, $before, $after, $desk]);
            set_flash("Galeri ditambahkan.");
        }
    }
    redirect('/admin/galeri/index.php');
}

if (isset($_POST['hapus'])) {
    $g = db_row("SELECT * FROM galeri_restorasi WHERE id = ?", "i", [(int)($_POST['id'] ?? 0)]);
    if ($g) {
        db_exec("DELETE FROM galeri_restorasi WHERE id = ?", "i", [$g['id']]);
        hapus_file('restorasi', $g['foto_before']);
        hapus_file('restorasi', $g['foto_after']);
        set_flash("Galeri dihapus.");
    }
    redirect('/admin/galeri/index.php');
}

$galeri = db_all("SELECT * FROM galeri_restorasi ORDER BY id DESC");

$judul = "Kelola Galeri";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'galeri';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3">Galeri Before - After</h3>

<form method="POST" enctype="multipart/form-data" class="card card-body mb-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Judul</label><input type="text" name="judul" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Deskripsi</label><input type="text" name="deskripsi" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Foto before</label><input type="file" name="foto_before" class="form-control" accept="image/*"></div>
        <div class="col-md-6"><label class="form-label">Foto after</label><input type="file" name="foto_after" class="form-control" accept="image/*"></div>
    </div>
    <button name="tambah" class="btn btn-dark mt-3 align-self-start">Tambah ke Galeri</button>
</form>

<div class="row g-3">
<?php foreach ($galeri as $g): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="row g-0">
                <div class="col-6"><?= tampil_gambar('restorasi', $g['foto_before'], 'Before', 140) ?></div>
                <div class="col-6"><?= tampil_gambar('restorasi', $g['foto_after'], 'After', 140) ?></div>
            </div>
            <div class="card-body d-flex justify-content-between align-items-start">
                <div><strong><?= e($g['judul']) ?></strong><br><small class="text-muted"><?= e($g['deskripsi']) ?></small></div>
                <form method="POST" onsubmit="return confirm('Hapus item galeri ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                    <button name="hapus" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
