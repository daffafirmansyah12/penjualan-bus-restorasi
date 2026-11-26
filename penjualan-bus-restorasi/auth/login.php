<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (is_login()) {
    header("Location: " . BASE_URL . (is_admin() ? "/admin/dashboard.php" : "/index.php"));
    exit;
}

$error = "";
$email = "";

if (isset($_POST['login'])) {
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, email, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);   // cegah session fixation
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        header("Location: " . BASE_URL . ($user['role'] === 'admin' ? "/admin/dashboard.php" : "/index.php"));
        exit;
    } else {
        $error = "Email atau password salah.";
    }
}

$judul = "Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-3">Login</h3>

                <?php if (isset($_GET['daftar'])): ?>
                    <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-dark w-100">Login</button>
                </form>

                <p class="text-center small mt-3 mb-0">
                    Belum punya akun? <a href="<?= BASE_URL ?>/auth/register.php">Daftar</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
