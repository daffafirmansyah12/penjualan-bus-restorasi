<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$tipe = $_GET['tipe'] ?? '';
$id   = (int)($_GET['id'] ?? 0);
$uid  = $_SESSION['user_id'];

if ($tipe === 'pesanan') {
    $t = db_row("SELECT p.*, b.nama AS item, u.nama AS pelanggan, u.email, u.no_hp FROM pesanan p
                 JOIN bus b ON b.id = p.bus_id JOIN users u ON u.id = p.user_id WHERE p.id = ?", "i", [$id]);
    $total = $t ? (float)$t['total_harga'] : 0;
    $nomor = sprintf("INV-P-%04d", $id);
    $uraian = $t ? $t['item'] . " x " . (int)$t['jumlah'] . " unit (" . $t['warna'] . ")" : '';
} elseif ($tipe === 'restorasi') {
    $t = db_row("SELECT r.*, l.nama_layanan AS item, u.nama AS pelanggan, u.email, u.no_hp FROM pengajuan_restorasi r
                 JOIN layanan_restorasi l ON l.id = r.layanan_id JOIN users u ON u.id = r.user_id WHERE r.id = ?", "i", [$id]);
    $total = ($t && $t['estimasi_biaya'] !== null) ? (float)$t['estimasi_biaya'] : 0;
    $nomor = sprintf("INV-R-%04d", $id);
    $uraian = $t ? $t['item'] . " - " . $t['jenis_bus'] : '';
} else {
    $t = null;
}

if (!$t || ($t['user_id'] != $uid && !is_admin())) {
    http_response_code(404);
    die("Invoice tidak ditemukan.");
}

$pembayaran = db_all("SELECT * FROM pembayaran WHERE tipe = ? AND referensi_id = ? AND status_verifikasi = 'diterima' ORDER BY id", "si", [$tipe, $id]);
$terbayar = 0;
foreach ($pembayaran as $p) $terbayar += $p['jumlah_bayar'];
$sisa = max(0, $total - $terbayar);
$tanggal = $tipe === 'pesanan' ? $t['tanggal'] : $t['created_at'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice <?= e($nomor) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:760px">
    <div class="d-print-none mb-3 d-flex justify-content-between">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">&laquo; Kembali</a>
        <button onclick="window.print()" class="btn btn-dark btn-sm">Cetak / Simpan PDF</button>
    </div>

    <div class="card shadow-sm"><div class="card-body p-4">
        <div class="d-flex justify-content-between mb-4">
            <div><h3 class="mb-0"><?= e(APP_NAME) ?></h3><small class="text-muted">Penjualan Bus dan Jasa Restorasi Body Bus</small></div>
            <div class="text-end"><h4 class="mb-0">INVOICE</h4><div><?= e($nomor) ?></div><small><?= tgl($tanggal, false) ?></small></div>
        </div>

        <div class="mb-4">
            <div class="text-muted small">Ditagihkan kepada</div>
            <strong><?= e($t['pelanggan']) ?></strong><br>
            <?= e($t['email']) ?><br><?= e($t['no_hp']) ?>
        </div>

        <table class="table table-bordered">
            <thead class="table-light"><tr><th>Uraian</th><th class="text-end" style="width:200px">Jumlah</th></tr></thead>
            <tbody><tr><td><?= e($uraian) ?></td><td class="text-end"><?= $total > 0 ? rupiah($total) : 'Belum ditentukan' ?></td></tr></tbody>
        </table>

        <?php if ($pembayaran): ?>
            <h6>Pembayaran diterima</h6>
            <table class="table table-sm">
                <?php foreach ($pembayaran as $p): ?>
                    <tr><td><?= tgl($p['tanggal']) ?></td><td class="text-end"><?= rupiah($p['jumlah_bayar']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <table class="table table-borderless w-auto ms-auto mb-0">
            <tr><td>Total</td><td class="text-end"><?= rupiah($total) ?></td></tr>
            <tr><td>Terbayar</td><td class="text-end"><?= rupiah($terbayar) ?></td></tr>
            <tr class="fw-bold fs-5"><td>Sisa tagihan</td><td class="text-end"><?= rupiah($sisa) ?></td></tr>
        </table>
        <p class="small text-muted mt-4 mb-0">Pembayaran ke: <?= e(REKENING) ?></p>
    </div></div>
</div>
</body>
</html>
