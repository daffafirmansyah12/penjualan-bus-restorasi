<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (is_login()) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = "";
$nama = $email = $no_hp = "";

if (isset($_POST['register'])) {
    $nama      = trim($_POST['nama']);
    $email     = strtolower(trim($_POST['email']));
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $password  = $_POST['password'];
    $password2 = $_POST['password2'];

    if ($nama == "" || $email == "" || $password == "") {
        $error = "Nama, email, dan password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $password2) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar, gunakan email lain.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, 'pelanggan')");
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hash, $no_hp);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: " . BASE_URL . "/auth/login.php?daftar=1");
                exit;
            } else {
                $error = "Registrasi gagal, coba lagi.";
            }
        }
    }
}

$judul = "Register";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-3">Daftar Akun</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= e($nama) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= e($no_hp) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimal 6 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ulangi Password</label>
                        <input type="password" name="password2" class="form-control" required>
                    </div>
                    <button type="submit" name="register" class="btn btn-dark w-100">Daftar</button>
                </form>

                <p class="text-center small mt-3 mb-0">
                    Sudah punya akun? <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
