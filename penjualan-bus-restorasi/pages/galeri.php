<?php
require_once __DIR__ . '/../includes/auth_check.php';

$galeri = db_all("SELECT * FROM galeri_restorasi ORDER BY id DESC");

$judul = "Galeri Restorasi";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-1">Galeri Hasil Restorasi</h3>
<p class="text-muted">Perbandingan kondisi bus sebelum dan sesudah direstorasi.</p>

<?php if (!$galeri): ?>
    <div class="alert alert-secondary">Belum ada galeri.</div>
<?php endif; ?>

<div class="row g-4">
<?php foreach ($galeri as $g): ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="row g-0">
                <div class="col-6">
                    <div class="position-relative">
                        <?= tampil_gambar('restorasi', $g['foto_before'], 'Before', 220) ?>
                        <span class="badge text-bg-dark position-absolute top-0 start-0 m-2">Before</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="position-relative">
                        <?= tampil_gambar('restorasi', $g['foto_after'], 'After', 220) ?>
                        <span class="badge text-bg-success position-absolute top-0 start-0 m-2">After</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= e($g['judul']) ?></h5>
                <p class="card-text"><?= nl2br(e($g['deskripsi'])) ?></p>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
