<?php

$judul = $judul ?? 'Beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pages/katalog.php">Katalog Bus</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pages/layanan.php">Jasa Restorasi</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pages/galeri.php">Galeri</a></li>
            </ul>

            <ul class="navbar-nav">
            <?php if (is_login()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        Halo, <?= e($_SESSION['nama']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (is_admin()): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard Admin</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/riwayat.php">Pesanan Saya</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/restorasi_riwayat.php">Restorasi Saya</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/wishlist.php">Wishlist</a></li>
                    <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profil.php">Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
                <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2 mt-1" href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">
<div class="container">
<?php show_flash(); ?>
