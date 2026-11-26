<?php
require_once __DIR__ . '/../includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
$b = db_row("SELECT b.*, k.nama_kategori FROM bus b JOIN kategori k ON k.id = b.kategori_id WHERE b.id = ?", "i", [$id]);
if (!$b) {
    http_response_code(404);
    $judul = "Bus tidak ditemukan";
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-warning">Bus tidak ditemukan. <a href="' . BASE_URL . '/pages/katalog.php">Kembali ke katalog</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$foto = [];
if ($b['gambar']) $foto[] = $b['gambar'];
foreach (db_all("SELECT file_gambar FROM bus_gambar WHERE bus_id = ? ORDER BY id", "i", [$id]) as $g) {
    $foto[] = $g['file_gambar'];
}

$di_wishlist = false;
if (is_login() && !is_admin()) {
    $di_wishlist = (bool)db_row("SELECT id FROM wishlist WHERE user_id = ? AND bus_id = ?", "ii", [$_SESSION['user_id'], $id]);
}

$judul = $b['nama'];
require_once __DIR__ . '/../includes/header.php';
?>

<nav class="small mb-3"><a href="<?= BASE_URL ?>/pages/katalog.php">&laquo; Kembali ke katalog</a></nav>

<div class="row g-4">
    <div class="col-lg-7">
        <?php if (count($foto) > 1): ?>
            <div id="carouselBus" class="carousel slide">
                <div class="carousel-inner rounded overflow-hidden">
                    <?php foreach ($foto as $i => $f): ?>
                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                            <?= tampil_gambar('bus', $f, $b['nama'], 380) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselBus" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselBus" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
            </div>
        <?php else: ?>
            <div class="rounded overflow-hidden"><?= tampil_gambar('bus', $foto[0] ?? null, $b['nama'], 380) ?></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <span class="badge text-bg-secondary mb-2"><?= e($b['nama_kategori']) ?></span>
        <h2><?= e($b['nama']) ?></h2>
        <p class="fs-3 fw-bold text-dark"><?= rupiah($b['harga']) ?></p>
        <ul class="list-unstyled">
            <li>Kapasitas: <strong><?= (int)$b['kapasitas'] ?> kursi</strong></li>
            <li>Stok tersedia: <strong><?= (int)$b['stok'] ?> unit</strong></li>
        </ul>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php if (is_admin()): ?>
                <span class="text-muted small">Anda login sebagai admin.</span>
            <?php elseif ($b['stok'] > 0): ?>
                <a href="<?= BASE_URL ?>/user/pesan.php?bus_id=<?= $b['id'] ?>" class="btn btn-warning btn-lg">Pesan Sekarang</a>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg" disabled>Stok Habis</button>
            <?php endif; ?>

            <?php if (!is_admin()): ?>
                <form method="POST" action="<?= BASE_URL ?>/user/wishlist.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="bus_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="kembali" value="/pages/detail.php?id=<?= $b['id'] ?>">
                    <button class="btn btn-lg <?= $di_wishlist ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <?= $di_wishlist ? '&#9829; Di Wishlist' : '&#9825; Wishlist' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <a href="<?= BASE_URL ?>/pages/bandingkan.php?id[]=<?= $b['id'] ?>" class="small">Bandingkan bus ini dengan bus lain</a>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-md-6">
        <h5>Deskripsi</h5>
        <p><?= nl2br(e($b['deskripsi'])) ?: '<span class="text-muted">-</span>' ?></p>
    </div>
    <div class="col-md-6">
        <h5>Spesifikasi</h5>
        <p><?= nl2br(e($b['spesifikasi'])) ?: '<span class="text-muted">-</span>' ?></p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
