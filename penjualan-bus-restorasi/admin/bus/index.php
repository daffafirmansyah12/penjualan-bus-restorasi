<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

if (isset($_POST['hapus'])) {
    $id  = (int)($_POST['id'] ?? 0);
    $b   = db_row("SELECT gambar FROM bus WHERE id = ?", "i", [$id]);
    $gal = db_all("SELECT file_gambar FROM bus_gambar WHERE bus_id = ?", "i", [$id]);
    try {
        db_exec("DELETE FROM bus WHERE id = ?", "i", [$id]);
        if ($b) hapus_file('bus', $b['gambar']);
        foreach ($gal as $g) hapus_file('bus', $g['file_gambar']);
        set_flash("Bus berhasil dihapus.");
    } catch (mysqli_sql_exception $ex) {
        set_flash("Bus tidak bisa dihapus karena sudah dipakai di pesanan. Ubah stoknya menjadi 0 jika tidak dijual lagi.", "danger");
    }
    redirect('/admin/bus/index.php');
}

$bus = db_all("SELECT b.*, k.nama_kategori FROM bus b JOIN kategori k ON k.id = b.kategori_id ORDER BY b.id DESC");

$judul = "Kelola Bus";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'bus';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Kelola Bus</h3>
    <a href="<?= BASE_URL ?>/admin/bus/form.php" class="btn btn-dark">+ Tambah Bus</a>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th style="width:90px">Foto</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Kursi</th><th>Stok</th><th style="width:150px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($bus as $b): ?>
        <tr>
            <td><?= tampil_gambar('bus', $b['gambar'], $b['nama'], 55) ?></td>
            <td><?= e($b['nama']) ?></td>
            <td><?= e($b['nama_kategori']) ?></td>
            <td><?= rupiah($b['harga']) ?></td>
            <td><?= (int)$b['kapasitas'] ?></td>
            <td><?= (int)$b['stok'] ?></td>
            <td>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/admin/bus/form.php?id=<?= $b['id'] ?>">Edit</a>
                    <form method="POST" onsubmit="return confirm('Hapus bus ini?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button name="hapus" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$bus): ?><tr><td colspan="7" class="text-center text-muted">Belum ada bus.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
