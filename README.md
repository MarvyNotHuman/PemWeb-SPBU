# ⛽ SPBU UNSOED - Aplikasi Web

> Tugas mata kuliah **Pemrograman Web I (IF21305)** — pengembangan proyek PBO SPBU berbasis Java terminal menjadi aplikasi web dinamis menggunakan PHP, MySQLi, Bootstrap, dan JavaScript.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)

---

## Tentang Proyek

**SPBU UNSOED** adalah aplikasi web untuk mengelola transaksi pengisian BBM, data kendaraan, data bahan bakar, kuota subsidi harian, riwayat transaksi, dashboard statistik, audit, serta ekspor laporan.

Proyek ini mengembangkan konsep PBO SPBU sebelumnya menjadi aplikasi web yang dinamis dengan alur `form -> PHP -> MySQL -> HTML/JSON`.

---

## Aturan Tangki

- Data kendaraan hanya menyimpan **kapasitas tangki maksimum**.
- **Isi tangki saat ini** diinput pada halaman transaksi karena kondisinya dapat berbeda-beda pada setiap kunjungan.
- Nilai isi tangki awal digunakan untuk menghitung sisa kapasitas dan dicatat pada transaksi melalui kolom `isi_tangki_awal`.
- Daftar kendaraan menampilkan **sisa kuota subsidi hari ini**.
- Kuota harian dihitung dari transaksi pada tanggal berjalan, sehingga batas kuota akan berganti secara otomatis setiap hari.

---

## Fitur Utama

- Autentikasi pengguna berbasis session dan cookie pengingat username
- Pembatasan hak akses untuk peran **Admin** dan **Kasir**
- CRUD data kendaraan dengan validasi jenis mobil atau motor
- CRUD data bahan bakar beserta harga, RON/CN, status diesel, dan subsidi
- Penghitungan harga, isi tangki, dan kuota subsidi pada halaman transaksi
- Pencatatan metode pembayaran **Tunai**, **QRIS**, dan **Debit**
- Pencarian, pagination, riwayat, detail, dan cetak struk transaksi
- Dashboard statistik transaksi menggunakan Chart.js
- Audit transaksi dan ekspor laporan ke CSV, XLSX, serta PDF
- Operasi CRUD bahan bakar tanpa reload menggunakan API JSON

---

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP 8.1+ dengan MySQLi |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, dan Bootstrap 5 |
| JavaScript | Vanilla JavaScript, Fetch API, dan Chart.js |
| Arsitektur | PHP native, session-based authentication, REST-like API JSON |
| Paradigma | PHP OOP untuk model kendaraan, bahan bakar, dan aturan SPBU |

---

## Akun Demo

Akun bawaan tersedia pada [`database.sql`](database.sql).

| Peran | Username | Password | Hak Akses |
|---|---|---|---|
| Admin | `admin` | `admin123` | Seluruh fitur, termasuk hapus kendaraan dan CRUD bahan bakar |
| Kasir | `kasir` | `kasir123` | Transaksi, riwayat, serta tambah dan ubah kendaraan |

> Catatan: akun demo digunakan untuk kebutuhan pengembangan dan demonstrasi. Ganti password dan hapus akun demo sebelum aplikasi digunakan pada lingkungan produksi.

---

## Cara Menjalankan

Persyaratan:

- PHP **8.1 atau lebih baru**
- Apache
- MySQL atau MariaDB
- XAMPP atau Laragon
- Ekstensi PHP `mysqli`

Langkah menjalankan aplikasi:

1. Salin folder proyek ke folder web server, misalnya `htdocs/spbu_web` pada XAMPP.

   ```text
   C:\xampp\htdocs\spbu_web
   ```

2. Jalankan Apache dan MySQL melalui XAMPP atau Laragon.

3. Impor `database.sql` melalui phpMyAdmin atau terminal:

   ```bash
   mysql -u root -p < database.sql
   ```

4. Sesuaikan konfigurasi MySQL pada [`config/koneksi.php`](config/koneksi.php) bila perlu:

   ```php
   $db = new mysqli('localhost', 'root', '', 'spbu_web');
   ```

5. Buka aplikasi melalui browser:

   ```text
   http://localhost/spbu_web/
   ```

6. Login menggunakan akun **`admin / admin123`** atau **`kasir / kasir123`**.

---

## Struktur Proyek

