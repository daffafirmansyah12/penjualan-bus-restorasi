<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$r = db_row("SELECT r.*, l.nama_layanan FROM pengajuan_restorasi r
             JOIN layanan_restorasi l ON l.id = r.layanan_id WHERE r.id = ? AND r.user_id = ?", "ii", [$id, $uid]);
if (!$r) {
    set_flash("Pengajuan tidak ditemukan.", "danger");
    redirect('/user/restorasi_riwayat.php');
}

if ($r['status'] === 'menunggu_persetujuan' && $r['estimasi_biaya'] !== null) {
    if (isset($_POST['setuju'])) {
        db_exec("UPDATE pengajuan_restorasi SET status = 'disetujui' WHERE id = ? AND user_id = ?", "ii", [$id, $uid]);
        set_flash("Estimasi disetujui. Silakan lakukan pembayaran agar pengerjaan dapat dimulai.");
        redirect('/user/restorasi_detail.php?id=' . $id);
    }
    if (isset($_POST['tolak'])) {
        db_exec("UPDATE pengajuan_restorasi SET status = 'ditolak' WHERE id = ? AND user_id = ?", "ii", [$id, $uid]);
        set_flash("Estimasi ditolak. Pengajuan ditutup.", "secondary");
        redirect('/user/restorasi_detail.php?id=' . $id);
    }
}

$progres = db_all("SELECT * FROM progres_restorasi WHERE pengajuan_id = ? ORDER BY tanggal DESC, id DESC", "i", [$id]);

$judul = "Detail Restorasi";
require_once __DIR__ . '/../includes/header.php';
?>

<nav class="small mb-3"><a href="<?= BASE_URL ?>/user/restorasi_riwayat.php">&laquo; Kembali</a></nav>
<h3 class="mb-3">Restorasi #<?= $r['id'] ?> <?= status_badge($r['status']) ?></h3>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Layanan</td><td><?= e($r['nama_layanan']) ?></td></tr>
                    <tr><td>Bus</td><td><?= e($r['jenis_bus']) ?><?= $r['tahun'] ? ' (' . (int)$r['tahun'] . ')' : '' ?></td></tr>
                    <tr><td>Jadwal survei</td><td><?= tgl($r['jadwal'], false) ?></td></tr>
                    <tr><td>Kondisi</td><td><?= nl2br(e($r['deskripsi_kondisi'])) ?></td></tr>
                    <tr><td>Estimasi biaya</td><td class="fw-bold"><?= $r['estimasi_biaya'] !== null ? rupiah($r['estimasi_biaya']) : 'Belum ada' ?></td></tr>
                </table>
            </div>
            <?php if ($r['foto']): ?>
                <?= tampil_gambar('restorasi', $r['foto'], 'Foto kondisi', 220) ?>
            <?php endif; ?>
        </div>

        <?php if ($r['status'] === 'menunggu_persetujuan' && $r['estimasi_biaya'] !== null): ?>
            <div class="card border-info mb-3">
                <div class="card-body">
                    <p class="mb-2">Admin memberi estimasi <strong><?= rupiah($r['estimasi_biaya']) ?></strong>. Setujui untuk melanjutkan?</p>
                    <form method="POST" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button name="setuju" class="btn btn-success">Setujui</button>
                        <button name="tolak" class="btn btn-outline-danger" onclick="return confirm('Tolak estimasi ini?')">Tolak</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($r['estimasi_biaya'] !== null && in_array($r['status'], ['disetujui', 'dikerjakan', 'selesai'], true)): ?>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/user/bayar.php?tipe=restorasi&id=<?= $id ?>" class="btn btn-warning">Bayar</a>
                <a href="<?= BASE_URL ?>/user/invoice.php?tipe=restorasi&id=<?= $id ?>" target="_blank" class="btn btn-outline-dark">Invoice</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <h5>Progres Pengerjaan</h5>
        <?php if (!$progres): ?>
            <p class="text-muted">Belum ada catatan progres.</p>
        <?php endif; ?>
        <?php foreach ($progres as $p): ?>
            <div class="card mb-3">
                <?php if ($p['foto']): ?><?= tampil_gambar('restorasi', $p['foto'], 'Progres', 200) ?><?php endif; ?>
                <div class="card-body py-2">
                    <div class="small text-muted"><?= tgl($p['tanggal']) ?></div>
                    <?= nl2br(e($p['keterangan'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
