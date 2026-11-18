<?php
// Minggu 14: CRUD asinkron (tanpa reload). Hanya admin. Semua respon JSON.
require __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
function out(array $a, int $kode = 200): never { http_response_code($kode); echo json_encode($a); exit; }
if (!user() || user()['role'] !== 'admin') out(['ok' => false, 'pesan' => 'Akses ditolak.'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'pesan' => 'Metode harus POST.'], 405);
csrf_cek();

$aksi = $_POST['action'] ?? '';
try {
    if ($aksi === 'list') {
        out(['ok' => true, 'data' => query($db, 'SELECT * FROM bahan_bakar ORDER BY id')]);
    }
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $db->prepare('DELETE FROM bahan_bakar WHERE id=?');
        $st->bind_param('i', $id);
        $st->execute();
        out(['ok' => true, 'pesan' => 'BBM dihapus.']);
    }
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = bersih($_POST['nama'] ?? '');
        $harga = filter_var($_POST['harga'] ?? '', FILTER_VALIDATE_INT);
        $mutu = filter_var($_POST['angka_mutu'] ?? '', FILTER_VALIDATE_INT);
        $non = filter_var($_POST['harga_non_subsidi'] ?? '', FILTER_VALIDATE_INT);
        $diesel = isset($_POST['diesel']) ? 1 : 0;
        $subsidi = isset($_POST['subsidi']) ? 1 : 0;
        $tolak = isset($_POST['kelebihan_ditolak']) && $subsidi ? 1 : 0;
        if ($nama === '' || mb_strlen($nama) > 40) out(['ok' => false, 'pesan' => 'Nama wajib diisi (maks 40 karakter).'], 422);
        if ($harga === false || $harga < 0 || $mutu === false || $mutu < 1 || $non === false || $non < 0)
            out(['ok' => false, 'pesan' => 'Harga, angka mutu, dan harga non-subsidi harus bilangan bulat valid.'], 422);
        if ($id) {
            $st = $db->prepare('UPDATE bahan_bakar SET nama=?,harga=?,angka_mutu=?,diesel=?,subsidi=?,harga_non_subsidi=?,kelebihan_ditolak=? WHERE id=?');
            $st->bind_param('siiiiiii', $nama, $harga, $mutu, $diesel, $subsidi, $non, $tolak, $id);
        } else {
            $st = $db->prepare('INSERT INTO bahan_bakar (nama,harga,angka_mutu,diesel,subsidi,harga_non_subsidi,kelebihan_ditolak) VALUES (?,?,?,?,?,?,?)');
            $st->bind_param('siiiiii', $nama, $harga, $mutu, $diesel, $subsidi, $non, $tolak);
        }
        $st->execute();
        out(['ok' => true, 'pesan' => 'BBM disimpan.']);
    }
    out(['ok' => false, 'pesan' => 'Aksi tidak dikenal.'], 400);
} catch (mysqli_sql_exception $e) {
    $pesan = match ($e->getCode()) {
        1062 => 'Nama BBM sudah ada.',
        1451 => 'BBM tidak bisa dihapus karena sudah dipakai transaksi.',
        default => 'Kesalahan database.',
    };
    if (!in_array($e->getCode(), [1062, 1451], true)) error_log($e->getMessage());
    out(['ok' => false, 'pesan' => $pesan], 409);
}
