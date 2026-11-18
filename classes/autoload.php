<?php
// Minggu 15: pengantar OOP PHP (class, object, property, method) - port dari proyek PBO Java
foreach (['Pengisian', 'Pembayaran', 'BahanBakar', 'Kendaraan', 'Mobil', 'Motor', 'SPBU'] as $c) {
    require_once __DIR__ . "/$c.php";
}
