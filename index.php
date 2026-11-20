<?php
$BASE = '';
require __DIR__ . '/src/helpers.php';
if (is_logged_in()) redirect('app/index.php');

$pesan = [
    'gagal'    => ['danger', 'Username atau password salah.'],
    'terkunci' => ['warning', 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.'],
    'login'    => ['info', 'Silakan login untuk melanjutkan.'],
    'keluar'   => ['success', 'Anda sudah keluar dari sistem.'],
    'kosong'   => ['danger', 'Username dan password wajib diisi.'],
][$_GET['pesan'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · Manajemen Pemain Sepak Bola</title>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="src/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
  <section class="login-art" aria-hidden="false">
    <svg viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="3" aria-hidden="true">
      <rect x="40" y="40" width="520" height="720"/>
      <line x1="40" y1="400" x2="560" y2="400"/>
      <circle cx="300" cy="400" r="90"/><circle cx="300" cy="400" r="5" fill="rgba(255,255,255,.14)"/>
      <rect x="150" y="40" width="300" height="130"/><rect x="220" y="40" width="160" height="50"/>
      <rect x="150" y="630" width="300" height="130"/><rect x="220" y="710" width="160" height="50"/>
      <path d="M240 170a60 60 0 0 0 120 0"/><path d="M240 630a60 60 0 0 1 120 0"/>
    </svg>
    <h1>Kelola pemain,<br>pertandingan,<br>dan performa.</h1>
    <p>Data klub, statistik gol dan assist, serta evaluasi pelatih dalam satu tempat.</p>
  </section>
  <section class="login-form">
    <form action="login_validasi.php" method="post" autocomplete="off">
      <h2 class="mb-1" style="font-size:34px;font-weight:700">Masuk</h2>
      <p class="text-muted mb-4">Gunakan akun Admin, Pelatih, atau Pengelola.</p>
      <?php if ($pesan): ?><div class="alert alert-<?= $pesan[0] ?>" role="alert"><?= e($pesan[1]) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control form-control-lg" id="username" name="username" required autofocus maxlength="50">
      </div>
      <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <input class="form-control form-control-lg" id="password" name="password" type="password" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Masuk</button>
    </form>
  </section>
</div>
</body>
</html>
