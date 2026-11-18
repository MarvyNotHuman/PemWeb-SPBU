<?php
// Data statistik dashboard (JSON): ringkasan, tren harian, komposisi BBM, subsidi, metode, top kendaraan
require __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
if (!user()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }

$hari = (int)($_GET['hari'] ?? 7);
if (!in_array($hari, [7, 30, 90], true)) $hari = 7;
$dari = query($db, 'SELECT CURDATE() - INTERVAL ? DAY d', 'i', [$hari - 1])[0]['d'];

$ring = query($db, 'SELECT COUNT(*) trx, COALESCE(SUM(total),0) omzet, COALESCE(SUM(liter),0) liter,
    COALESCE(SUM(liter_subsidi),0) sub, COALESCE(SUM(liter_non_subsidi),0) non FROM transaksi WHERE tanggal >= ?', 's', [$dari])[0];

$map = [];
foreach (query($db, 'SELECT DATE(tanggal) d, COUNT(*) trx, SUM(total) omzet, SUM(liter) liter FROM transaksi
    WHERE tanggal >= ? GROUP BY DATE(tanggal)', 's', [$dari]) as $r) $map[$r['d']] = $r;
$harian = [];
$tgl = new DateTime($dari);
for ($i = 0; $i < $hari; $i++, $tgl->modify('+1 day')) {
    $k = $tgl->format('Y-m-d');
    $harian[] = ['tanggal' => $k, 'trx' => (int)($map[$k]['trx'] ?? 0), 'omzet' => (float)($map[$k]['omzet'] ?? 0), 'liter' => (float)($map[$k]['liter'] ?? 0)];
}

$num = fn(array $rows, array $kunci) => array_map(function ($r) use ($kunci) {
    foreach ($kunci as $k) $r[$k] = (float)$r[$k];
    return $r;
}, $rows);

echo json_encode([
    'ok' => true, 'hari' => $hari, 'dari' => $dari,
    'ringkas' => ['trx' => (int)$ring['trx'], 'omzet' => (float)$ring['omzet'], 'liter' => (float)$ring['liter'], 'sub' => (float)$ring['sub'], 'non' => (float)$ring['non']],
    'harian' => $harian,
    'bbm' => $num(query($db, 'SELECT b.nama, SUM(t.liter) liter, SUM(t.total) omzet FROM transaksi t JOIN bahan_bakar b ON b.id=t.bbm_id
        WHERE t.tanggal >= ? GROUP BY b.id, b.nama ORDER BY liter DESC', 's', [$dari]), ['liter', 'omzet']),
    'metode' => $num(query($db, 'SELECT metode, COUNT(*) trx, SUM(total) omzet FROM transaksi WHERE tanggal >= ? GROUP BY metode', 's', [$dari]), ['trx', 'omzet']),
    'top' => $num(query($db, 'SELECT k.plat, k.pemilik, k.jenis, COUNT(*) trx, SUM(t.liter) liter, SUM(t.total) omzet FROM transaksi t
        JOIN kendaraan k ON k.id=t.kendaraan_id WHERE t.tanggal >= ? GROUP BY k.id, k.plat, k.pemilik, k.jenis ORDER BY omzet DESC LIMIT 5', 's', [$dari]), ['trx', 'liter', 'omzet']),
]);
