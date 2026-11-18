-- ============================================================
-- database.sql  -  SPBU UNSOED (Pemrograman Web I, IF21305)
-- Impor lewat phpMyAdmin (tab Import) atau: mysql -u root < database.sql
-- Database: spbu_web  (sesuai config/koneksi.php)
-- Tabel   : users, bahan_bakar, kendaraan, transaksi
-- ============================================================

CREATE DATABASE IF NOT EXISTS spbu_web
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spbu_web;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS kendaraan;
DROP TABLE IF EXISTS bahan_bakar;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- users : akun login (login.php, includes/auth.php)
-- role  : admin (semua fitur) / kasir (transaksi, riwayat, kendaraan)
-- password disimpan sebagai hash bcrypt (password_verify)
-- ------------------------------------------------------------
CREATE TABLE users (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username  VARCHAR(30)  NOT NULL,
  password  VARCHAR(255) NOT NULL,
  nama      VARCHAR(60)  NOT NULL,
  role      ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- bahan_bakar : CRUD di bbm.php + api/bbm.php
-- harga              : harga per liter (Rp, bilangan bulat)
-- angka_mutu         : RON (bensin) / CN (diesel)
-- diesel             : 1 = diesel (tidak boleh dipakai motor)
-- subsidi            : 1 = BBM bersubsidi (kena kuota harian)
-- harga_non_subsidi  : harga liter di atas kuota
-- kelebihan_ditolak  : 1 = lebih dari kuota ditolak (Biosolar)
-- ------------------------------------------------------------
CREATE TABLE bahan_bakar (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama               VARCHAR(40)  NOT NULL,
  harga              INT UNSIGNED NOT NULL,
  angka_mutu         INT UNSIGNED NOT NULL,
  diesel             TINYINT(1)   NOT NULL DEFAULT 0,
  subsidi            TINYINT(1)   NOT NULL DEFAULT 0,
  harga_non_subsidi  INT UNSIGNED NOT NULL DEFAULT 0,
  kelebihan_ditolak  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bbm_nama (nama)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- kendaraan : kendaraan.php, kendaraan_form.php
-- Hanya kapasitas tangki MAKSIMUM yang disimpan; isi tangki saat
-- tiba diinput per transaksi (transaksi.isi_tangki_awal).
-- Motor: jumlah_roda = 2, max_penumpang = 2 (diatur oleh aplikasi).
-- ------------------------------------------------------------
CREATE TABLE kendaraan (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis             ENUM('Mobil','Motor') NOT NULL,
  plat              VARCHAR(12)   NOT NULL,
  pemilik           VARCHAR(60)   NOT NULL,
  kapasitas_mesin   INT UNSIGNED  NOT NULL,                -- cc
  jumlah_roda       TINYINT UNSIGNED NOT NULL DEFAULT 4,
  max_penumpang     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  kapasitas_tangki  DECIMAL(6,2)  NOT NULL,                -- liter
  PRIMARY KEY (id),
  UNIQUE KEY uq_kendaraan_plat (plat),
  KEY idx_kendaraan_pemilik (pemilik)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- transaksi : transaksi.php, riwayat.php, audit.php, struk.php
-- FK RESTRICT -> menghapus kendaraan/BBM/user yang sudah dipakai
-- transaksi ditolak (error 1451, ditangani aplikasi).
-- Kuota harian dihitung dari DATE(tanggal) = CURDATE().
-- ------------------------------------------------------------
CREATE TABLE transaksi (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tanggal            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  kendaraan_id       INT UNSIGNED NOT NULL,
  bbm_id             INT UNSIGNED NOT NULL,
  user_id            INT UNSIGNED NOT NULL,
  isi_tangki_awal    DECIMAL(6,2)  NOT NULL DEFAULT 0,
  liter              DECIMAL(8,2)  NOT NULL,
  liter_subsidi      DECIMAL(8,2)  NOT NULL DEFAULT 0,
  liter_non_subsidi  DECIMAL(8,2)  NOT NULL DEFAULT 0,
  total              DECIMAL(12,2) NOT NULL,
  metode             ENUM('Tunai','QRIS','Debit') NOT NULL DEFAULT 'Tunai',
  PRIMARY KEY (id),
  KEY idx_trx_tanggal (tanggal),
  KEY idx_trx_kendaraan_bbm_tgl (kendaraan_id, bbm_id, tanggal),
  KEY idx_trx_bbm (bbm_id),
  KEY idx_trx_user (user_id),
  CONSTRAINT fk_trx_kendaraan FOREIGN KEY (kendaraan_id) REFERENCES kendaraan (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_trx_bbm       FOREIGN KEY (bbm_id)       REFERENCES bahan_bakar (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_trx_user      FOREIGN KEY (user_id)      REFERENCES users (id)       ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL
-- ============================================================

-- Login: admin / admin123  dan  kasir / kasir123
INSERT INTO users (id, username, password, nama, role) VALUES
(1, 'admin', '$2y$10$jXkYesga11zMfJ0JidxFuur42zH3sVBBZzZrX5YW1qdIZfp7ISEpq', 'Administrator', 'admin'),
(2, 'kasir', '$2y$10$PNgzemhYwTa4bv9lgZ2h2eQkcRHf99zIqXYjUWLxn2u7PleoxEU6m', 'Kasir Shift 1', 'kasir');

-- Data bahan bakar sesuai halaman Bahan Bakar (harga dapat diubah lewat menu admin).
-- Pertalite: lebih kuota dihargai Rp18.040/L; Biosolar: lebih kuota ditolak.
INSERT INTO bahan_bakar (id, nama, harga, angka_mutu, diesel, subsidi, harga_non_subsidi, kelebihan_ditolak) VALUES
(1, 'Pertalite',          10000, 90, 0, 1, 18040, 0),
(2, 'Pertamax',           15950, 92, 0, 0, 15950, 0),
(3, 'Pertamax Green 95',  19150, 95, 0, 0, 19150, 0),
(4, 'Pertamax Turbo',     19600, 98, 0, 0, 19600, 0),
(5, 'Biosolar',            6800, 48, 1, 1,     0, 1),
(6, 'Dexlite',            23700, 51, 1, 0, 23700, 0),
(7, 'Pertamina Dex',      25200, 53, 1, 0, 25200, 0);

-- Data kendaraan sesuai halaman Data Kendaraan.
-- (kapasitas tangki & jumlah penumpang tidak tampak di tampilan, diisi nilai wajar)
INSERT INTO kendaraan (id, jenis, plat, pemilik, kapasitas_mesin, jumlah_roda, max_penumpang, kapasitas_tangki) VALUES
(1, 'Mobil', 'R 1111 AA', 'Andi',   1200, 4, 5,  40.00),
(2, 'Mobil', 'R 2222 BB', 'Budi',   2400, 6, 3, 150.00),
(3, 'Motor', 'R 3333 CC', 'Citra',   150, 2, 2,   5.50),
(4, 'Motor', 'R 4444 DD', 'Dewi',    250, 2, 2,  12.00),
(5, 'Mobil', 'R 1144 K',  'Marvy',  1200, 8, 3, 300.00);

-- Contoh transaksi. liter = liter_subsidi + liter_non_subsidi.
-- Transaksi Marvy hari ini (Biosolar 150 L) membuat sisa kuota Biosolar-nya 50 / 200 L.
INSERT INTO transaksi (tanggal, kendaraan_id, bbm_id, user_id, isi_tangki_awal, liter, liter_subsidi, liter_non_subsidi, total, metode) VALUES
(NOW() - INTERVAL 3 DAY, 1, 1, 2, 10.00,  20.00,  20.00,  0.00,  200000.00, 'Tunai'),
(NOW() - INTERVAL 3 DAY, 3, 1, 2,  1.00,   4.00,   4.00,  0.00,   40000.00, 'QRIS'),
(NOW() - INTERVAL 2 DAY, 2, 5, 2, 30.00, 100.00, 100.00,  0.00,  680000.00, 'Tunai'),
(NOW() - INTERVAL 2 DAY, 4, 2, 1,  2.00,   8.00,   0.00,  8.00,  127600.00, 'QRIS'),
(NOW() - INTERVAL 1 DAY, 1, 4, 1, 12.00,  25.00,   0.00, 25.00,  490000.00, 'Debit'),
(NOW(),                  5, 5, 2,  0.00, 150.00, 150.00,  0.00, 1020000.00, 'Tunai');
