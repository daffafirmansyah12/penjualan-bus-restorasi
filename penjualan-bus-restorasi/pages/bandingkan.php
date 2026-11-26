<?php
require_once __DIR__ . '/../includes/auth_check.php';

$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_GET['id'] ?? [])))));
$ids = array_slice($ids, 0, 3);

$bus = [];
foreach ($ids as $id) {
    $b = db_row("SELECT b.*, k.nama_kategori FROM bus b JOIN kategori k ON k.id = b.kategori_id WHERE b.id = ?", "i", [$id]);
    if ($b) $bus[] = $b;
}

$judul = "Bandingkan Bus";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">Bandingkan Bus</h3>

<?php if (count($bus) < 2): ?>
    <div class="alert alert-info">
        Pilih minimal 2 bus untuk dibandingkan. Centang bus di
        <a href="<?= BASE_URL ?>/pages/katalog.php">katalog</a>, lalu klik "Bandingkan yang dicentang".
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:160px"></th>
                    <?php foreach ($bus as $b): ?>
                        <th><a href="<?= BASE_URL ?>/pages/detail.php?id=<?= $b['id'] ?>"><?= e($b['nama']) ?></a></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr><th>Foto</th><?php foreach ($bus as $b): ?><td style="max-width:260px"><?= tampil_gambar('bus', $b['gambar'], $b['nama'], 140) ?></td><?php endforeach; ?></tr>
                <tr><th>Kategori</th><?php foreach ($bus as $b): ?><td><?= e($b['nama_kategori']) ?></td><?php endforeach; ?></tr>
                <tr><th>Harga</th><?php foreach ($bus as $b): ?><td class="fw-bold"><?= rupiah($b['harga']) ?></td><?php endforeach; ?></tr>
                <tr><th>Kapasitas</th><?php foreach ($bus as $b): ?><td><?= (int)$b['kapasitas'] ?> kursi</td><?php endforeach; ?></tr>
                <tr><th>Stok</th><?php foreach ($bus as $b): ?><td><?= (int)$b['stok'] ?> unit</td><?php endforeach; ?></tr>
                <tr><th>Spesifikasi</th><?php foreach ($bus as $b): ?><td><?= nl2br(e($b['spesifikasi'])) ?></td><?php endforeach; ?></tr>
                <tr><th></th><?php foreach ($bus as $b): ?>
                    <td><a class="btn btn-dark btn-sm" href="<?= BASE_URL ?>/pages/detail.php?id=<?= $b['id'] ?>">Lihat Detail</a></td>
                <?php endforeach; ?></tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
