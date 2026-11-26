<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

function hitung($sql, $types = '', $params = []) {
    $r = db_row($sql, $types, $params);
    return $r ? (float)array_values($r)[0] : 0;
}

$jml_pelanggan = (int)hitung("SELECT COUNT(*) FROM users WHERE role = 'pelanggan'");
$jml_bus       = (int)hitung("SELECT COUNT(*) FROM bus");
$jml_pesanan   = (int)hitung("SELECT COUNT(*) FROM pesanan");
$jml_restorasi = (int)hitung("SELECT COUNT(*) FROM pengajuan_restorasi");
$pendapatan    = hitung("SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE status_verifikasi = 'diterima'");

$tunggu_pesanan = (int)hitung("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'");
$tunggu_survei  = (int)hitung("SELECT COUNT(*) FROM pengajuan_restorasi WHERE status = 'menunggu_survei'");
$tunggu_bayar   = (int)hitung("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'menunggu'");

$terbaru = db_all("SELECT p.id, p.tanggal, p.total_harga, p.status, u.nama AS pelanggan, b.nama AS bus
                   FROM pesanan p JOIN users u ON u.id = p.user_id JOIN bus b ON b.id = p.bus_id
                   ORDER BY p.id DESC LIMIT 5");

$judul = "Dashboard Admin";
require_once __DIR__ . '/../includes/header.php';
$menu_aktif = 'dashboard';
require_once __DIR__ . '/../includes/admin_menu.php';
?>

<h3 class="mb-4">Dashboard Admin</h3>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg"><div class="card text-bg-primary"><div class="card-body"><div class="small">Pelanggan</div><div class="fs-3 fw-bold"><?= $jml_pelanggan ?></div></div></div></div>
    <div class="col-6 col-lg"><div class="card text-bg-success"><div class="card-body"><div class="small">Unit Bus (jenis)</div><div class="fs-3 fw-bold"><?= $jml_bus ?></div></div></div></div>
    <div class="col-6 col-lg"><div class="card text-bg-warning"><div class="card-body"><div class="small">Pesanan</div><div class="fs-3 fw-bold"><?= $jml_pesanan ?></div></div></div></div>
    <div class="col-6 col-lg"><div class="card text-bg-danger"><div class="card-body"><div class="small">Pengajuan Restorasi</div><div class="fs-3 fw-bold"><?= $jml_restorasi ?></div></div></div></div>
    <div class="col-12 col-lg"><div class="card text-bg-dark"><div class="card-body"><div class="small">Pendapatan (terverifikasi)</div><div class="fs-5 fw-bold"><?= rupiah($pendapatan) ?></div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><a class="text-decoration-none" href="<?= BASE_URL ?>/admin/pesanan/index.php?status=menunggu"><div class="card border-warning"><div class="card-body">Pesanan menunggu konfirmasi: <strong><?= $tunggu_pesanan ?></strong></div></div></a></div>
    <div class="col-md-4"><a class="text-decoration-none" href="<?= BASE_URL ?>/admin/restorasi/index.php?status=menunggu_survei"><div class="card border-warning"><div class="card-body">Restorasi menunggu survei: <strong><?= $tunggu_survei ?></strong></div></div></a></div>
    <div class="col-md-4"><a class="text-decoration-none" href="<?= BASE_URL ?>/admin/pembayaran/index.php?status=menunggu"><div class="card border-warning"><div class="card-body">Pembayaran menunggu verifikasi: <strong><?= $tunggu_bayar ?></strong></div></div></a></div>
</div>

<h5>Pesanan Terbaru</h5>
<?php if (!$terbaru): ?>
    <p class="text-muted">Belum ada pesanan.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Bus</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($terbaru as $p): ?>
        <tr><td><?= $p['id'] ?></td><td><?= tgl($p['tanggal']) ?></td><td><?= e($p['pelanggan']) ?></td><td><?= e($p['bus']) ?></td><td><?= rupiah($p['total_harga']) ?></td><td><?= status_badge($p['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
