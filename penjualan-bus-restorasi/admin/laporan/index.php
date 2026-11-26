<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$valid = fn($d) => DateTime::createFromFormat('Y-m-d', $d) !== false;
$dari   = isset($_GET['dari'])   && $valid($_GET['dari'])   ? $_GET['dari']   : date('Y-m-01');
$sampai = isset($_GET['sampai']) && $valid($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
if ($dari > $sampai) { [$dari, $sampai] = [$sampai, $dari]; }

$pesanan = db_all("SELECT p.*, u.nama AS pelanggan, b.nama AS nama_bus FROM pesanan p
                   JOIN users u ON u.id = p.user_id JOIN bus b ON b.id = p.bus_id
                   WHERE DATE(p.tanggal) BETWEEN ? AND ? ORDER BY p.id", "ss", [$dari, $sampai]);

if (($_GET['export'] ?? '') === 'pesanan') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_pesanan_' . $dari . '_sd_' . $sampai . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Tanggal', 'Pelanggan', 'Bus', 'Jumlah', 'Total', 'Status'], ';', '"', '\\');
    foreach ($pesanan as $p) {
        fputcsv($out, [$p['id'], $p['tanggal'], $p['pelanggan'], $p['nama_bus'], $p['jumlah'], $p['total_harga'], $p['status']], ';', '"', '\\');
    }
    fclose($out);
    exit;
}

$restorasi = db_all("SELECT r.*, u.nama AS pelanggan, l.nama_layanan FROM pengajuan_restorasi r
                     JOIN users u ON u.id = r.user_id JOIN layanan_restorasi l ON l.id = r.layanan_id
                     WHERE DATE(r.created_at) BETWEEN ? AND ? ORDER BY r.id", "ss", [$dari, $sampai]);

$aktif = array_filter($pesanan, fn($p) => $p['status'] !== 'batal');
$nilai_pesanan = array_sum(array_column($aktif, 'total_harga'));
$resto_selesai = count(array_filter($restorasi, fn($r) => $r['status'] === 'selesai'));

$pendapatan = db_all("SELECT tipe, COALESCE(SUM(jumlah_bayar),0) AS total FROM pembayaran
                      WHERE status_verifikasi = 'diterima' AND DATE(tanggal) BETWEEN ? AND ? GROUP BY tipe", "ss", [$dari, $sampai]);
$pend = ['pesanan' => 0, 'restorasi' => 0];
foreach ($pendapatan as $x) $pend[$x['tipe']] = (float)$x['total'];

$judul = "Laporan";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'laporan';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3">Laporan</h3>

<form method="GET" class="row g-2 align-items-end mb-4 d-print-none">
    <div class="col-auto"><label class="form-label small mb-1">Dari</label><input type="date" name="dari" value="<?= e($dari) ?>" class="form-control"></div>
    <div class="col-auto"><label class="form-label small mb-1">Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-dark">Tampilkan</button></div>
    <div class="col-auto"><a class="btn btn-outline-dark" href="?dari=<?= e($dari) ?>&sampai=<?= e($sampai) ?>&export=pesanan">Ekspor CSV Pesanan</a></div>
    <div class="col-auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()">Cetak</button></div>
</form>

<p class="text-muted">Periode: <?= tgl($dari, false) ?> sampai <?= tgl($sampai, false) ?></p>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Pesanan (tidak batal)</div><div class="fs-4 fw-bold"><?= count($aktif) ?></div><div class="small"><?= rupiah($nilai_pesanan) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Pengajuan restorasi</div><div class="fs-4 fw-bold"><?= count($restorasi) ?></div><div class="small"><?= $resto_selesai ?> selesai</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Pendapatan penjualan bus</div><div class="fs-5 fw-bold"><?= rupiah($pend['pesanan']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Pendapatan restorasi</div><div class="fs-5 fw-bold"><?= rupiah($pend['restorasi']) ?></div></div></div></div>
</div>
<p class="small text-muted">Pendapatan dihitung dari pembayaran yang sudah diverifikasi pada periode ini.</p>

<h5>Daftar Pesanan</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-sm align-middle">
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Bus</th><th>Jml</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($pesanan as $p): ?>
        <tr><td><?= $p['id'] ?></td><td><?= tgl($p['tanggal']) ?></td><td><?= e($p['pelanggan']) ?></td><td><?= e($p['nama_bus']) ?></td><td><?= (int)$p['jumlah'] ?></td><td><?= rupiah($p['total_harga']) ?></td><td><?= status_badge($p['status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$pesanan): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada pesanan pada periode ini.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<h5>Daftar Pengajuan Restorasi</h5>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Layanan</th><th>Bus</th><th>Estimasi</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($restorasi as $r): ?>
        <tr><td><?= $r['id'] ?></td><td><?= tgl($r['created_at'], false) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= e($r['nama_layanan']) ?></td><td><?= e($r['jenis_bus']) ?></td><td><?= $r['estimasi_biaya'] !== null ? rupiah($r['estimasi_biaya']) : '-' ?></td><td><?= status_badge($r['status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$restorasi): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada pengajuan pada periode ini.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
