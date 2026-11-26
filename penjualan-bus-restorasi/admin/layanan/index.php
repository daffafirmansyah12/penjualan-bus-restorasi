<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

if (isset($_POST['hapus'])) {
    try {
        db_exec("DELETE FROM layanan_restorasi WHERE id = ?", "i", [(int)$_POST['id']]);
        set_flash("Layanan dihapus.");
    } catch (mysqli_sql_exception $ex) {
        set_flash("Layanan tidak bisa dihapus karena sudah dipakai pada pengajuan restorasi.", "danger");
    }
    redirect('/admin/layanan/index.php');
}

$layanan = db_all("SELECT * FROM layanan_restorasi ORDER BY id");

$judul = "Kelola Layanan Restorasi";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'layanan';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Layanan Restorasi</h3>
    <a href="<?= BASE_URL ?>/admin/layanan/form.php" class="btn btn-dark">+ Tambah Layanan</a>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th>Layanan</th><th>Deskripsi</th><th>Harga mulai</th><th>Estimasi</th><th style="width:150px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($layanan as $l): ?>
        <tr>
            <td><?= e($l['nama_layanan']) ?></td>
            <td><?= e($l['deskripsi']) ?></td>
            <td><?= rupiah($l['harga_mulai']) ?></td>
            <td><?= (int)$l['estimasi_hari'] ?> hari</td>
            <td>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/admin/layanan/form.php?id=<?= $l['id'] ?>">Edit</a>
                    <form method="POST" onsubmit="return confirm('Hapus layanan ini?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                        <button name="hapus" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
