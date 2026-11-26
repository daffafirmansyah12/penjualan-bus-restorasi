<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$id = $_SESSION['user_id'];
$pesan_profil = $pesan_password = "";
$ok_profil = $ok_password = false;

if (isset($_POST['ubah_profil'])) {
    $nama  = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);

    if ($nama == "") {
        $pesan_profil = "Nama tidak boleh kosong.";
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, no_hp = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $nama, $no_hp, $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['nama'] = $nama;
        $pesan_profil = "Profil berhasil diperbarui.";
        $ok_profil = true;
    }
}

if (isset($_POST['ganti_password'])) {
    $lama  = $_POST['password_lama'];
    $baru1 = $_POST['password_baru1'];
    $baru2 = $_POST['password_baru2'];

    $stmt = mysqli_prepare($koneksi, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!password_verify($lama, $row['password'])) {
        $pesan_password = "Password lama salah.";
    } elseif (strlen($baru1) < 6) {
        $pesan_password = "Password baru minimal 6 karakter.";
    } elseif ($baru1 !== $baru2) {
        $pesan_password = "Password baru 1 dan password baru 2 tidak sama.";
    } else {
        $hash = password_hash($baru1, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        mysqli_stmt_execute($stmt);
        $pesan_password = "Password berhasil diganti.";
        $ok_password = true;
    }
}

$stmt = mysqli_prepare($koneksi, "SELECT nama, email, no_hp, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$judul = "Profil Saya";
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">Profil Saya</h3>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Data Akun</h5>
                <?php if ($pesan_profil): ?>
                    <div class="alert alert-<?= $ok_profil ? 'success' : 'danger' ?>"><?= e($pesan_profil) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" value="<?= e($user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= e($user['no_hp']) ?>">
                    </div>
                    <p class="small text-muted">Terdaftar sejak <?= e($user['created_at']) ?></p>
                    <button type="submit" name="ubah_profil" class="btn btn-dark">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ganti Password</h5>
                <?php if ($pesan_password): ?>
                    <div class="alert alert-<?= $ok_password ? 'success' : 'danger' ?>"><?= e($pesan_password) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru1" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ulangi Password Baru</label>
                        <input type="password" name="password_baru2" class="form-control" required>
                    </div>
                    <button type="submit" name="ganti_password" class="btn btn-dark">Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
