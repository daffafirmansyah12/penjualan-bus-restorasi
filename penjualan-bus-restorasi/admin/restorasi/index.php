<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$status_valid = ['menunggu_survei', 'menunggu_persetujuan', 'disetujui', 'dikerjakan', 'selesai', 'ditolak'];
$filter = $_GET['status'] ?? '';

$sql = "SELECT r.*, u.nama AS pelanggan, l.nama_layanan FROM pengajuan_restorasi r
        JOIN users u ON u.id = r.user_id JOIN layanan_restorasi l ON l.id = r.layanan_id";
if (in_array($filter, $status_valid, true)) {
    $daftar = db_all($sql . " WHERE r.status = ? ORDER BY r.id DESC", "s", [$filter]);
} else {
    $filter = '';
    $daftar = db_all($sql . " ORDER BY r.id DESC");
}

$judul = "Kelola Restorasi";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'restorasi';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Pengajuan Restorasi</h3>
    <form method="GET">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <?php foreach ($status_valid as $s): ?>
                <option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Layanan</th><th>Bus</th><th>Jadwal survei</th><th>Estimasi</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= tgl($r['created_at'], false) ?></td>
            <td><?= e($r['pelanggan']) ?></td>
            <td><?= e($r['nama_layanan']) ?></td>
            <td><?= e($r['jenis_bus']) ?></td>
            <td><?= tgl($r['jadwal'], false) ?></td>
            <td><?= $r['estimasi_biaya'] !== null ? rupiah($r['estimasi_biaya']) : '-' ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><a class="btn btn-sm btn-dark" href="<?= BASE_URL ?>/admin/restorasi/detail.php?id=<?= $r['id'] ?>">Kelola</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="9" class="text-center text-muted">Tidak ada pengajuan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
