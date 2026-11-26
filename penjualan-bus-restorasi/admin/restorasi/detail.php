<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$sql_r = "SELECT r.*, u.nama AS pelanggan, u.email, u.no_hp, l.nama_layanan, l.harga_mulai
          FROM pengajuan_restorasi r JOIN users u ON u.id = r.user_id
          JOIN layanan_restorasi l ON l.id = r.layanan_id WHERE r.id = ?";
$r = db_row($sql_r, "i", [$id]);
if (!$r) {
    set_flash("Pengajuan tidak ditemukan.", "danger");
    redirect('/admin/restorasi/index.php');
}

$alur = [
    'menunggu_survei'      => ['ditolak'],
    'menunggu_persetujuan' => ['menunggu_survei', 'ditolak'],
    'disetujui'            => ['dikerjakan'],
    'dikerjakan'           => ['selesai'],
    'selesai'              => [],
    'ditolak'              => [],
];
$bisa_progres = in_array($r['status'], ['disetujui', 'dikerjakan', 'selesai'], true);
$back = '/admin/restorasi/detail.php?id=' . $id;

if (isset($_POST['simpan_estimasi'])) {
    $biaya  = (float)($_POST['estimasi_biaya'] ?? 0);
    $jadwal = trim($_POST['jadwal'] ?? '');
    if (!in_array($r['status'], ['menunggu_survei', 'menunggu_persetujuan'], true)) {
        set_flash("Estimasi tidak bisa diubah pada status ini.", "danger");
    } elseif ($biaya <= 0) {
        set_flash("Estimasi biaya harus lebih dari 0.", "danger");
    } elseif ($jadwal !== '' && DateTime::createFromFormat('Y-m-d', $jadwal) === false) {
        set_flash("Format jadwal tidak valid.", "danger");
    } else {
        db_exec("UPDATE pengajuan_restorasi SET estimasi_biaya = ?, jadwal = ?, status = 'menunggu_persetujuan' WHERE id = ?",
                "dsi", [$biaya, $jadwal === '' ? null : $jadwal, $id]);
        set_flash("Estimasi tersimpan. Menunggu persetujuan pelanggan.");
    }
    redirect($back);
}

if (isset($_POST['ubah_status'])) {
    $baru = $_POST['status'] ?? '';
    if (in_array($baru, $alur[$r['status']], true)) {
        db_exec("UPDATE pengajuan_restorasi SET status = ? WHERE id = ?", "si", [$baru, $id]);
        set_flash("Status diubah menjadi " . str_replace('_', ' ', $baru) . ".");
    } else {
        set_flash("Perubahan status tidak diizinkan.", "danger");
    }
    redirect($back);
}

if (isset($_POST['tambah_progres'])) {
    $ket = trim($_POST['keterangan'] ?? '');
    if (!$bisa_progres) {
        set_flash("Progres bisa dicatat setelah pelanggan menyetujui estimasi.", "danger");
    } elseif ($ket === '') {
        set_flash("Keterangan progres wajib diisi.", "danger");
    } else {
        $err = "";
        $foto = upload_gambar($_FILES['foto'] ?? [], 'restorasi', $err);
        if ($foto === false) {
            set_flash($err, "danger");
        } else {
            db_exec("INSERT INTO progres_restorasi (pengajuan_id, keterangan, foto) VALUES (?, ?, ?)", "iss", [$id, $ket, $foto]);
            set_flash("Progres ditambahkan.");
        }
    }
    redirect($back);
}

if (isset($_POST['hapus_progres'])) {
    $p = db_row("SELECT * FROM progres_restorasi WHERE id = ? AND pengajuan_id = ?", "ii", [(int)$_POST['progres_id'], $id]);
    if ($p) {
        db_exec("DELETE FROM progres_restorasi WHERE id = ?", "i", [$p['id']]);
        hapus_file('restorasi', $p['foto']);
        set_flash("Progres dihapus.");
    }
    redirect($back);
}

