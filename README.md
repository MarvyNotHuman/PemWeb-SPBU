# SPBU UNSOED - Aplikasi Web (Pemrograman Web I, IF21305)

Pengembangan proyek **PBO SPBU (Java, terminal)** menjadi aplikasi web dinamis: PHP + MySQLi + Bootstrap + JavaScript.

## Aturan tangki
- Data kendaraan hanya menyimpan **kapasitas tangki maksimum**.
- **Isi tangki saat ini** diinput pada halaman Transaksi (kondisinya berbeda-beda tiap kunjungan), dipakai untuk menghitung sisa kapasitas, dan dicatat di transaksi (`isi_tangki_awal`).
- Daftar kendaraan menampilkan **sisa kuota subsidi hari ini**.
- Database lama: jalankan `migrasi_tangki.sql` sekali.

## Cara menjalankan (XAMPP / Laragon, PHP >= 8.1)
1. Salin folder ini ke `htdocs/spbu_web`.
2. Jalankan Apache dan MySQL, lalu impor `database.sql` lewat phpMyAdmin.
3. Sesuaikan user/password MySQL di `config/koneksi.php` bila perlu.
4. Buka `http://localhost/spbu_web/` — login `admin / admin123` atau `kasir / kasir123`.

## Pemetaan ke RPS
| Minggu | Materi | Lokasi |
|---|---|---|
| 1 | Arsitektur web dinamis, request-response | Alur `form -> PHP -> MySQL -> HTML/JSON` di seluruh halaman |
| 2-3 | PHP dasar, fungsi, array, include/require | `includes/fungsi.php`, `init.php`, `header.php`, `footer.php` |
| 4 | Form GET/POST, validasi & sanitasi, Bootstrap | `kendaraan_form.php`, `transaksi.php`, `login.php` (`bersih()`, `e()`, `filter_var`) |
| 5 | Session, cookie, autentikasi, hak akses | `login.php`, `logout.php`, `includes/auth.php` (role admin/kasir, cookie "ingat username") |
| 6 | Basis data relasional, SQL dasar | `database.sql` (4 tabel, PK/FK) |
| 7 | MySQLi, prepared statement, error handling | `config/koneksi.php`, `query()`, try/catch `mysqli_sql_exception` |
| 9 | CRUD, pencarian, pagination | `kendaraan*.php`, `riwayat.php`, `paginasi()` |
| 10-11 | JavaScript, DOM, event, validasi klien | `assets/app.js` (form kendaraan) |
| 12 | Asinkron & JSON | respon JSON `api/*.php`, `fetch` + `async/await` |
| 13 | Permintaan data asinkron ke endpoint PHP | `api/hitung.php` (rincian harga/kuota di halaman transaksi) |
| 14 | CRUD tanpa reload | `bbm.php` + `api/bbm.php` |
| 9+ | Statistik & audit transaksi | `index.php` + `api/statistik.php` (Chart.js), `audit.php`, `audit_ekspor.php`, `includes/ekspor.php` (CSV/XLSX/PDF) |
| 15 | OOP PHP (class, object, property, method) | `classes/` — port dari proyek PBO |

## Port dari proyek PBO Java
| Java | PHP |
|---|---|
| `Kendaraan` (abstract), `Mobil`, `Motor` | `classes/Kendaraan.php`, `Mobil.php`, `Motor.php` (extends, `parent::`, override) |
| interface `Pengisian`, `Pembayaran` | `classes/Pengisian.php`, `Pembayaran.php` (multiple interface) |
| `BahanBakar` (`private final`) | `BahanBakar.php` (`public readonly`; overloading -> parameter opsional) |
| `SPBU` (kuota di memori) | `SPBU.php` (kuota harian dihitung dari tabel `transaksi`, jadi berganti hari otomatis) |
| Aturan bisnis (subsidi, batas cc, diesel, kapasitas tangki) | tetap sama, dicek di `SPBU::rincian()` |

Hak akses: **kasir** — transaksi, riwayat, kendaraan (tambah/ubah). **admin** — ditambah hapus kendaraan dan CRUD bahan bakar.

## Tidak dibawa dari versi Java
Menu "Demonstrasi konsep" (10 persyaratan PBO) tidak dibuat karena tidak relevan dengan RPS Pemrograman Web I.
"# PemWeb-SPBU" 
