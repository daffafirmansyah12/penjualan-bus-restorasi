# Penjualan Bus dan Jasa Restorasi Body Bus Berbasis Web

Website dealer dan karoseri bus: pelanggan dapat melihat katalog bus, memesan unit,
dan mengajukan jasa restorasi body bus, sementara admin mengelola data dan memantau progres.

Project kelompok mata kuliah Pemrograman Berbasis Platform (PBP).

## Anggota Kelompok
| No | Nama | Bagian |
|----|------|--------|
| 1 | (isi nama) | Fondasi, auth, keamanan, hosting |
| 2 | (isi nama) | Katalog, layanan restorasi (publik dan pelanggan), tampilan |
| 3 | (isi nama) | Pemesanan bus dan pembayaran |
| 4 | (isi nama) | Panel admin |

## Fitur Utama
- Register, login, logout, dan profil (peran admin dan pelanggan)
- Katalog bus dengan detail, filter, dan pencarian
- Pemesanan bus dan pembayaran dengan bukti transfer
- Pengajuan jasa restorasi, estimasi biaya, dan pemantauan progres pengerjaan
- Panel admin untuk mengelola bus, pesanan, restorasi, pembayaran, dan laporan

## Teknologi
PHP, MySQL/MariaDB, HTML, CSS, Bootstrap 5

## Cara Menjalankan (Setup)
1. Pasang XAMPP, lalu jalankan **Apache** dan **MySQL**.
2. Salin folder project ke `C:\xampp\htdocs\penjualan-bus-restorasi`.
3. Buka `http://localhost/phpmyadmin`, klik tab **Import**, pilih `database/bus_db.sql`, lalu **Import**.
4. Jika nama folder project berbeda, ubah `BASE_URL` di `config/config.php`.
5. Buka `http://localhost/penjualan-bus-restorasi/`.

### Akun contoh
| Peran | Email | Password |
|-------|-------|----------|
| Admin | admin@bus.com | admin123 |
| Pelanggan | pelanggan@bus.com | pelanggan123 |

## Struktur Folder
```
penjualan-bus-restorasi/
├── index.php
├── README.md
├── .gitignore
├── config/        (config.php, database.php)
├── includes/      (auth_check.php, fungsi.php, header.php, footer.php, admin_menu.php)
├── auth/          (login.php, register.php, logout.php)
├── pages/         (katalog.php, detail.php, bandingkan.php, layanan.php, galeri.php)
├── user/          (pesan.php, riwayat.php, bayar.php, invoice.php, wishlist.php, profil.php,
│                   restorasi_ajukan.php, restorasi_riwayat.php, restorasi_detail.php)
├── admin/         (dashboard.php, bus/, kategori/, layanan/, pesanan/, restorasi/,
│                   pembayaran/, galeri/, user/, laporan/)
├── assets/        (css/, js/, img/)
├── uploads/       (bus/, bukti_bayar/, restorasi/)
└── database/bus_db.sql
```

## Screenshot
(tambahkan screenshot tampilan website di sini)
