<?php

function e($teks) {
    return htmlspecialchars((string)($teks ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tgl($waktu, $dengan_jam = true) {
    if (!$waktu) return '-';
    return date($dengan_jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime($waktu));
}

function status_badge($status) {
    $warna = [
        'menunggu' => 'warning', 'dikonfirmasi' => 'info', 'produksi' => 'primary',
        'selesai' => 'success', 'batal' => 'danger',
        'menunggu_survei' => 'warning', 'menunggu_persetujuan' => 'info',
        'disetujui' => 'primary', 'dikerjakan' => 'primary', 'ditolak' => 'danger',
        'diterima' => 'success',
    ];
    $kelas = $warna[$status] ?? 'secondary';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge text-bg-' . $kelas . '">' . e($label) . '</span>';
}


function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function aman_kembali($path, $default = '/index.php') {
    if (is_string($path) && preg_match('/^\/[A-Za-z0-9_\-\/\.\?=&%\[\]]*$/', $path) && strpos($path, '//') !== 0) {
        return $path;
    }
    return $default;
}

function set_flash($pesan, $tipe = 'success') {
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['tipe']) . ' alert-dismissible fade show">'
           . e($f['pesan'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}


function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419);
            die("Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.");
        }
    }
}



function db_stmt($sql, $types, $params) {
    global $koneksi;
    $st = mysqli_prepare($koneksi, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($st, $types, ...$params);
    }
    mysqli_stmt_execute($st);
    return $st;
}

function db_all($sql, $types = '', $params = []) {
    $st = db_stmt($sql, $types, $params);
    return mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
}

function db_row($sql, $types = '', $params = []) {
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_exec($sql, $types = '', $params = []) {
    $st = db_stmt($sql, $types, $params);
    return mysqli_stmt_affected_rows($st);
}

function db_insert_id() {
    global $koneksi;
    return mysqli_insert_id($koneksi);
}



function upload_gambar($file, $folder, &$error) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload gagal (kode " . $file['error'] . "). Pastikan ukuran file tidak terlalu besar.";
        return false;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = "Ukuran gambar maksimal 2 MB.";
        return false;
    }
    $info = @getimagesize($file['tmp_name']);
    $boleh = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($boleh[$info[2]])) {
        $error = "File harus berupa gambar JPG, PNG, atau WEBP.";
        return false;
    }
    $nama = bin2hex(random_bytes(8)) . '.' . $boleh[$info[2]];
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) {
        $error = "Gagal menyimpan file ke server.";
        return false;
    }
    return $nama;
}

function upload_banyak($files, $folder, &$error) {
    $hasil = [];
    if (!isset($files['name']) || !is_array($files['name'])) return $hasil;
    foreach ($files['name'] as $i => $nama) {
        $satu = [
            'name' => $nama, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i], 'size' => $files['size'][$i],
        ];
        $r = upload_gambar($satu, $folder, $error);
        if ($r === false) return $hasil;
        if ($r !== null) $hasil[] = $r;
    }
    return $hasil;
}

function img_url($folder, $file) {
    return $file ? BASE_URL . '/uploads/' . $folder . '/' . rawurlencode($file) : null;
}

function tampil_gambar($folder, $file, $alt = '', $tinggi = 200) {
    $u = img_url($folder, $file);
    if ($u) {
        return '<img src="' . e($u) . '" alt="' . e($alt) . '" class="w-100" style="height:' . (int)$tinggi . 'px;object-fit:cover">';
    }
    return '<div class="bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center w-100" style="height:'
         . (int)$tinggi . 'px">Belum ada foto</div>';
}

function hapus_file($folder, $file) {
    if ($file) {
        $p = __DIR__ . '/../uploads/' . $folder . '/' . basename($file);
        if (is_file($p)) @unlink($p);
    }
}
