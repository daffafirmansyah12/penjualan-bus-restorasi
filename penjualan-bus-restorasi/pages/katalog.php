<?php
require_once __DIR__ . '/../includes/auth_check.php';

$q    = trim($_GET['q'] ?? '');
$kat  = (int)($_GET['kategori'] ?? 0);
$hmin = ($_GET['harga_min'] ?? '') !== '' ? (float)$_GET['harga_min'] : null;
$hmax = ($_GET['harga_max'] ?? '') !== '' ? (float)$_GET['harga_max'] : null;
$urut = $_GET['urut'] ?? 'terbaru';

$where = ['1=1']; $types = ''; $params = [];
if ($q !== '')       { $where[] = 'b.nama LIKE ?';     $types .= 's'; $params[] = '%' . $q . '%'; }
if ($kat > 0)        { $where[] = 'b.kategori_id = ?'; $types .= 'i'; $params[] = $kat; }
if ($hmin !== null)  { $where[] = 'b.harga >= ?';      $types .= 'd'; $params[] = $hmin; }
if ($hmax !== null)  { $where[] = 'b.harga <= ?';      $types .= 'd'; $params[] = $hmax; }

$urutan = [
    'terbaru'   => 'b.id DESC',
    'termurah'  => 'b.harga ASC',
    'termahal'  => 'b.harga DESC',
    'kapasitas' => 'b.kapasitas DESC',
];
$order = $urutan[$urut] ?? $urutan['terbaru'];

$bus = db_all(
    "SELECT b.*, k.nama_kategori FROM bus b JOIN kategori k ON k.id = b.kategori_id
     WHERE " . implode(' AND ', $where) . " ORDER BY $order", $types, $params);
$kategori = db_all("SELECT * FROM kategori ORDER BY nama_kategori");

// Bus yang sudah ada di wishlist user
$wish = [];
if (is_login() && !is_admin()) {
    foreach (db_all("SELECT bus_id FROM wishlist WHERE user_id = ?", "i", [$_SESSION['user_id']]) as $w) {
        $wish[$w['bus_id']] = true;
    }
}

$judul = "Katalog Bus";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">Katalog Bus</h3>

<form method="GET" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Cari nama</label>
            <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="mis. Medium">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Kategori</label>
            <select name="kategori" class="form-select">
                <option value="0">Semua</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kat == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Harga min</label>
            <input type="number" name="harga_min" class="form-control" value="<?= e($_GET['harga_min'] ?? '') ?>" min="0">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Harga maks</label>
            <input type="number" name="harga_max" class="form-control" value="<?= e($_GET['harga_max'] ?? '') ?>" min="0">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Urutkan</label>
            <select name="urut" class="form-select">
                <option value="terbaru" <?= $urut == 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="termurah" <?= $urut == 'termurah' ? 'selected' : '' ?>>Harga termurah</option>
                <option value="termahal" <?= $urut == 'termahal' ? 'selected' : '' ?>>Harga termahal</option>
                <option value="kapasitas" <?= $urut == 'kapasitas' ? 'selected' : '' ?>>Kapasitas terbanyak</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-dark">Cari</button>
        </div>
    </div>
</form>

<form method="GET" action="<?= BASE_URL ?>/pages/bandingkan.php" id="formBandingkan" class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small"><?= count($bus) ?> bus ditemukan. Centang 2 sampai 3 bus untuk dibandingkan.</span>
    <button class="btn btn-outline-dark btn-sm">Bandingkan yang dicentang</button>
</form>

<?php if (!$bus): ?>
    <div class="alert alert-secondary">Tidak ada bus yang cocok dengan pencarianmu.</div>
<?php endif; ?>

<div class="row g-4">
<?php foreach ($bus as $b): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card card-layanan h-100">
            <?= tampil_gambar('bus', $b['gambar'], $b['nama'], 200) ?>
            <div class="card-body d-flex flex-column">
                <span class="badge text-bg-secondary align-self-start mb-2"><?= e($b['nama_kategori']) ?></span>
                <h5 class="card-title"><?= e($b['nama']) ?></h5>
                <p class="mb-1 small text-muted"><?= (int)$b['kapasitas'] ?> kursi &middot; Stok <?= (int)$b['stok'] ?></p>
                <p class="fs-5 fw-bold mb-3"><?= rupiah($b['harga']) ?></p>
                <div class="mt-auto d-flex gap-2 align-items-center">
                    <a href="<?= BASE_URL ?>/pages/detail.php?id=<?= $b['id'] ?>" class="btn btn-dark btn-sm">Detail</a>
                    <div class="form-check ms-1">
                        <input class="form-check-input" type="checkbox" name="id[]" value="<?= $b['id'] ?>" form="formBandingkan" id="cb<?= $b['id'] ?>">
                        <label class="form-check-label small" for="cb<?= $b['id'] ?>">Bandingkan</label>
                    </div>
                    <?php if (!is_admin()): ?>
                        <form method="POST" action="<?= BASE_URL ?>/user/wishlist.php" class="ms-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="bus_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="kembali" value="/pages/katalog.php">
                            <button class="btn btn-sm <?= isset($wish[$b['id']]) ? 'btn-danger' : 'btn-outline-danger' ?>" title="Wishlist">
                                <?= isset($wish[$b['id']]) ? '&#9829;' : '&#9825;' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
