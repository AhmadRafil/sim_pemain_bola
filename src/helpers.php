<?php
// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

// $BASE = path relatif dari halaman saat ini ke folder root project ('' atau '../')
$BASE = $BASE ?? '';

// ---------- Umum ----------
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flash_show(): string {
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $out .= '<div class="alert alert-' . e($t) . ' alert-dismissible fade show" role="alert">' . e($m)
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}
function qs(array $merge = []): string {   // query string saat ini + perubahan
    return '?' . http_build_query(array_filter(array_merge($_GET, $merge), fn($v) => $v !== '' && $v !== null));
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
    if (!$ok) { http_response_code(419); exit('Sesi formulir tidak valid atau kedaluwarsa. Kembali ke halaman sebelumnya dan muat ulang.'); }
}

// ---------- Autentikasi & hak akses ----------
const ALL_ROLES = ['admin', 'pelatih', 'pengelola'];
function perms(): array {
    return [
        'dashboard'    => ['view' => ALL_ROLES],
        'klub'         => ['view' => ALL_ROLES, 'create' => ['admin'], 'edit' => ['admin'], 'delete' => ['admin']],
        'posisi'       => ['view' => ALL_ROLES, 'create' => ['admin'], 'edit' => ['admin'], 'delete' => ['admin']],
        'pemain'       => ['view' => ALL_ROLES, 'create' => ['admin'], 'edit' => ['admin'], 'delete' => ['admin']],
        'pelatih'      => ['view' => ['admin', 'pelatih'], 'create' => ['admin'], 'edit' => ['admin'], 'delete' => ['admin']],
        'pertandingan' => ['view' => ALL_ROLES, 'create' => ['admin', 'pelatih'], 'edit' => ['admin', 'pelatih'], 'delete' => ['admin']],
        'statistik'    => ['view' => ALL_ROLES, 'create' => ['admin', 'pelatih'], 'edit' => ['admin', 'pelatih'], 'delete' => ['admin', 'pelatih']],
        'evaluasi'     => ['view' => ALL_ROLES, 'create' => ['admin', 'pelatih'], 'edit' => ['admin', 'pelatih'], 'delete' => ['admin', 'pelatih']],
        'laporan'      => ['view' => ALL_ROLES],
        'users'        => ['view' => ['admin'], 'create' => ['admin'], 'edit' => ['admin'], 'delete' => ['admin']],
        'akun'         => ['view' => ALL_ROLES],
    ];
}
function can(string $modul, string $aksi): bool {
    return in_array($_SESSION['role'] ?? '', perms()[$modul][$aksi] ?? [], true);
}
function is_logged_in(): bool { return !empty($_SESSION['id_user']); }
function require_login(): void {
    global $BASE;
    if (!is_logged_in()) redirect($BASE . 'index.php?pesan=login');
}

// ---------- Database (prepared statement) ----------
function db_q(string $sql, array $p = []): mysqli_stmt {
    global $koneksi;
    $st = $koneksi->prepare($sql);
    if ($p) {
        $types = '';
        foreach ($p as $v) $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        $st->bind_param($types, ...$p);
    }
    $st->execute();
    return $st;
}
function db_all(string $sql, array $p = []): array {
    $st = db_q($sql, $p);
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}
function db_row(string $sql, array $p = []): ?array { return db_all($sql, $p)[0] ?? null; }
function db_val(string $sql, array $p = []) {
    $r = db_row($sql, $p);
    return $r ? array_values($r)[0] : null;
}
function db_exec(string $sql, array $p = []): int {
    $st = db_q($sql, $p);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}
function db_insert_id(): int { global $koneksi; return (int)$koneksi->insert_id; }

// ---------- Upload gambar ----------
function upload_image(array $file, string $sub, ?string &$error): ?string {
    global $BASE;
    $error = null;
    if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Unggahan gagal (kode ' . $file['error'] . ').'; return null; }
    if ($file['size'] > 2 * 1024 * 1024) { $error = 'Ukuran foto maksimal 2 MB.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($file['tmp_name'])) { $error = 'Format harus JPG, PNG, atau WEBP.'; return null; }
    $dir = __DIR__ . '/../uploads/' . $sub;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) { $error = 'Gagal menyimpan file.'; return null; }
    return $name;
}
function delete_upload(?string $name, string $sub): void {
    if ($name && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $name)) @unlink(__DIR__ . '/../uploads/' . $sub . '/' . $name);
}
function avatar(?string $file, string $sub, string $nama, int $size = 40): string {
    global $BASE;
    $s = 'width:' . $size . 'px;height:' . $size . 'px;';
    if ($file) return '<img class="avatar" style="' . $s . '" src="' . e($BASE . 'uploads/' . $sub . '/' . $file) . '" alt="' . e($nama) . '">';
    $ini = '';
    foreach (array_slice(preg_split('/\s+/', trim($nama)), 0, 2) as $w) $ini .= mb_strtoupper(mb_substr($w, 0, 1));
    return '<span class="avatar avatar-ini" style="' . $s . 'font-size:' . round($size * .38) . 'px">' . e($ini) . '</span>';
}

// ---------- Format ----------
function bulan_indo(int $m): string {
    return ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$m];
}
function tgl(?string $d, bool $jam = false): string {
    if (!$d) return '-';
    $t = strtotime($d);
    return date('j', $t) . ' ' . bulan_indo((int)date('n', $t)) . ' ' . date('Y', $t) . ($jam ? ', ' . date('H:i', $t) : '');
}
function usia(string $lahir): int { return (int)(new DateTime($lahir))->diff(new DateTime('today'))->y; }
function badge_status(string $s): string {
    $c = ['aktif' => 'success', 'cedera' => 'danger', 'dipinjamkan' => 'info', 'nonaktif' => 'secondary',
          'selesai' => 'success', 'dijadwalkan' => 'primary', 'ditunda' => 'warning', 'dibatalkan' => 'secondary',
          'admin' => 'dark', 'pelatih' => 'success', 'pengelola' => 'info'][$s] ?? 'secondary';
    return '<span class="badge text-bg-' . $c . '">' . e(ucfirst($s)) . '</span>';
}
