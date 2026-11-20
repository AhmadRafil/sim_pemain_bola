<?php
$config = [
    'key' => 'pelatih', 'title' => 'Data Pelatih', 'singular' => 'Pelatih', 'table' => 'pelatih', 'pk' => 'id_pelatih',
    'fields' => [
        ['name' => 'nama_pelatih', 'label' => 'Nama Pelatih', 'required' => true, 'max' => 100],
        ['name' => 'id_klub', 'label' => 'Klub', 'type' => 'select', 'required' => true, 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub'],
        ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'required' => true, 'not_future' => true],
        ['name' => 'kewarganegaraan', 'label' => 'Kewarganegaraan', 'required' => true, 'max' => 60],
        ['name' => 'pengalaman', 'label' => 'Pengalaman', 'type' => 'textarea', 'max' => 255, 'col' => 12],
        ['name' => 'kontak', 'label' => 'Kontak', 'max' => 50],
    ],
    'list' => [
        'select' => 'pl.*, k.nama_klub',
        'from' => 'pelatih pl JOIN klub k ON k.id_klub = pl.id_klub',
        'search' => ['pl.nama_pelatih', 'k.nama_klub', 'pl.kewarganegaraan'],
        'order' => 'k.nama_klub, pl.nama_pelatih',
        'filters' => [['name' => 'klub', 'label' => 'Semua klub', 'col' => 'pl.id_klub', 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub']],
        'cols' => [
            ['label' => 'Pelatih', 'render' => fn($r) => '<strong>' . e($r['nama_pelatih']) . '</strong><br><small class="text-muted">' . e($r['kewarganegaraan']) . ', ' . usia($r['tanggal_lahir']) . ' th</small>'],
            ['label' => 'Klub', 'render' => fn($r) => e($r['nama_klub'])],
            ['label' => 'Pengalaman', 'render' => fn($r) => e($r['pengalaman'] ?? '-')],
            ['label' => 'Kontak', 'render' => fn($r) => e($r['kontak'] ?? '-')],
        ],
    ],
];
crud($config);
