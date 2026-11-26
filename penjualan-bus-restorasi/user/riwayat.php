<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid = $_SESSION['user_id'];

if (isset($_POST['batal'])) {
    $id = (int)($_POST['id'] ?? 0);
    mysqli_begin_transaction($koneksi);
    try {
        $p = db_row("SELECT id, bus_id, jumlah, status FROM pesanan WHERE id = ? AND user_id = ? FOR UPDATE", "ii", [$id, $uid]);
        if ($p && $p['status'] === 'menunggu') {
            db_exec("UPDATE pesanan SET status = 'batal' WHERE id = ?", "i", [$id]);
            db_exec("UPDATE bus SET stok = stok + ? WHERE id = ?", "ii", [$p['jumlah'], $p['bus_id']]);
            mysqli_commit($koneksi);
            set_flash("Pesanan #$id dibatalkan.", "secondary");
        } else {
            mysqli_rollback($koneksi);
            set_flash("Pesanan tidak bisa dibatalkan.", "danger");
        }
    } catch (Throwable $ex) {
        mysqli_rollback($koneksi);
        set_flash("Gagal membatalkan pesanan.", "danger");
    }
    redirect('/user/riwayat.php');
}

$pesanan = db_all(
    "SELECT p.*, b.nama AS nama_bus,
        (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran
          WHERE tipe = 'pesanan' AND referensi_id = p.id AND status_verifikasi = 'diterima') AS terbayar
     FROM pesanan p JOIN bus b ON b.id = p.bus_id
     WHERE p.user_id = ? ORDER BY p.id DESC", "i", [$uid]);

$judul = "Pesanan Saya";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">Pesanan Saya</h3>

<?php if (!$pesanan): ?>
    <div class="alert alert-secondary">Belum ada pesanan. Lihat <a href="<?= BASE_URL ?>/pages/katalog.php">katalog bus</a>.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Tanggal</th><th>Bus</th><th>Jumlah</th><th>Total</th><th>Terbayar</th><th>Status</th><th style="width:230px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pesanan as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= tgl($p['tanggal']) ?></td>
                <td><?= e($p['nama_bus']) ?><br><small class="text-muted"><?= e($p['warna']) ?><?= $p['fasilitas'] ? ' - ' . e($p['fasilitas']) : '' ?></small></td>
                <td><?= (int)$p['jumlah'] ?></td>
                <td><?= rupiah($p['total_harga']) ?></td>
                <td><?= rupiah($p['terbayar']) ?></td>
                <td><?= status_badge($p['status']) ?></td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                    <?php if ($p['status'] !== 'batal'): ?>
                        <a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>/user/bayar.php?tipe=pesanan&id=<?= $p['id'] ?>">Bayar</a>
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/user/invoice.php?tipe=pesanan&id=<?= $p['id'] ?>" target="_blank">Invoice</a>
                    <?php endif; ?>
                    <?php if ($p['status'] === 'menunggu'): ?>
                        <form method="POST" onsubmit="return confirm('Batalkan pesanan ini?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button name="batal" class="btn btn-sm btn-outline-danger">Batalkan</button>
                        </form>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
