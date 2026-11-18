<?php
require 'includes/init.php';
wajib_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kendaraan.php');
csrf_cek();
$id = (int)($_POST['id'] ?? 0);
try {
    $st = $db->prepare('DELETE FROM kendaraan WHERE id=?');
    $st->bind_param('i', $id);
    $st->execute();
    flash('Kendaraan dihapus.');
} catch (mysqli_sql_exception $e) {
    flash($e->getCode() === 1451 ? 'Kendaraan tidak bisa dihapus karena sudah memiliki transaksi.' : 'Gagal menghapus data.', 'danger');
}
redirect('kendaraan.php');
