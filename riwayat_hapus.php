<?php
// Hapus riwayat transaksi: khusus admin, hanya lewat POST + token CSRF
require 'includes/init.php';
wajib_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('riwayat.php');
csrf_cek();
try {
    if (($_POST['aksi'] ?? '') === 'semua') {
        $db->query('DELETE FROM transaksi');
        flash('Seluruh riwayat transaksi dihapus.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $st = $db->prepare('DELETE FROM transaksi WHERE id=?');
        $st->bind_param('i', $id);
        $st->execute();
        flash($st->affected_rows ? "Transaksi #$id dihapus." : 'Transaksi tidak ditemukan.', $st->affected_rows ? 'success' : 'warning');
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    flash('Gagal menghapus transaksi.', 'danger');
}
redirect('riwayat.php');
