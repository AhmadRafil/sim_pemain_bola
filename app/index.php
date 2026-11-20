<?php
// Router utama (padanan buka_file.php pada proyek SPP): index.php?open=<modul>
$BASE = '../';
require __DIR__ . '/../src/koneksi.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/crud.php';
require __DIR__ . '/../src/layout.php';
require_login();

$daftar = ['dashboard', 'klub', 'posisi', 'pemain', 'pelatih', 'pertandingan', 'statistik', 'evaluasi', 'laporan', 'users', 'akun'];
$open = $_GET['open'] ?? 'dashboard';
if (!in_array($open, $daftar, true)) $open = 'dashboard';

$PAGE_TITLE = ucfirst($open);
$EXTRA_JS = '';
$RAW = false;

ob_start();
if (!can($open, 'view')) {
    http_response_code(403);
    $PAGE_TITLE = 'Akses ditolak';
    echo '<div class="alert alert-danger">Role Anda (' . e($_SESSION['role']) . ') tidak memiliki akses ke halaman ini.</div>';
} else {
    require __DIR__ . '/modul/' . $open . '.php';
}
$content = ob_get_clean();

if ($RAW) { echo $content; exit; }   // untuk ekspor file
layout_start($PAGE_TITLE, $open);
echo $content;
layout_end();
