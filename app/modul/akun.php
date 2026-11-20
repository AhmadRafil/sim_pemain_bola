<?php
$PAGE_TITLE = 'Pengaturan Akun';
$uid = (int)$_SESSION['id_user'];
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim($_POST['nama'] ?? '');
    $lama = (string)($_POST['password_lama'] ?? '');
    $baru = (string)($_POST['password_baru'] ?? '');
    $konf = (string)($_POST['password_konf'] ?? '');
    if ($nama === '' || mb_strlen($nama) > 100) $err[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if ($baru !== '') {
        $u = db_row('SELECT password_hash FROM users WHERE id_user = ?', [$uid]);
        if (!password_verify($lama, $u['password_hash'])) $err[] = 'Password lama salah.';
        if (strlen($baru) < 6) $err[] = 'Password baru minimal 6 karakter.';
        if ($baru !== $konf) $err[] = 'Konfirmasi password tidak sama.';
    }
    if (!$err) {
        db_exec('UPDATE users SET nama = ? WHERE id_user = ?', [$nama, $uid]);
        if ($baru !== '') db_exec('UPDATE users SET password_hash = ? WHERE id_user = ?', [password_hash($baru, PASSWORD_DEFAULT), $uid]);
        $_SESSION['nama'] = $nama;
        flash('success', 'Pengaturan akun berhasil disimpan.');
        redirect('index.php?open=akun');
    }
}
$me = db_row('SELECT * FROM users WHERE id_user = ?', [$uid]);
?>
<div class="panel" style="max-width:640px"><div class="panel-head"><h2>Pengaturan Akun</h2></div><div class="panel-body">
<?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
<form method="post" class="row g-3" novalidate>
  <?= csrf_field() ?>
  <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?= e($me['username']) ?>" disabled></div>
  <div class="col-md-6"><label class="form-label">Role</label><input class="form-control" value="<?= e(ucfirst($me['role'])) ?>" disabled></div>
  <div class="col-12"><label class="form-label" for="nama">Nama Lengkap</label><input class="form-control" id="nama" name="nama" value="<?= e($_POST['nama'] ?? $me['nama']) ?>" maxlength="100" required></div>
  <div class="col-12"><hr class="my-1"><strong>Ganti password</strong> <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small></div>
  <div class="col-12"><label class="form-label" for="pl">Password lama</label><input class="form-control" type="password" id="pl" name="password_lama" autocomplete="current-password"></div>
  <div class="col-md-6"><label class="form-label" for="pb">Password baru</label><input class="form-control" type="password" id="pb" name="password_baru" autocomplete="new-password"></div>
  <div class="col-md-6"><label class="form-label" for="pk">Ulangi password baru</label><input class="form-control" type="password" id="pk" name="password_konf" autocomplete="new-password"></div>
  <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan perubahan</button></div>
</form></div></div>
