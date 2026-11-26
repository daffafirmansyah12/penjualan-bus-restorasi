<?php

$menu_aktif = $menu_aktif ?? '';
$menu_admin = [
    'dashboard'  => ['Dashboard',  '/admin/dashboard.php'],
    'bus'        => ['Bus',        '/admin/bus/index.php'],
    'kategori'   => ['Kategori',   '/admin/kategori/index.php'],
    'layanan'    => ['Layanan Restorasi', '/admin/layanan/index.php'],
    'pesanan'    => ['Pesanan',    '/admin/pesanan/index.php'],
    'restorasi'  => ['Restorasi',  '/admin/restorasi/index.php'],
    'pembayaran' => ['Pembayaran', '/admin/pembayaran/index.php'],
    'galeri'     => ['Galeri',     '/admin/galeri/index.php'],
    'user'       => ['User',       '/admin/user/index.php'],
    'laporan'    => ['Laporan',    '/admin/laporan/index.php'],
];
?>
<ul class="nav nav-pills mb-4 flex-wrap gap-1">
    <?php foreach ($menu_admin as $kode => [$label, $url]): ?>
        <li class="nav-item">
            <a class="nav-link py-1 <?= $menu_aktif === $kode ? 'active bg-dark' : 'text-dark border' ?>"
               href="<?= BASE_URL . $url ?>"><?= e($label) ?></a>
        </li>
    <?php endforeach; ?>
</ul>