```text
PemWeb-SPBU/
├── api/                  # Endpoint API JSON untuk penghitungan dan BBM
├── assets/
│   ├── app.js            # Interaksi dan validasi JavaScript
│   ├── statistik.js      # Dashboard statistik menggunakan Chart.js
│   └── style.css         # Gaya utama aplikasi
├── classes/              # Model OOP kendaraan, BBM, SPBU, dan pembayaran
├── config/
│   └── koneksi.php       # Konfigurasi koneksi MySQLi
├── includes/
│   ├── auth.php          # Pemeriksaan autentikasi dan hak akses
│   ├── fungsi.php        # Fungsi umum dan sanitasi
│   ├── init.php          # Inisialisasi aplikasi
│   ├── header.php        # Header halaman
│   ├── footer.php        # Footer halaman
│   ├── laporan.php       # Pengolahan laporan
│   └── ekspor.php        # Ekspor CSV, XLSX, dan PDF
├── bbm.php               # Manajemen bahan bakar
├── kendaraan.php         # Daftar kendaraan
├── kendaraan_form.php    # Tambah dan ubah kendaraan
├── kendaraan_hapus.php   # Penghapusan kendaraan
├── transaksi.php         # Form transaksi pengisian
├── riwayat.php           # Riwayat transaksi
├── riwayat_hapus.php     # Penghapusan riwayat
├── struk.php             # Cetak struk transaksi
├── audit.php             # Audit transaksi
├── audit_ekspor.php      # Ekspor hasil audit
├── login.php             # Login pengguna
├── logout.php            # Logout pengguna
├── index.php             # Dashboard utama
└── database.sql          # Skema dan data awal database
```

---

## Struktur Database

Database `spbu_web` terdiri dari empat tabel utama:

| Tabel | Fungsi |
|---|---|
| `users` | Akun pengguna, nama, dan peran admin/kasir |
| `bahan_bakar` | Jenis BBM, harga, subsidi, angka mutu, dan aturan diesel |
| `kendaraan` | Data mobil/motor beserta spesifikasi dan kapasitas tangki |
| `transaksi` | Riwayat pengisian, kuota, pembayaran, dan identitas pengguna |

Relasi transaksi menggunakan foreign key ke tabel `kendaraan`, `bahan_bakar`, dan `users`. Penghapusan data dibatasi agar riwayat transaksi tidak kehilangan referensi.

---

## Pemetaan ke RPS

| Minggu | Materi | Lokasi |
|---|---|---|
| 1 | Arsitektur web dinamis, request-response | Alur `form -> PHP -> MySQL -> HTML/JSON` di seluruh halaman |
| 2-3 | PHP dasar, fungsi, array, include/require | `includes/fungsi.php`, `includes/init.php`, `includes/header.php`, `includes/footer.php` |
| 4 | Form GET/POST, validasi & sanitasi, Bootstrap | `kendaraan_form.php`, `transaksi.php`, `login.php` (`bersih()`, `e()`, `filter_var`) |
| 5 | Session, cookie, autentikasi, hak akses | `login.php`, `logout.php`, `includes/auth.php` (role admin/kasir, cookie "ingat username") |
| 6 | Basis data relasional, SQL dasar | `database.sql` (4 tabel, PK/FK) |
| 7 | MySQLi, prepared statement, error handling | `config/koneksi.php`, `query()`, try/catch `mysqli_sql_exception` |
| 9 | CRUD, pencarian, pagination | `kendaraan*.php`, `riwayat.php`, `paginasi()` |
| 10-11 | JavaScript, DOM, event, validasi klien | `assets/app.js` (form kendaraan) |
| 12 | Asinkron & JSON | Respon JSON `api/*.php`, `fetch` + `async/await` |
| 13 | Permintaan data asinkron ke endpoint PHP | `api/hitung.php` (rincian harga/kuota di halaman transaksi) |
| 14 | CRUD tanpa reload | `bbm.php` + `api/bbm.php` |
| 9+ | Statistik & audit transaksi | `index.php` + `api/statistik.php` (Chart.js), `audit.php`, `audit_ekspor.php`, `includes/ekspor.php` (CSV/XLSX/PDF) |
| 15 | OOP PHP (class, object, property, method) | `classes/` — port dari proyek PBO |

---

## Port dari Proyek PBO Java

| Java | PHP |
|---|---|
| `Kendaraan` (abstract), `Mobil`, `Motor` | `classes/Kendaraan.php`, `Mobil.php`, `Motor.php` (extends, `parent::`, override) |
| Interface `Pengisian`, `Pembayaran` | `classes/Pengisian.php`, `Pembayaran.php` (multiple interface) |
| `BahanBakar` (`private final`) | `classes/BahanBakar.php` (`public readonly`; overloading -> parameter opsional) |
| `SPBU` (kuota di memori) | `classes/SPBU.php` (kuota harian dihitung dari tabel `transaksi`, jadi berganti hari otomatis) |
| Aturan bisnis subsidi, batas CC, diesel, dan kapasitas tangki | Tetap dipertahankan dan dicek pada `SPBU::rincian()` |

Hak akses:

- **Kasir** — transaksi, riwayat, serta tambah/ubah kendaraan.
- **Admin** — ditambah hak hapus kendaraan dan CRUD bahan bakar.

## Tidak Dibawa dari Versi Java

Menu "Demonstrasi konsep" yang berisi 10 persyaratan PBO tidak dibuat karena tidak relevan dengan RPS Pemrograman Web I.

---

## Lisensi

Proyek ini dikembangkan untuk tujuan pembelajaran. Gunakan dan kembangkan sesuai kebutuhan akademik dengan tetap mencantumkan sumber proyek.