<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$status_valid = ['menunggu', 'dikonfirmasi', 'produksi', 'selesai', 'batal'];

if (isset($_POST['ubah_status'])) {
    $id  = (int)($_POST['id'] ?? 0);
    $baru = $_POST['status'] ?? '';
    if (!in_array($baru, $status_valid, true)) {
        set_flash("Status tidak valid.", "danger");
        redirect('/admin/pesanan/index.php');
    }
    mysqli_begin_transaction($koneksi);
    try {
        $p = db_row("SELECT id, bus_id, jumlah, status FROM pesanan WHERE id = ? FOR UPDATE", "i", [$id]);
        if (!$p) {
            mysqli_rollback($koneksi);
            set_flash("Pesanan tidak ditemukan.", "danger");
        } elseif ($p['status'] === $baru) {
            mysqli_rollback($koneksi);
        } else {
            $ok = true;
            if ($baru === 'batal') {
                db_exec("UPDATE bus SET stok = stok + ? WHERE id = ?", "ii", [$p['jumlah'], $p['bus_id']]);
            } elseif ($p['status'] === 'batal') {
                $b = db_row("SELECT stok FROM bus WHERE id = ? FOR UPDATE", "i", [$p['bus_id']]);
                if ($b['stok'] < $p['jumlah']) {
                    $ok = false;
                } else {
                    db_exec("UPDATE bus SET stok = stok - ? WHERE id = ?", "ii", [$p['jumlah'], $p['bus_id']]);
                }
            }
            if ($ok) {
                db_exec("UPDATE pesanan SET status = ? WHERE id = ?", "si", [$baru, $id]);
                mysqli_commit($koneksi);
                set_flash("Status pesanan #$id diubah menjadi $baru.");
            } else {
                mysqli_rollback($koneksi);
                set_flash("Stok bus tidak cukup untuk mengaktifkan kembali pesanan ini.", "danger");
            }
        }
    } catch (Throwable $ex) {
        mysqli_rollback($koneksi);
        set_flash("Gagal mengubah status.", "danger");
    }
    redirect('/admin/pesanan/index.php' . (!empty($_POST['filter']) ? '?status=' . urlencode($_POST['filter']) : ''));
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT p.*, u.nama AS pelanggan, u.no_hp, b.nama AS nama_bus,
          (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE tipe='pesanan' AND referensi_id=p.id AND status_verifikasi='diterima') AS terbayar
        FROM pesanan p JOIN users u ON u.id = p.user_id JOIN bus b ON b.id = p.bus_id";
if (in_array($filter, $status_valid, true)) {
    $pesanan = db_all($sql . " WHERE p.status = ? ORDER BY p.id DESC", "s", [$filter]);
} else {
    $filter = '';
    $pesanan = db_all($sql . " ORDER BY p.id DESC");
}

$judul = "Kelola Pesanan";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'pesanan';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Kelola Pesanan</h3>
    <form method="GET" class="d-flex gap-2">
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
    <thead class="table-light"><tr><th>#</th><th>Tanggal</th><th>Pelanggan</th><th>Bus</th><th>Jml</th><th>Total</th><th>Terbayar</th><th>Status</th><th style="width:210px">Ubah status</th></tr></thead>
    <tbody>
    <?php foreach ($pesanan as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= tgl($p['tanggal']) ?></td>
            <td><?= e($p['pelanggan']) ?><br><small class="text-muted"><?= e($p['no_hp']) ?></small></td>
            <td><?= e($p['nama_bus']) ?><br><small class="text-muted"><?= e($p['warna']) ?><?= $p['fasilitas'] ? ' - ' . e($p['fasilitas']) : '' ?></small></td>
            <td><?= (int)$p['jumlah'] ?></td>
            <td><?= rupiah($p['total_harga']) ?></td>
            <td><?= rupiah($p['terbayar']) ?></td>
            <td><?= status_badge($p['status']) ?></td>
            <td>
                <form method="POST" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach ($status_valid as $s): ?>
                            <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button name="ubah_status" class="btn btn-sm btn-dark">OK</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$pesanan): ?><tr><td colspan="9" class="text-center text-muted">Tidak ada pesanan.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
