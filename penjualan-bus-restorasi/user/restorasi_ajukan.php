<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid = $_SESSION['user_id'];
$layanan = db_all("SELECT * FROM layanan_restorasi ORDER BY id");
$error = "";

$layanan_id = (int)($_POST['layanan_id'] ?? $_GET['layanan_id'] ?? 0);
$jenis_bus = $tahun = $kondisi = $jadwal = "";

if (isset($_POST['ajukan'])) {
    $jenis_bus = trim($_POST['jenis_bus'] ?? '');
    $tahun     = trim($_POST['tahun'] ?? '');
    $kondisi   = trim($_POST['deskripsi_kondisi'] ?? '');
    $jadwal    = trim($_POST['jadwal'] ?? '');

    $ada_layanan = db_row("SELECT id FROM layanan_restorasi WHERE id = ?", "i", [$layanan_id]);
    $tgl_ok = $jadwal !== '' && DateTime::createFromFormat('Y-m-d', $jadwal) !== false;

    if (!$ada_layanan) {
        $error = "Pilih layanan restorasi.";
    } elseif ($jenis_bus === '') {
        $error = "Jenis atau merek bus wajib diisi.";
    } elseif ($tahun !== '' && (!ctype_digit($tahun) || $tahun < 1980 || $tahun > (int)date('Y') + 1)) {
        $error = "Tahun bus tidak valid.";
    } elseif (!$tgl_ok || $jadwal <= date('Y-m-d')) {
        $error = "Jadwal survei harus berupa tanggal setelah hari ini.";
    } else {
        $foto = upload_gambar($_FILES['foto'] ?? [], 'restorasi', $error);
        if ($foto !== false) {
            db_exec("INSERT INTO pengajuan_restorasi (user_id, layanan_id, jenis_bus, tahun, deskripsi_kondisi, foto, jadwal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    "iisisss", [$uid, $layanan_id, $jenis_bus, $tahun === '' ? null : (int)$tahun, $kondisi, $foto, $jadwal]);
            set_flash("Pengajuan restorasi terkirim. Admin akan melakukan survei dan memberi estimasi biaya.");
            redirect('/user/restorasi_riwayat.php');
        }
    }
}

$judul = "Ajukan Restorasi";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8">
    <h3 class="mb-3">Ajukan Restorasi Body Bus</h3>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card card-body">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Layanan</label>
            <select name="layanan_id" class="form-select" required>
                <option value="">-- Pilih layanan --</option>
                <?php foreach ($layanan as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $layanan_id == $l['id'] ? 'selected' : '' ?>>
                        <?= e($l['nama_layanan']) ?> (mulai <?= rupiah($l['harga_mulai']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Jenis / merek bus</label>
                <input type="text" name="jenis_bus" class="form-control" value="<?= e($jenis_bus) ?>" placeholder="mis. Big Bus Pariwisata" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Tahun</label>
                <input type="number" name="tahun" class="form-control" value="<?= e($tahun) ?>" placeholder="2010">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Kondisi dan keluhan</label>
            <textarea name="deskripsi_kondisi" rows="4" class="form-control" placeholder="Jelaskan bagian yang rusak atau ingin diperbaiki"><?= e($kondisi) ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Foto kondisi bus (opsional)</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Jadwal survei yang diinginkan</label>
                <input type="date" name="jadwal" class="form-control" value="<?= e($jadwal) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
        </div>
        <button type="submit" name="ajukan" class="btn btn-warning">Kirim Pengajuan</button>
    </form>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
