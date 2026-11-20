<?php
// Koneksi database (sesuaikan jika password root MySQL Anda tidak kosong)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli('localhost', 'root', '', 'db_sepak_bola');
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL di XAMPP sudah Start dan database "db_sepak_bola" sudah diimpor (lihat README.md).');
}
