<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

if (isset($_POST['tambah']) || isset($_POST['ubah'])) {
    $nama = trim($_POST['nama_kategori'] ?? '');
    if ($nama === '') {
        set_flash("Nama kategori wajib diisi.", "danger");
    } else {
        try {
            if (isset($_POST['ubah'])) {
                db_exec("UPDATE kategori SET nama_kategori = ? WHERE id = ?", "si", [$nama, (int)$_POST['id']]);
                set_flash("Kategori diperbarui.");
            } else {
                db_exec("INSERT INTO kategori (nama_kategori) VALUES (?)", "s", [$nama]);
                set_flash("Kategori ditambahkan.");
            }
        } catch (mysqli_sql_exception $ex) {
            set_flash("Nama kategori sudah ada.", "danger");
        }
    }
    redirect('/admin/kategori/index.php');
}

if (isset($_POST['hapus'])) {
    try {
        db_exec("DELETE FROM kategori WHERE id = ?", "i", [(int)$_POST['id']]);
        set_flash("Kategori dihapus.");
    } catch (mysqli_sql_exception $ex) {
        set_flash("Kategori tidak bisa dihapus karena masih dipakai oleh bus.", "danger");
    }
    redirect('/admin/kategori/index.php');
}

$kategori = db_all("SELECT k.*, (SELECT COUNT(*) FROM bus WHERE kategori_id = k.id) AS jml_bus FROM kategori k ORDER BY k.nama_kategori");

$judul = "Kelola Kategori";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'kategori';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3">Kelola Kategori</h3>

<form method="POST" class="row g-2 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-5"><input type="text" name="nama_kategori" class="form-control" placeholder="Nama kategori baru" required></div>
    <div class="col-auto"><button name="tambah" class="btn btn-dark">Tambah</button></div>
</form>

<table class="table table-bordered align-middle" style="max-width:720px">
    <thead class="table-light"><tr><th>Nama kategori</th><th>Jumlah bus</th><th style="width:210px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($kategori as $k): ?>
        <tr>
            <td>
                <form method="POST" class="d-flex gap-2" id="u<?= $k['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <input type="text" name="nama_kategori" class="form-control form-control-sm" value="<?= e($k['nama_kategori']) ?>" required>
                    <button name="ubah" class="btn btn-sm btn-outline-dark">Simpan</button>
                </form>
            </td>
            <td><?= (int)$k['jml_bus'] ?></td>
            <td>
                <form method="POST" onsubmit="return confirm('Hapus kategori ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <button name="hapus" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