$progres = db_all("SELECT * FROM progres_restorasi WHERE pengajuan_id = ? ORDER BY tanggal DESC, id DESC", "i", [$id]);
$bayar = db_row("SELECT COALESCE(SUM(CASE WHEN status_verifikasi='diterima' THEN jumlah_bayar END),0) AS diterima,
                        COALESCE(SUM(CASE WHEN status_verifikasi='menunggu' THEN jumlah_bayar END),0) AS menunggu
                 FROM pembayaran WHERE tipe = 'restorasi' AND referensi_id = ?", "i", [$id]);

$judul = "Kelola Restorasi #" . $id;
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'restorasi';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<nav class="small mb-3"><a href="<?= BASE_URL ?>/admin/restorasi/index.php">&laquo; Kembali ke daftar</a></nav>
<h3 class="mb-3">Restorasi #<?= $r['id'] ?> <?= status_badge($r['status']) ?></h3>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Pelanggan</td><td><?= e($r['pelanggan']) ?><br><small class="text-muted"><?= e($r['email']) ?> | <?= e($r['no_hp']) ?></small></td></tr>
                    <tr><td>Layanan</td><td><?= e($r['nama_layanan']) ?> (mulai <?= rupiah($r['harga_mulai']) ?>)</td></tr>
                    <tr><td>Bus</td><td><?= e($r['jenis_bus']) ?><?= $r['tahun'] ? ' (' . (int)$r['tahun'] . ')' : '' ?></td></tr>
                    <tr><td>Kondisi</td><td><?= nl2br(e($r['deskripsi_kondisi'])) ?></td></tr>
                    <tr><td>Pembayaran</td><td>Diterima <?= rupiah($bayar['diterima']) ?>, menunggu <?= rupiah($bayar['menunggu']) ?></td></tr>
                </table>
            </div>
            <?php if ($r['foto']): ?><?= tampil_gambar('restorasi', $r['foto'], 'Foto kondisi', 220) ?><?php endif; ?>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Estimasi Biaya dan Jadwal</h5>
                <?php if (in_array($r['status'], ['menunggu_survei', 'menunggu_persetujuan'], true)): ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="mb-3"><label class="form-label">Estimasi biaya (Rp)</label>
                            <input type="number" name="estimasi_biaya" class="form-control" min="1" value="<?= $r['estimasi_biaya'] !== null ? (int)$r['estimasi_biaya'] : '' ?>" required></div>
                        <div class="mb-3"><label class="form-label">Jadwal survei / masuk bengkel</label>
                            <input type="date" name="jadwal" class="form-control" value="<?= e($r['jadwal']) ?>"></div>
                        <button name="simpan_estimasi" class="btn btn-dark">Simpan dan Kirim ke Pelanggan</button>
                    </form>
                <?php else: ?>
                    <p class="mb-0">Estimasi: <strong><?= $r['estimasi_biaya'] !== null ? rupiah($r['estimasi_biaya']) : '-' ?></strong><br>
                    Jadwal: <?= tgl($r['jadwal'], false) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($alur[$r['status']]): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ubah Status</h5>
                <form method="POST" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="status" class="form-select">
                        <?php foreach ($alur[$r['status']] as $s): ?>
                            <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button name="ubah_status" class="btn btn-dark">Ubah</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <h5>Progres Pengerjaan</h5>
        <?php if ($bisa_progres): ?>
        <form method="POST" enctype="multipart/form-data" class="card card-body mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-2"><textarea name="keterangan" rows="2" class="form-control" placeholder="Catatan progres, mis. pengecatan dasar selesai" required></textarea></div>
            <div class="mb-2"><input type="file" name="foto" class="form-control form-control-sm" accept="image/*"></div>
            <button name="tambah_progres" class="btn btn-warning btn-sm align-self-start">Tambah Progres</button>
        </form>
        <?php else: ?>
            <p class="text-muted small">Progres bisa dicatat setelah pelanggan menyetujui estimasi.</p>
        <?php endif; ?>

        <?php foreach ($progres as $p): ?>
            <div class="card mb-3">
                <?php if ($p['foto']): ?><?= tampil_gambar('restorasi', $p['foto'], 'Progres', 180) ?><?php endif; ?>
                <div class="card-body py-2 d-flex justify-content-between gap-2">
                    <div><div class="small text-muted"><?= tgl($p['tanggal']) ?></div><?= nl2br(e($p['keterangan'])) ?></div>
                    <form method="POST" onsubmit="return confirm('Hapus progres ini?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="progres_id" value="<?= $p['id'] ?>">
                        <button name="hapus_progres" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
