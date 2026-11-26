<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = (int)($_POST['bus_id'] ?? 0);
    $kembali = aman_kembali($_POST['kembali'] ?? '', '/user/wishlist.php');

    $ada = db_row("SELECT id FROM wishlist WHERE user_id = ? AND bus_id = ?", "ii", [$uid, $bus_id]);
    if ($ada) {
        db_exec("DELETE FROM wishlist WHERE id = ?", "i", [$ada['id']]);
        set_flash("Bus dihapus dari wishlist.", "secondary");
    } elseif (db_row("SELECT id FROM bus WHERE id = ?", "i", [$bus_id])) {
        db_exec("INSERT INTO wishlist (user_id, bus_id) VALUES (?, ?)", "ii", [$uid, $bus_id]);
        set_flash("Bus ditambahkan ke wishlist.");
    }
    redirect($kembali);
}

$daftar = db_all(
    "SELECT b.*, k.nama_kategori FROM wishlist w
     JOIN bus b ON b.id = w.bus_id JOIN kategori k ON k.id = b.kategori_id
     WHERE w.user_id = ? ORDER BY w.id DESC", "i", [$uid]);

$judul = "Wishlist";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">Wishlist Saya</h3>

<?php if (!$daftar): ?>
    <div class="alert alert-secondary">Wishlist masih kosong. Lihat <a href="<?= BASE_URL ?>/pages/katalog.php">katalog bus</a>.</div>
<?php endif; ?>

<div class="row g-4">
<?php foreach ($daftar as $b): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <?= tampil_gambar('bus', $b['gambar'], $b['nama'], 180) ?>
            <div class="card-body">
                <span class="badge text-bg-secondary mb-2"><?= e($b['nama_kategori']) ?></span>
                <h5><?= e($b['nama']) ?></h5>
                <p class="fw-bold"><?= rupiah($b['harga']) ?></p>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/pages/detail.php?id=<?= $b['id'] ?>" class="btn btn-dark btn-sm">Detail</a>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="bus_id" value="<?= $b['id'] ?>">
                        <input type="hidden" name="kembali" value="/user/wishlist.php">
                        <button class="btn btn-outline-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
