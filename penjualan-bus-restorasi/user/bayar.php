<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid  = $_SESSION['user_id'];
$tipe = $_GET['tipe'] ?? $_POST['tipe'] ?? '';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($tipe === 'pesanan') {
    $t = db_row("SELECT p.*, b.nama AS item FROM pesanan p JOIN bus b ON b.id = p.bus_id WHERE p.id = ? AND p.user_id = ?", "ii", [$id, $uid]);
    $total     = $t ? (float)$t['total_harga'] : 0;
    $bisa      = $t && $t['status'] !== 'batal';
    $alasan    = "Pesanan ini sudah dibatalkan.";
    $judul_tag = "Pesanan #$id";
    $kembali   = '/user/riwayat.php';
} elseif ($tipe === 'restorasi') {
    $t = db_row("SELECT r.*, l.nama_layanan AS item FROM pengajuan_restorasi r JOIN layanan_restorasi l ON l.id = r.layanan_id WHERE r.id = ? AND r.user_id = ?", "ii", [$id, $uid]);
    $total     = ($t && $t['estimasi_biaya'] !== null) ? (float)$t['estimasi_biaya'] : 0;
    $bisa      = $t && $t['estimasi_biaya'] !== null && in_array($t['status'], ['disetujui', 'dikerjakan', 'selesai'], true);
    $alasan    = "Pembayaran restorasi dibuka setelah estimasi biaya kamu setujui.";
    $judul_tag = "Restorasi #$id";
    $kembali   = '/user/restorasi_riwayat.php';
} else {
    redirect('/index.php');
}

if (!$t) {
    set_flash("Data tagihan tidak ditemukan.", "danger");
    redirect('/index.php');
}

$diterima = (float)db_row("SELECT COALESCE(SUM(jumlah_bayar),0) AS s FROM pembayaran WHERE tipe = ? AND referensi_id = ? AND status_verifikasi = 'diterima'", "si", [$tipe, $id])['s'];
$menunggu = (float)db_row("SELECT COALESCE(SUM(jumlah_bayar),0) AS s FROM pembayaran WHERE tipe = ? AND referensi_id = ? AND status_verifikasi = 'menunggu'", "si", [$tipe, $id])['s'];
$sisa = $total - $diterima - $menunggu;
$error = "";

if (isset($_POST['bayar'])) {
    $jumlah = (float)($_POST['jumlah_bayar'] ?? 0);
    if (!$bisa) {
        $error = $alasan;
    } elseif ($jumlah <= 0) {
        $error = "Jumlah pembayaran harus lebih dari 0.";
    } elseif ($jumlah > $sisa) {
        $error = "Jumlah melebihi sisa tagihan (" . rupiah($sisa) . ").";
    } else {
        $bukti = upload_gambar($_FILES['bukti'] ?? [], 'bukti_bayar', $error);
        if ($bukti === null) {
            $error = "Bukti pembayaran wajib diunggah.";
        } elseif ($bukti !== false) {
            db_exec("INSERT INTO pembayaran (tipe, referensi_id, jumlah_bayar, bukti_bayar) VALUES (?, ?, ?, ?)",
                    "sids", [$tipe, $id, $jumlah, $bukti]);
            set_flash("Bukti pembayaran terkirim. Menunggu verifikasi admin.");
            redirect('/user/bayar.php?tipe=' . $tipe . '&id=' . $id);
        }
    }
}

$riwayat = db_all("SELECT * FROM pembayaran WHERE tipe = ? AND referensi_id = ? ORDER BY id DESC", "si", [$tipe, $id]);

$judul = "Pembayaran";
require_once __DIR__ . '/../includes/header.php';
?>

<nav class="small mb-3"><a href="<?= BASE_URL . $kembali ?>">&laquo; Kembali</a></nav>
<h3 class="mb-3">Pembayaran: <?= e($judul_tag) ?></h3>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= e($t['item']) ?></h5>
                <table class="table table-sm mb-0">
                    <tr><td>Total tagihan</td><td class="text-end fw-bold"><?= $total > 0 ? rupiah($total) : 'Belum ditentukan' ?></td></tr>
                    <tr><td>Sudah diverifikasi</td><td class="text-end"><?= rupiah($diterima) ?></td></tr>
                    <tr><td>Menunggu verifikasi</td><td class="text-end"><?= rupiah($menunggu) ?></td></tr>
                    <tr class="table-light"><td>Sisa yang belum dibayar</td><td class="text-end fw-bold"><?= rupiah(max(0, $sisa)) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6>Transfer ke:</h6>
                <p class="mb-0 fs-5"><?= e(REKENING) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Upload Bukti Pembayaran</h5>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

                <?php if (!$bisa): ?>
                    <div class="alert alert-warning mb-0"><?= e($alasan) ?></div>
                <?php elseif ($sisa <= 0): ?>
                    <div class="alert alert-success mb-0">Tagihan sudah lunas atau seluruhnya dalam proses verifikasi.</div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tipe" value="<?= e($tipe) ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="mb-3">
                            <label class="form-label">Jumlah yang dibayar (Rp)</label>
                            <input type="number" name="jumlah_bayar" class="form-control" min="1" max="<?= (int)$sisa ?>" value="<?= (int)$sisa ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bukti transfer (JPG/PNG/WEBP, maks 2 MB)</label>
                            <input type="file" name="bukti" class="form-control" accept="image/*" required>
                        </div>
                        <button type="submit" name="bayar" class="btn btn-warning">Kirim Bukti</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <h5>Riwayat Pembayaran</h5>
        <?php if (!$riwayat): ?>
            <p class="text-muted">Belum ada pembayaran.</p>
        <?php else: ?>
            <table class="table table-bordered align-middle">
                <thead class="table-light"><tr><th>Tanggal</th><th>Jumlah</th><th>Bukti</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= tgl($r['tanggal']) ?></td>
                        <td><?= rupiah($r['jumlah_bayar']) ?></td>
                        <td><a href="<?= e(img_url('bukti_bayar', $r['bukti_bayar'])) ?>" target="_blank">Lihat</a></td>
                        <td><?= status_badge($r['status_verifikasi']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
