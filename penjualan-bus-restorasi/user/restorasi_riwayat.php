<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$daftar = db_all(
    "SELECT r.*, l.nama_layanan FROM pengajuan_restorasi r
     JOIN layanan_restorasi l ON l.id = r.layanan_id
     WHERE r.user_id = ? ORDER BY r.id DESC", "i", [$_SESSION['user_id']]);

$judul = "Restorasi Saya";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Restorasi Saya</h3>
    <a href="<?= BASE_URL ?>/user/restorasi_ajukan.php" class="btn btn-warning btn-sm">+ Ajukan Restorasi</a>
</div>

<?php if (!$daftar): ?>
    <div class="alert alert-secondary">Belum ada pengajuan restorasi.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Tanggal</th><th>Layanan</th><th>Bus</th><th>Jadwal survei</th><th>Estimasi biaya</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= tgl($r['created_at'], false) ?></td>
                <td><?= e($r['nama_layanan']) ?></td>
                <td><?= e($r['jenis_bus']) ?><?= $r['tahun'] ? ' (' . (int)$r['tahun'] . ')' : '' ?></td>
                <td><?= tgl($r['jadwal'], false) ?></td>
                <td><?= $r['estimasi_biaya'] !== null ? rupiah($r['estimasi_biaya']) : '<span class="text-muted">Belum ada</span>' ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><a class="btn btn-dark btn-sm" href="<?= BASE_URL ?>/user/restorasi_detail.php?id=<?= $r['id'] ?>">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
