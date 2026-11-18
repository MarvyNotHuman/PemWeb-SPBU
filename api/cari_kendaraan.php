<?php
// Pencarian kendaraan (autocomplete) - hanya mengembalikan beberapa hasil agar tetap cepat walau datanya banyak
require __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
if (!user()) { http_response_code(401); echo json_encode(['ok' => false, 'data' => []]); exit; }
$q = bersih($_GET['q'] ?? '');
$plat = '%' . str_replace(' ', '', $q) . '%';        // "r1111aa" cocok dengan "R 1111 AA"
$nama = "%$q%";
$rows = query($db, "SELECT id, plat, jenis, pemilik, kapasitas_tangki FROM kendaraan
    WHERE REPLACE(plat,' ','') LIKE ? OR pemilik LIKE ? ORDER BY plat LIMIT 8", 'ss', [$plat, $nama]);
echo json_encode(['ok' => true, 'data' => array_map(fn($r) => [
    'id' => (int)$r['id'], 'plat' => $r['plat'], 'jenis' => $r['jenis'], 'pemilik' => $r['pemilik'],
    'kapasitas' => (float)$r['kapasitas_tangki'],
], $rows)]);
