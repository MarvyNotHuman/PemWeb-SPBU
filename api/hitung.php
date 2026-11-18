<?php
require __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
if (!user()) { http_response_code(401); echo json_encode(['ok' => false, 'pesan' => 'Sesi habis, silakan login ulang.']); exit; }
$kr = query($db, 'SELECT * FROM kendaraan WHERE id=?', 'i', [(int)($_GET['kendaraan_id'] ?? 0)]);
$br = query($db, 'SELECT * FROM bahan_bakar WHERE id=?', 'i', [(int)($_GET['bbm_id'] ?? 0)]);
$liter = filter_var($_GET['liter'] ?? '', FILTER_VALIDATE_FLOAT);
$isi = filter_var($_GET['isi'] ?? '', FILTER_VALIDATE_FLOAT);
if (!$kr || !$br || $liter === false || $isi === false) {
    echo json_encode(['ok' => false, 'pesan' => 'Lengkapi kendaraan, isi tangki saat ini, BBM, dan jumlah liter.']); exit;
}
if ($galat = SPBU::cekIsiTangki((float)$isi, (float)$kr[0]['kapasitas_tangki'])) { echo json_encode(['ok' => false, 'pesan' => $galat]); exit; }
echo json_encode((new SPBU($db))->rincian(Kendaraan::fromRow($kr[0], (float)$isi), BahanBakar::fromRow($br[0]), (float)$liter));
