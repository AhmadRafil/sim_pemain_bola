<?php
$config = [
    'key' => 'posisi', 'title' => 'Data Posisi', 'singular' => 'Posisi', 'table' => 'posisi', 'pk' => 'id_posisi',
    'fields' => [
        ['name' => 'nama_posisi', 'label' => 'Nama Posisi', 'required' => true, 'max' => 50, 'col' => 12],
        ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea', 'max' => 500, 'col' => 12],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        if (db_val('SELECT COUNT(*) FROM posisi WHERE nama_posisi = ? AND id_posisi <> ?', [$d['nama_posisi'], $id]) > 0)
            $err[] = 'Nama posisi sudah ada.';
    },
    'list' => [
        'select' => 'ps.*, (SELECT COUNT(*) FROM pemain WHERE id_posisi = ps.id_posisi) AS jml_pemain',
        'from' => 'posisi ps',
        'search' => ['ps.nama_posisi', 'ps.deskripsi'],
        'order' => 'ps.id_posisi ASC',
        'cols' => [
            ['label' => 'Posisi', 'render' => fn($r) => '<strong>' . e($r['nama_posisi']) . '</strong>'],
            ['label' => 'Deskripsi', 'render' => fn($r) => e($r['deskripsi'] ?? '-')],
            ['label' => 'Pemain', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['jml_pemain']],
        ],
    ],
];
crud($config);
