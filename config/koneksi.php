<?php
// Minggu 7: koneksi MySQLi + penanganan error (exception, bukan pesan mentah ke pengguna)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli('localhost', 'root', '', 'spbu_web');
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die('Koneksi database gagal. Pastikan MySQL aktif dan database.sql sudah diimpor.');
}
