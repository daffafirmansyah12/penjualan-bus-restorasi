<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_admin();

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$edit = $id > 0;
$bus  = $edit ? db_row("SELECT * FROM bus WHERE id = ?", "i", [$id]) : null;
if ($edit && !$bus) {
    set_flash("Bus tidak ditemukan.", "danger");
    redirect('/admin/bus/index.php');
}
$kategori = db_all("SELECT * FROM kategori ORDER BY nama_kategori");
$error = "";

if (isset($_POST['hapus_foto']) && $edit) {
    $g = db_row("SELECT * FROM bus_gambar WHERE id = ? AND bus_id = ?", "ii", [(int)$_POST['foto_id'], $id]);
    if ($g) {
        db_exec("DELETE FROM bus_gambar WHERE id = ?", "i", [$g['id']]);
        hapus_file('bus', $g['file_gambar']);
        set_flash("Foto dihapus.");
    }
    redirect('/admin/bus/form.php?id=' . $id);
}

if (isset($_POST['simpan'])) {
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $harga       = (float)($_POST['harga'] ?? 0);
    $kapasitas   = (int)($_POST['kapasitas'] ?? 0);
    $stok        = (int)($_POST['stok'] ?? 0);
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');

    if (!db_row("SELECT id FROM kategori WHERE id = ?", "i", [$kategori_id])) {
        $error = "Pilih kategori.";
    } elseif ($nama === '') {
        $error = "Nama bus wajib diisi.";
    } elseif ($harga <= 0) {
        $error = "Harga harus lebih dari 0.";
    } elseif ($kapasitas < 1) {
        $error = "Kapasitas minimal 1 kursi.";
    } elseif ($stok < 0) {
        $error = "Stok tidak boleh negatif.";
    } else {
        $foto = upload_gambar($_FILES['gambar'] ?? [], 'bus', $error);
        if ($foto !== false) {
            if ($edit) {
                db_exec("UPDATE bus SET kategori_id=?, nama=?, harga=?, kapasitas=?, deskripsi=?, spesifikasi=?, stok=? WHERE id=?",
                        "isdissii", [$kategori_id, $nama, $harga, $kapasitas, $deskripsi, $spesifikasi, $stok, $id]);
                if ($foto) {
                    hapus_file('bus', $bus['gambar']);
                    db_exec("UPDATE bus SET gambar = ? WHERE id = ?", "si", [$foto, $id]);
                }
            } else {
                db_exec("INSERT INTO bus (kategori_id, nama, harga, kapasitas, deskripsi, spesifikasi, stok, gambar) VALUES (?,?,?,?,?,?,?,?)",
                        "isdissis", [$kategori_id, $nama, $harga, $kapasitas, $deskripsi, $spesifikasi, $stok, $foto]);
                $id = db_insert_id();
            }
            $err2 = "";
            foreach (upload_banyak($_FILES['galeri'] ?? [], 'bus', $err2) as $f) {
                db_exec("INSERT INTO bus_gambar (bus_id, file_gambar) VALUES (?, ?)", "is", [$id, $f]);
            }
            set_flash($err2 ? "Bus disimpan, tetapi ada foto galeri gagal: $err2" : "Bus berhasil disimpan.", $err2 ? "warning" : "success");
            redirect('/admin/bus/index.php');
        }
    }
    $bus = ['kategori_id' => $kategori_id, 'nama' => $nama, 'harga' => $harga, 'kapasitas' => $kapasitas,
            'stok' => $stok, 'deskripsi' => $deskripsi, 'spesifikasi' => $spesifikasi, 'gambar' => $bus['gambar'] ?? null];
}

$galeri = $edit ? db_all("SELECT * FROM bus_gambar WHERE bus_id = ? ORDER BY id", "i", [$id]) : [];
$v = fn($k, $d = '') => e($bus[$k] ?? $d);

$judul = $edit ? "Edit Bus" : "Tambah Bus";
require_once __DIR__ . '/../../includes/header.php';
$menu_aktif = 'bus';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<h3 class="mb-3"><?= e($judul) ?></h3>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama bus</label>
            <input type="text" name="nama" class="form-control" value="<?= $v('nama') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="kategori_id" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= ($bus['kategori_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Harga (Rp)</label>
            <input type="number" name="harga" class="form-control" min="1" step="1" value="<?= $edit || $bus ? e((int)($bus['harga'] ?? 0)) : '' ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kapasitas (kursi)</label>
            <input type="number" name="kapasitas" class="form-control" min="1" value="<?= $v('kapasitas') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Stok</label>
            <input type="number" name="stok" class="form-control" min="0" value="<?= $v('stok', '0') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" rows="4" class="form-control"><?= $v('deskripsi') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Spesifikasi</label>
            <textarea name="spesifikasi" rows="4" class="form-control"><?= $v('spesifikasi') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Foto utama <?= $edit ? '(kosongkan jika tidak diganti)' : '' ?></label>
            <input type="file" name="gambar" class="form-control" accept="image/*">
            <?php if (!empty($bus['gambar'])): ?><div class="mt-2" style="max-width:240px"><?= tampil_gambar('bus', $bus['gambar'], '', 120) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Foto tambahan (boleh banyak)</label>
            <input type="file" name="galeri[]" class="form-control" accept="image/*" multiple>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" name="simpan" class="btn btn-dark">Simpan</button>
        <a href="<?= BASE_URL ?>/admin/bus/index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<?php if ($galeri): ?>
    <h5 class="mt-4">Foto Tambahan</h5>
    <div class="row g-3">
    <?php foreach ($galeri as $g): ?>
        <div class="col-6 col-md-3">
            <div class="card">
                <?= tampil_gambar('bus', $g['file_gambar'], '', 120) ?>
                <form method="POST" class="p-2" onsubmit="return confirm('Hapus foto ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="foto_id" value="<?= $g['id'] ?>">
                    <button name="hapus_foto" class="btn btn-sm btn-outline-danger w-100">Hapus</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
