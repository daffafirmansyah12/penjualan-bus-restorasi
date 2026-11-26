<?php
require_once __DIR__ . '/includes/auth_check.php';

$judul = "Beranda";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero p-5 mb-4 text-center">
    <h1 class="display-5 fw-bold">Penjualan Bus dan Jasa Restorasi Body Bus</h1>
    <p class="lead mb-4">Pesan bus baru sesuai kebutuhanmu, atau percayakan restorasi body bus kepada kami.</p>
    <a href="<?= BASE_URL ?>/pages/katalog.php" class="btn btn-warning btn-lg me-2">Lihat Katalog Bus</a>
    <a href="<?= BASE_URL ?>/pages/layanan.php" class="btn btn-outline-light btn-lg">Jasa Restorasi</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card card-layanan h-100">
            <div class="card-body p-4">
                <h4 class="card-title">Penjualan Bus</h4>
                <p class="card-text">Pilih unit dari berbagai kategori, atur warna dan fasilitas, lalu pesan dan bayar DP secara online.</p>
                <a href="<?= BASE_URL ?>/pages/katalog.php" class="btn btn-dark">Lihat Katalog</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-layanan h-100">
            <div class="card-body p-4">
                <h4 class="card-title">Jasa Restorasi Body Bus</h4>
                <p class="card-text">Ajukan restorasi, terima estimasi biaya, dan pantau progres pengerjaan bus-mu langsung dari website.</p>
                <a href="<?= BASE_URL ?>/pages/layanan.php" class="btn btn-dark">Lihat Layanan</a>
            </div>
        </div>
    </div>
</div>

<?php if (!is_login()): ?>
    <div class="alert alert-secondary text-center">
        Ingin memesan? <a href="<?= BASE_URL ?>/auth/register.php">Daftar</a> atau
        <a href="<?= BASE_URL ?>/auth/login.php">login</a> terlebih dahulu.
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
