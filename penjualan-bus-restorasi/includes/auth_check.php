<?php


if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/fungsi.php';

function is_login() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_login() && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_login()) {
        redirect('/auth/login.php');
    }
}

function require_pelanggan() {
    require_login();
    if (is_admin()) {
        set_flash('Fitur ini hanya untuk pelanggan.', 'warning');
        redirect('/admin/dashboard.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die("<h3>403 - Halaman ini hanya untuk admin.</h3><a href='" . BASE_URL . "/index.php'>Kembali ke beranda</a>");
    }
}

csrf_verify();
