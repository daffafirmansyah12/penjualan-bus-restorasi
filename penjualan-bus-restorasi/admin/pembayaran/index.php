<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

if (isset($_POST['terima']) || isset($_POST['tolak'])) {
    $id   = (int)($_POST['id'] ?? 0);
    $baru = isset($_POST['terima']) ? 'diterima' : 'ditolak';
    $p = db_row("SELECT * FROM pembayaran WHERE id = ?", "i", [$id]);
    if ($p && $p['status_verifikasi'] === 'menunggu') {
        db_exec("UPDATE pembayaran SET status_verifikasi = ? WHERE id = ?", "si", [$baru, $id]);
        // Pembayaran pesanan diterima -> pesanan otomatis dikonfirmasi
        if ($baru === 'diterima' && $p['tipe'] === 'pesanan') {
            db_exec("UPDATE pesanan SET status = 'dikonfirmasi' WHERE id = ? AND status = 'menunggu'", "i", [$p['referensi_id']]);
        }
        set_flash("Pembayaran #$id " . $baru . ".");
    } else {
        set_flash("Pembayaran sudah diproses sebelumnya.", "warning");
    }
    redirect('/admin/pembayaran/index.php' . (!empty($_POST['filter']) ? '?status=' . urlencode($_POST['filter']) : ''));
}

$status_valid = ['menunggu', 'diterima', 'ditolak'];
$filter = $_GET['status'] ?? '';
$sql = "SELECT p.*, u.nama AS pelanggan
        FROM pembayaran p
        LEFT JOIN pesanan ps ON p.tipe = 'pesanan' AND ps.id = p.referensi_id
        LEFT JOIN pengajuan_restorasi r ON p.tipe = 'restorasi' AND r.id = p.referensi_id
        LEFT JOIN users u ON u.id = COALESCE(ps.user_id, r.user_id)";
if (in_array($filter, $status_valid, true)) {
    $daftar = db_all($sql . " WHERE p.status_verifikasi = ? ORDER BY p.id DESC", "s", [$filter]);
} else {
    $filter = '';
    $daftar = db_all($sql . " ORDER BY p.id DESC");
}

$judul = "Verifikasi Pembayaran";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'pembayaran';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Verifikasi Pembayaran</h3>
    <form method="GET">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <?php foreach ($status_valid as $s): ?>
                <option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Untuk</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th style="width:170px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= tgl($p['tanggal']) ?></td>
            <td><?= e($p['pelanggan']) ?></td>
            <td><?= $p['tipe'] === 'pesanan' ? 'Pesanan' : 'Restorasi' ?> #<?= (int)$p['referensi_id'] ?></td>
            <td><?= rupiah($p['jumlah_bayar']) ?></td>
            <td><?php if ($p['bukti_bayar']): ?><a href="<?= e(img_url('bukti_bayar', $p['bukti_bayar'])) ?>" target="_blank">Lihat bukti</a><?php endif; ?></td>
            <td><?= status_badge($p['status_verifikasi']) ?></td>
            <td>
                <?php if ($p['status_verifikasi'] === 'menunggu'): ?>
                    <form method="POST" class="d-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                        <button name="terima" class="btn btn-sm btn-success">Terima</button>
                        <button name="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="8" class="text-center text-muted">Tidak ada pembayaran.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
