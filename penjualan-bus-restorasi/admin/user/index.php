<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

// Hapus pelanggan beserta seluruh data miliknya
if (isset($_POST['hapus'])) {
    $id = (int)($_POST['id'] ?? 0);
    $u  = db_row("SELECT id, role FROM users WHERE id = ?", "i", [$id]);

    if (!$u) {
        set_flash("User tidak ditemukan.", "danger");
    } elseif ($u['role'] === 'admin' || $id === (int)$_SESSION['user_id']) {
        set_flash("Akun admin tidak bisa dihapus dari sini.", "danger");
    } else {
        $files = [];   // file yang dihapus setelah transaksi sukses: [folder, nama]
        mysqli_begin_transaction($koneksi);
        try {
            // Kembalikan stok untuk pesanan yang masih aktif
            foreach (db_all("SELECT bus_id, jumlah FROM pesanan WHERE user_id = ? AND status IN ('menunggu','dikonfirmasi','produksi')", "i", [$id]) as $p) {
                db_exec("UPDATE bus SET stok = stok + ? WHERE id = ?", "ii", [$p['jumlah'], $p['bus_id']]);
            }
            // Kumpulkan file yang akan ikut dihapus
            foreach (db_all("SELECT p.bukti_bayar AS f FROM pembayaran p JOIN pesanan ps ON p.tipe='pesanan' AND ps.id=p.referensi_id WHERE ps.user_id = ?
                             UNION ALL SELECT p.bukti_bayar FROM pembayaran p JOIN pengajuan_restorasi r ON p.tipe='restorasi' AND r.id=p.referensi_id WHERE r.user_id = ?", "ii", [$id, $id]) as $x) {
                $files[] = ['bukti_bayar', $x['f']];
            }
            foreach (db_all("SELECT foto AS f FROM pengajuan_restorasi WHERE user_id = ?
                             UNION ALL SELECT pr.foto FROM progres_restorasi pr JOIN pengajuan_restorasi r ON r.id = pr.pengajuan_id WHERE r.user_id = ?", "ii", [$id, $id]) as $x) {
                $files[] = ['restorasi', $x['f']];
            }
            // Pembayaran tidak punya foreign key, hapus manual
            db_exec("DELETE p FROM pembayaran p JOIN pesanan ps ON p.tipe='pesanan' AND ps.id=p.referensi_id WHERE ps.user_id = ?", "i", [$id]);
            db_exec("DELETE p FROM pembayaran p JOIN pengajuan_restorasi r ON p.tipe='restorasi' AND r.id=p.referensi_id WHERE r.user_id = ?", "i", [$id]);
            db_exec("DELETE FROM users WHERE id = ?", "i", [$id]);   // pesanan, restorasi, wishlist ikut terhapus (CASCADE)
            mysqli_commit($koneksi);
            foreach ($files as [$folder, $nama]) hapus_file($folder, $nama);
            set_flash("User dan seluruh datanya dihapus.");
        } catch (Throwable $ex) {
            mysqli_rollback($koneksi);
            set_flash("Gagal menghapus user.", "danger");
        }
    }
    redirect('/admin/user/index.php');
}

$users = db_all("SELECT u.*,
                   (SELECT COUNT(*) FROM pesanan WHERE user_id = u.id) AS jml_pesanan,
                   (SELECT COUNT(*) FROM pengajuan_restorasi WHERE user_id = u.id) AS jml_restorasi
                 FROM users u ORDER BY u.role, u.id");

$judul = "Kelola User";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'user';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3">Kelola User</h3>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Peran</th><th>Pesanan</th><th>Restorasi</th><th>Terdaftar</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= e($u['nama']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['no_hp']) ?></td>
            <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'dark' : 'secondary' ?>"><?= e($u['role']) ?></span></td>
            <td><?= (int)$u['jml_pesanan'] ?></td>
            <td><?= (int)$u['jml_restorasi'] ?></td>
            <td><?= tgl($u['created_at'], false) ?></td>
            <td>
                <?php if ($u['role'] !== 'admin'): ?>
                    <form method="POST" onsubmit="return confirm('Hapus user ini beserta semua pesanan dan pengajuan restorasinya?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button name="hapus" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
