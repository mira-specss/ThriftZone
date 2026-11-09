# ThriftZone – README

**ThriftZone** adalah marketplace preloved (second-hand) berbasis PHP + MySQL yang berjalan di XAMPP.  
Dibuat dengan HTML, CSS, Vanilla JavaScript, PHP (PDO + prepared statements), dan MySQL.

---

## Prasyarat

| Software | Versi minimum |
|----------|--------------|
| XAMPP    | 8.x (PHP 8.1+, Apache, MySQL/MariaDB) |
| Browser  | Chrome, Firefox, Edge (modern) |

---

## Langkah Instalasi

### 1. Letakkan folder proyek

Pastikan folder proyek ada di:
```
C:\xampp\htdocs\thriftzone\
```
(atau `/var/www/html/thriftzone/` di Linux LAMP).

### 2. Aktifkan ekstensi PHP GD (untuk fitur upload foto)

> Ekstensi GD diperlukan agar generate_placeholders.php bisa berjalan.  
> Untuk web server biasa, GD sudah cukup untuk resize gambar.

**Windows (XAMPP):**
1. Buka `C:\xampp\php\php.ini` sebagai Administrator.
2. Cari baris `;extension=gd` dan hapus titik koma di depannya → `extension=gd`.
3. Restart Apache di XAMPP Control Panel.

**Ubuntu LAMP:**
```bash
sudo apt install php-gd
sudo systemctl restart apache2
```

### 3. Import database

**Cara A – phpMyAdmin (mudah):**
1. Buka browser → `http://localhost/phpmyadmin`
2. Klik **New** (kiri atas) → isi nama database: `thriftzone` → klik **Create**.
3. Pilih database `thriftzone` di sidebar.
4. Klik tab **Import** → pilih file `c:\xampp\htdocs\thriftzone\database.sql`.
5. Klik **Go**.

**Cara B – Command line:**
```bash
# Windows (jalankan di cmd sebagai Administrator)
C:\xampp\mysql\bin\mysql.exe -u root thriftzone < C:\xampp\htdocs\thriftzone\database.sql

# Ubuntu
mysql -u root -p thriftzone < /var/www/html/thriftzone/database.sql
```

### 4. Jalankan situs

1. Buka XAMPP Control Panel.
2. Klik **Start** di baris **Apache** dan **MySQL**.
3. Buka browser → **http://localhost/thriftzone/**

---

## Akun Demo

| Email | Password | Keterangan |
|-------|----------|-----------|
| budi@example.com | password123 | Demo user 1 |
| siti@example.com | password123 | Demo user 2 |

---

## Struktur Folder

```
thriftzone/
├── api/                   # JSON API endpoints
│   ├── cart.php           # Keranjang (add/remove)
│   ├── items.php          # Filter barang (category page)
│   ├── payment.php        # Konfirmasi pembayaran
│   └── wishlist.php       # Wishlist (add/remove)
├── assets/
│   ├── css/style.css      # Stylesheet utama (CSS variables, responsive)
│   ├── img/               # Logo, QRIS placeholder
│   │   ├── logo.png
│   │   ├── qris.png
│   │   └── sample/        # Placeholder gambar barang
│   └── js/main.js         # JavaScript global
├── config/
│   └── db.php             # Koneksi PDO (satu tempat konfigurasi DB)
├── includes/
│   ├── header.php         # Header + category bar (shared)
│   └── footer.php         # Footer (shared)
├── uploads/               # Foto upload dari pengguna (auto-created)
├── index.php              # Beranda – grid barang acak
├── category.php           # Halaman kategori + filter live
├── item.php               # Detail barang
├── wishlist.php           # Wishlist
├── cart.php               # Keranjang belanja
├── checkout.php           # Form alamat & buat pesanan
├── payment.php            # Simulasi QRIS payment
├── thankyou.php           # Konfirmasi pesanan
├── orders.php             # Pesanan saya
├── sell.php               # Upload & jual barang
├── search.php             # Halaman hasil pencarian
├── register.php           # Daftar akun
├── login.php              # Masuk
├── logout.php             # Keluar
├── generate_placeholders.php  # Script generate gambar (jalankan sekali)
├── database.sql           # Schema + seed data
├── .htaccess              # Konfigurasi Apache
└── README.md              # Dokumen ini
```

---

## Konfigurasi Database

Edit `config/db.php` jika perlu mengubah kredensial:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'thriftzone');
define('DB_USER', 'root');   // Ganti untuk produksi
define('DB_PASS', '');        // Ganti untuk produksi
```

---

## Deploy ke Ubuntu LAMP (Cloud)

```bash
# 1. Install LAMP
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-gd libapache2-mod-php -y

# 2. Upload folder
sudo cp -r thriftzone/ /var/www/html/thriftzone/
sudo chown -R www-data:www-data /var/www/html/thriftzone/
sudo chmod -R 755 /var/www/html/thriftzone/
sudo chmod -R 775 /var/www/html/thriftzone/uploads/

# 3. Import database
sudo mysql -u root -e "CREATE DATABASE thriftzone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root thriftzone < /var/www/html/thriftzone/database.sql

# 4. Aktifkan mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

> **Catatan produksi:** Ganti `DB_USER` dan `DB_PASS` di `config/db.php` dengan user MySQL yang bukan root.

---

## Integrasi Midtrans (Payment Gateway)

Halaman `payment.php` dan `api/payment.php` sudah disiapkan dengan komentar panduan.  
Untuk menggunakan Midtrans Sandbox:

1. Daftar di [https://dashboard.midtrans.com](https://dashboard.midtrans.com).
2. Install SDK: `composer require midtrans/midtrans-php`.
3. Di `checkout.php`, buat transaksi Snap dan simpan `snap_token`.
4. Di `payment.php`, load Snap JS dan panggil `snap.pay(snapToken)`.
5. Tangani webhook notifikasi di `api/payment.php` untuk memverifikasi pembayaran.

---

## Palet Warna (CSS Variables)

| Variabel | Nilai | Kegunaan |
|----------|-------|----------|
| `--color-navy` | `#0F2A43` | Header, judul |
| `--color-teal` | `#0D9488` | Tombol utama, link |
| `--color-teal-light` | `#CCFBF1` | Hover, highlight |
| `--color-bg` | `#F4F8FA` | Background halaman |
| `--color-card` | `#FFFFFF` | Kartu produk |
| `--color-gray` | `#64748B` | Teks sekunder |
| `--color-amber` | `#F59E0B` | Badge Sold/New |

