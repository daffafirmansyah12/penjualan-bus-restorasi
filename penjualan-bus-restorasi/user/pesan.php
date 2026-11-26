<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_pelanggan();

$uid    = $_SESSION['user_id'];
$bus_id = (int)($_GET['bus_id'] ?? $_POST['bus_id'] ?? 0);

$cek = db_row("SELECT id FROM bus WHERE id = ?", "i", [$bus_id]);
if (!$cek) {
    set_flash("Bus tidak ditemukan.", "danger");
    redirect('/pages/katalog.php');
}

$daftar_warna     = ['Putih', 'Hitam', 'Silver', 'Biru', 'Merah', 'Kuning', 'Hijau'];
$daftar_fasilitas = ['AC', 'TV', 'Toilet', 'Karaoke', 'Reclining Seat', 'Wi-Fi', 'USB Charger', 'Bagasi Besar'];
$error = "";

if (isset($_POST['pesan'])) {
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $warna  = $_POST['warna'] ?? '';
    $fas    = array_values(array_intersect((array)($_POST['fasilitas'] ?? []), $daftar_fasilitas));

    if ($jumlah < 1) {
        $error = "Jumlah minimal 1 unit.";
    } elseif (!in_array($warna, $daftar_warna, true)) {
        $error = "Silakan pilih warna.";
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            $now = db_row("SELECT harga, stok FROM bus WHERE id = ? FOR UPDATE", "i", [$bus_id]);
            if ($now['stok'] < $jumlah) {
                mysqli_rollback($koneksi);
                $error = "Stok tidak mencukupi. Tersisa " . (int)$now['stok'] . " unit.";
            } else {
                $total = $now['harga'] * $jumlah;   // total dihitung di server, bukan dari form
                db_exec("INSERT INTO pesanan (user_id, bus_id, jumlah, warna, fasilitas, total_harga) VALUES (?, ?, ?, ?, ?, ?)",
                        "iiissd", [$uid, $bus_id, $jumlah, $warna, implode(', ', $fas), $total]);
                $pid = db_insert_id();
                db_exec("UPDATE bus SET stok = stok - ? WHERE id = ?", "ii", [$jumlah, $bus_id]);
                mysqli_commit($koneksi);

                set_flash("Pesanan berhasil dibuat. Silakan lakukan pembayaran.");
                redirect('/user/bayar.php?tipe=pesanan&id=' . $pid);
            }
        } catch (Throwable $ex) {
            mysqli_rollback($koneksi);
            $error = "Pesanan gagal diproses, coba lagi.";
        }
    }
}

$b = db_row("SELECT b.*, k.nama_kategori FROM bus b JOIN kategori k ON k.id = b.kategori_id WHERE b.id = ?", "i", [$bus_id]);

$judul = "Pesan Bus";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">Pesan Bus</h3>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <?= tampil_gambar('bus', $b['gambar'], $b['nama'], 180) ?>
            <div class="card-body">
                <span class="badge text-bg-secondary mb-2"><?= e($b['nama_kategori']) ?></span>
                <h5><?= e($b['nama']) ?></h5>
                <p class="mb-1"><?= (int)$b['kapasitas'] ?> kursi &middot; Stok <?= (int)$b['stok'] ?></p>
                <p class="fs-5 fw-bold mb-0"><?= rupiah($b['harga']) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($b['stok'] < 1): ?>
            <div class="alert alert-warning">Maaf, stok bus ini sedang habis.</div>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" class="card card-body mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="bus_id" value="<?= $b['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Jumlah unit</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" max="<?= (int)$b['stok'] ?>" value="1" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Warna</label>
                        <select name="warna" class="form-select" required>
                            <option value="">-- Pilih warna --</option>
                            <?php foreach ($daftar_warna as $w): ?>
                                <option value="<?= e($w) ?>"><?= e($w) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Fasilitas tambahan</label>
                        <div class="row">
                            <?php foreach ($daftar_fasilitas as $f): ?>
                                <div class="col-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= e($f) ?>" id="f<?= md5($f) ?>">
                                        <label class="form-check-label" for="f<?= md5($f) ?>"><?= e($f) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-secondary mb-0">
                            Total harga: <strong id="totalHarga"><?= rupiah($b['harga']) ?></strong>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="pesan" class="btn btn-warning btn-lg">Buat Pesanan</button>
                    </div>
                </div>
            </form>

            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title">Simulasi DP dan Cicilan</h5>
                    <p class="small text-muted">Hanya perkiraan. Ketentuan resmi mengikuti kesepakatan dengan admin.</p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">DP (%)</label>
                            <input type="number" id="dpPersen" class="form-control" value="30" min="0" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Tenor (bulan)</label>
                            <select id="tenor" class="form-select">
                                <option>12</option><option>24</option><option selected>36</option><option>48</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Bunga flat per tahun (%)</label>
                            <input type="number" id="bunga" class="form-control" value="8" min="0" step="0.1">
                        </div>
                    </div>
                    <div>DP: <strong id="simDp">-</strong></div>
                    <div>Cicilan per bulan: <strong id="simCicilan">-</strong></div>
                </div>
            </div>

            <script>
            var harga = <?= (float)$b['harga'] ?>;
            function rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
            function hitung() {
                var jml = Math.max(1, parseInt(document.getElementById('jumlah').value, 10) || 1);
                var total = harga * jml;
                var dpP = Math.min(100, Math.max(0, parseFloat(document.getElementById('dpPersen').value) || 0));
                var tenor = parseInt(document.getElementById('tenor').value, 10);
                var bunga = Math.max(0, parseFloat(document.getElementById('bunga').value) || 0);
                var dp = total * dpP / 100, pokok = total - dp;
                var cicilan = (pokok + pokok * (bunga / 100) * (tenor / 12)) / tenor;
                document.getElementById('totalHarga').textContent = rp(total);
                document.getElementById('simDp').textContent = rp(dp);
                document.getElementById('simCicilan').textContent = rp(cicilan);
            }
            ['jumlah', 'dpPersen', 'tenor', 'bunga'].forEach(function (id) {
                document.getElementById(id).addEventListener('input', hitung);
            });
            hitung();
            </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
