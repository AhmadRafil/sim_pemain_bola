<?php
$config = [
    'key' => 'users', 'title' => 'Data Pengguna', 'singular' => 'Pengguna', 'table' => 'users', 'pk' => 'id_user',
    'fields' => [
        ['name' => 'nama', 'label' => 'Nama Lengkap', 'required' => true, 'max' => 100],
        ['name' => 'username', 'label' => 'Username', 'required' => true, 'max' => 50, 'help' => '3-30 karakter: huruf, angka, titik, garis bawah.'],
        ['name' => 'password', 'column' => 'password_hash', 'label' => 'Password', 'type' => 'password', 'help' => 'Minimal 6 karakter. Saat edit, kosongkan jika tidak ingin mengubah.'],
        ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'required' => true, 'options' => ['admin' => 'Admin', 'pelatih' => 'Pelatih', 'pengelola' => 'Pengelola']],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $d['username'])) $err[] = 'Format username tidak valid.';
        if (db_val('SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?', [$d['username'], $id]) > 0) $err[] = 'Username sudah dipakai.';
        if ($id === (int)$_SESSION['id_user'] && $d['role'] !== 'admin') $err[] = 'Anda tidak dapat menurunkan role akun Anda sendiri.';
    },
    'can_delete' => function (array $r) {
        if ((int)$r['id_user'] === (int)$_SESSION['id_user']) return 'Anda tidak dapat menghapus akun yang sedang dipakai.';
        if ($r['role'] === 'admin' && db_val("SELECT COUNT(*) FROM users WHERE role = 'admin'") <= 1) return 'Admin terakhir tidak dapat dihapus.';
        return null;
    },
    'list' => [
        'select' => 'u.*',
        'from' => 'users u',
        'search' => ['u.nama', 'u.username'],
        'order' => 'u.role, u.nama',
        'filters' => [['name' => 'role', 'label' => 'Semua role', 'col' => 'u.role', 'options' => ['admin' => 'Admin', 'pelatih' => 'Pelatih', 'pengelola' => 'Pengelola']]],
        'cols' => [
            ['label' => 'Nama', 'render' => fn($r) => '<strong>' . e($r['nama']) . '</strong>'],
            ['label' => 'Username', 'render' => fn($r) => e($r['username'])],
            ['label' => 'Role', 'render' => fn($r) => badge_status($r['role'])],
        ],
    ],
];
crud($config);
