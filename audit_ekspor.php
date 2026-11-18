<?php
// Ekspor audit transaksi: csv | xlsx | pdf. Memakai filter yang sama persis dengan audit.php.
require 'includes/init.php';
wajib_login();
require_once 'includes/laporan.php';
require_once 'includes/ekspor.php';

$format = $_GET['format'] ?? 'pdf';
if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) { http_response_code(400); die('Format tidak dikenal.'); }

[$f, $where, $tp, $p] = audit_filter($db, $_GET);
$ring = audit_ringkas($db, $where, $tp, $p);
const BATAS_EKSPOR = 20000;
$data = audit_baris($db, $where, $tp, $p, BATAS_EKSPOR);
foreach ($data as $i => &$r) $r['no'] = $i + 1;
unset($r);
$total = ['tanggal' => 'TOTAL', 'liter' => $ring['liter'], 'liter_subsidi' => $ring['sub'], 'liter_non_subsidi' => $ring['non'], 'total' => $ring['omzet']];

// Keterangan filter untuk kepala laporan
$ket = [];
if ($f['bbm'])    $ket[] = 'BBM: ' . (query($db, 'SELECT nama FROM bahan_bakar WHERE id=?', 'i', [$f['bbm']])[0]['nama'] ?? '-');
if ($f['metode']) $ket[] = 'Metode: ' . $f['metode'];
if ($f['kasir'])  $ket[] = 'Kasir: ' . (query($db, 'SELECT nama FROM users WHERE id=?', 'i', [$f['kasir']])[0]['nama'] ?? '-');
if ($f['q'] !== '') $ket[] = 'Pencarian: ' . $f['q'];
$meta = [
    'Rentang: ' . tgl_id($f['dari']) . ' s.d. ' . tgl_id($f['sampai']) . '   |   Filter: ' . ($ket ? implode(', ', $ket) : 'semua'),
    'Jumlah transaksi: ' . number_format($ring['trx'], 0, ',', '.') . ($ring['trx'] > BATAS_EKSPOR ? ' (hanya ' . number_format(BATAS_EKSPOR, 0, ',', '.') . ' baris terbaru ditampilkan; total di bawah mencakup semua)' : ''),
    'Dicetak oleh: ' . user()['nama'] . ' (' . user()['role'] . ') pada ' . date('d/m/Y H:i') . ' WIB',
];
$judul = SPBU::NAMA . ' - Audit Transaksi';
$nama = "audit-transaksi_{$f['dari']}_sd_{$f['sampai']}";
$kolom = kolom_audit();

try {
    switch ($format) {
        case 'csv':  kirim_unduhan("$nama.csv", 'text/csv; charset=utf-8', bangun_csv($kolom, $data, $total));
        case 'xlsx': kirim_unduhan("$nama.xlsx", 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', bangun_xlsx($judul, $kolom, $data, $meta, $total));
        default:     kirim_unduhan("$nama.pdf", 'application/pdf', bangun_pdf($judul, $kolom, $data, $meta, $total));
    }
} catch (RuntimeException $e) {
    error_log($e->getMessage());
    flash($e->getMessage(), 'danger');
    redirect('audit.php?' . http_build_query($f));
}
