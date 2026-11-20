<?php
$BASE = '';
require __DIR__ . '/src/koneksi.php';
require __DIR__ . '/src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_check();

// Pembatasan percobaan login (5x salah => kunci 60 detik)
if (($_SESSION['lock_until'] ?? 0) > time()) redirect('index.php?pesan=terkunci');

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
if ($username === '' || $password === '') redirect('index.php?pesan=kosong');

$u = db_row('SELECT * FROM users WHERE username = ?', [$username]);

if ($u && password_verify($password, $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['id_user']  = (int)$u['id_user'];
    $_SESSION['nama']     = $u['nama'];
    $_SESSION['username'] = $u['username'];
    $_SESSION['role']     = $u['role'];
    unset($_SESSION['attempts'], $_SESSION['lock_until']);
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password_hash = ? WHERE id_user = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id_user']]);
    }
    redirect('app/index.php');
}

$_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
if ($_SESSION['attempts'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['attempts'] = 0; }
redirect('index.php?pesan=gagal');
