<?php
require_once __DIR__ . '/../includes/auth_check.php';

$layanan = db_all("SELECT * FROM layanan_restorasi ORDER BY id");

$judul = "Jasa Restorasi";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-1">Jasa Restorasi Body Bus</h3>
<p class="text-muted">Pilih layanan, ajukan restorasi, terima estimasi dari kami, lalu pantau progres pengerjaan.</p>

<div class="row g-4 mb-4">
<?php foreach ($layanan as $l): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card card-layanan h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?= e($l['nama_layanan']) ?></h5>
                <p class="card-text"><?= e($l['deskripsi']) ?></p>
                <p class="mb-1 small text-muted">Estimasi pengerjaan: <?= (int)$l['estimasi_hari'] ?> hari</p>
                <p class="fw-bold mb-3">Mulai dari <?= rupiah($l['harga_mulai']) ?></p>
                <div class="mt-auto d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input hitung" type="checkbox" id="l<?= $l['id'] ?>"
                               data-harga="<?= (float)$l['harga_mulai'] ?>" data-hari="<?= (int)$l['estimasi_hari'] ?>">
                        <label class="form-check-label small" for="l<?= $l['id'] ?>">Hitung estimasi</label>
                    </div>
                    <?php if (!is_admin()): ?>
                        <a href="<?= BASE_URL ?>/user/restorasi_ajukan.php?layanan_id=<?= $l['id'] ?>" class="btn btn-dark btn-sm">Ajukan</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="card border-warning">
    <div class="card-body">
        <h5 class="card-title">Kalkulator Estimasi Awal</h5>
        <p class="small text-muted mb-2">Centang layanan di atas untuk melihat gambaran biaya dan waktu. Angka ini hanya perkiraan awal; biaya pasti ditentukan admin setelah survei kondisi bus.</p>
        <div class="row">
            <div class="col-md-6">Perkiraan biaya mulai dari: <strong id="estBiaya">Rp 0</strong></div>
            <div class="col-md-6">Perkiraan waktu: <strong id="estHari">0 hari</strong></div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.hitung').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var biaya = 0, hari = 0;
        document.querySelectorAll('.hitung:checked').forEach(function (c) {
            biaya += parseFloat(c.dataset.harga);
            hari += parseInt(c.dataset.hari, 10);
        });
        document.getElementById('estBiaya').textContent = 'Rp ' + biaya.toLocaleString('id-ID');
        document.getElementById('estHari').textContent = hari + ' hari';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
