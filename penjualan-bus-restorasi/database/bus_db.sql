CREATE DATABASE IF NOT EXISTS bus_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bus_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS wishlist, pembayaran, galeri_restorasi, progres_restorasi,
  pengajuan_restorasi, layanan_restorasi, pesanan, bus_gambar, bus, kategori, users;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,              -- hasil password_hash()
  no_hp       VARCHAR(20)  DEFAULT NULL,
  role        ENUM('admin','pelanggan') NOT NULL DEFAULT 'pelanggan',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE kategori (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE bus (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id   INT NOT NULL,
  nama          VARCHAR(100) NOT NULL,
  harga         DECIMAL(15,2) NOT NULL,
  kapasitas     INT NOT NULL,                      -- jumlah kursi
  deskripsi     TEXT,
  spesifikasi   TEXT,
  gambar        VARCHAR(255) DEFAULT NULL,         -- gambar utama
  stok          INT NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE bus_gambar (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  bus_id       INT NOT NULL,
  file_gambar  VARCHAR(255) NOT NULL,
  FOREIGN KEY (bus_id) REFERENCES bus(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE pesanan (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  bus_id       INT NOT NULL,
  jumlah       INT NOT NULL DEFAULT 1,
  warna        VARCHAR(50) DEFAULT NULL,
  fasilitas    TEXT,                               -- contoh: AC, TV, Toilet
  total_harga  DECIMAL(15,2) NOT NULL,
  status       ENUM('menunggu','dikonfirmasi','produksi','selesai','batal')
               NOT NULL DEFAULT 'menunggu',
  tanggal      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (bus_id) REFERENCES bus(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE layanan_restorasi (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nama_layanan   VARCHAR(100) NOT NULL,
  deskripsi      TEXT,
  harga_mulai    DECIMAL(15,2) NOT NULL DEFAULT 0,
  estimasi_hari  INT NOT NULL DEFAULT 7
) ENGINE=InnoDB;


CREATE TABLE pengajuan_restorasi (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  user_id           INT NOT NULL,
  layanan_id        INT NOT NULL,
  jenis_bus         VARCHAR(100) NOT NULL,
  tahun             INT DEFAULT NULL,
  deskripsi_kondisi TEXT,
  foto              VARCHAR(255) DEFAULT NULL,
  jadwal            DATE DEFAULT NULL,             -- jadwal survei / masuk bengkel
  estimasi_biaya    DECIMAL(15,2) DEFAULT NULL,    -- diisi admin
  status            ENUM('menunggu_survei','menunggu_persetujuan','disetujui',
                         'dikerjakan','selesai','ditolak')
                    NOT NULL DEFAULT 'menunggu_survei',
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (layanan_id) REFERENCES layanan_restorasi(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE progres_restorasi (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pengajuan_id INT NOT NULL,
  keterangan   TEXT NOT NULL,
  foto         VARCHAR(255) DEFAULT NULL,
  tanggal      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pengajuan_id) REFERENCES pengajuan_restorasi(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE galeri_restorasi (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  judul       VARCHAR(150) NOT NULL,
  foto_before VARCHAR(255) DEFAULT NULL,
  foto_after  VARCHAR(255) DEFAULT NULL,
  deskripsi   TEXT
) ENGINE=InnoDB;


CREATE TABLE pembayaran (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  tipe               ENUM('pesanan','restorasi') NOT NULL,
  referensi_id       INT NOT NULL,
  jumlah_bayar       DECIMAL(15,2) NOT NULL,
  bukti_bayar        VARCHAR(255) DEFAULT NULL,
  status_verifikasi  ENUM('menunggu','diterima','ditolak') NOT NULL DEFAULT 'menunggu',
  tanggal            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tipe_ref (tipe, referensi_id)
) ENGINE=InnoDB;


CREATE TABLE wishlist (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  user_id  INT NOT NULL,
  bus_id   INT NOT NULL,
  UNIQUE KEY uniq_user_bus (user_id, bus_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (bus_id) REFERENCES bus(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO users (nama, email, password, no_hp, role) VALUES
('Administrator', 'admin@bus.com',
 '$2y$10$t.EvUy.up.a43uyLUpxOQ.SKugs2oBJOu5lnlEopRUNlvK4JE/lpK', '081234567890', 'admin'),
('Pelanggan Contoh', 'pelanggan@bus.com',
 '$2y$10$r7kPpPLp2Fu8FjCFelKof.fHxr7kO9xi3YnmAPoFY6E6vA119EQf6', '089876543210', 'pelanggan');

INSERT INTO kategori (nama_kategori) VALUES
('Mini Bus'), ('Medium Bus'), ('Big Bus'), ('Double Decker');

INSERT INTO bus (kategori_id, nama, harga, kapasitas, deskripsi, spesifikasi, stok) VALUES
(1, 'Mini Bus Executive 19 Seat', 650000000, 19,
 'Cocok untuk wisata kelompok kecil dan antar-jemput.',
 'Mesin 4 silinder, AC, audio, reclining seat', 5),
(2, 'Medium Bus Pariwisata 31 Seat', 950000000, 31,
 'Bus sedang untuk pariwisata dan rombongan instansi.',
 'AC, TV, bagasi luas, reclining seat', 4),
(3, 'Big Bus Pariwisata 59 Seat', 1650000000, 59,
 'Bus besar untuk perjalanan jarak jauh dan pariwisata.',
 'AC, TV, toilet, karaoke, bagasi besar', 3),
(4, 'Double Decker Premium', 2800000000, 48,
 'Bus bertingkat dengan kabin mewah.',
 'AC, TV, toilet, lounge, panoramic window', 1);

INSERT INTO layanan_restorasi (nama_layanan, deskripsi, harga_mulai, estimasi_hari) VALUES
('Cat Ulang Body',          'Pengecatan ulang seluruh body bus.',                 25000000, 14),
('Perbaikan Panel Body',    'Perbaikan atau penggantian panel body yang rusak.',  15000000, 10),
('Restorasi Interior',      'Penggantian jok, plafon, dan lantai kabin.',         30000000, 14),
('Penggantian Kaca',        'Penggantian kaca depan, samping, dan belakang.',      8000000,  5),
('Pelapisan Anti Karat',    'Perawatan rangka dan body dari karat.',              12000000,  7);

INSERT INTO galeri_restorasi (judul, deskripsi) VALUES
('Restorasi Big Bus 2010', 'Cat ulang dan perbaikan panel body. (Isi foto before dan after nanti.)');
