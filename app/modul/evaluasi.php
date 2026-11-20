<?php
$config = [
    'key' => 'evaluasi', 'title' => 'Evaluasi Pemain', 'singular' => 'Evaluasi', 'table' => 'evaluasi_pemain', 'pk' => 'id_evaluasi',
    'fields' => [
        ['name' => 'id_pemain', 'label' => 'Pemain', 'type' => 'select', 'required' => true,
            'sql' => "SELECT p.id_pemain, CONCAT(p.nama_pemain, ' — ', k.nama_klub) FROM pemain p JOIN klub k ON k.id_klub = p.id_klub ORDER BY k.nama_klub, p.nama_pemain"],
        ['name' => 'id_pelatih', 'label' => 'Pelatih Penilai', 'type' => 'select', 'required' => true,
            'sql' => "SELECT pl.id_pelatih, CONCAT(pl.nama_pelatih, ' — ', k.nama_klub) FROM pelatih pl JOIN klub k ON k.id_klub = pl.id_klub ORDER BY k.nama_klub, pl.nama_pelatih"],
        ['name' => 'tanggal_evaluasi', 'label' => 'Tanggal Evaluasi', 'type' => 'date', 'required' => true, 'not_future' => true, 'default' => date('Y-m-d'), 'col' => 3],
        ['name' => 'penilaian_teknik', 'label' => 'Teknik (1-10)', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 10, 'col' => 3],
        ['name' => 'penilaian_fisik', 'label' => 'Fisik (1-10)', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 10, 'col' => 3],
        ['name' => 'penilaian_kerja_sama', 'label' => 'Kerja Sama (1-10)', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 10, 'col' => 3],
        ['name' => 'catatan_pelatih', 'label' => 'Catatan Pelatih', 'type' => 'textarea', 'max' => 1000, 'col' => 12],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        $kp = db_val('SELECT id_klub FROM pemain WHERE id_pemain = ?', [$d['id_pemain']]);
        $kl = db_val('SELECT id_klub FROM pelatih WHERE id_pelatih = ?', [$d['id_pelatih']]);
        if ($kp !== null && $kl !== null && (int)$kp !== (int)$kl) $err[] = 'Pelatih hanya dapat mengevaluasi pemain dari klubnya sendiri.';
    },
    'list' => [
        'select' => 'e.*, p.nama_pemain, k.nama_klub, pl.nama_pelatih',
        'from' => 'evaluasi_pemain e JOIN pemain p ON p.id_pemain = e.id_pemain JOIN klub k ON k.id_klub = p.id_klub JOIN pelatih pl ON pl.id_pelatih = e.id_pelatih',
        'search' => ['p.nama_pemain', 'pl.nama_pelatih', 'e.catatan_pelatih'],
        'order' => 'e.tanggal_evaluasi DESC, e.id_evaluasi DESC',
        'filters' => [['name' => 'klub', 'label' => 'Semua klub', 'col' => 'p.id_klub', 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub']],
        'cols' => [
            ['label' => 'Tanggal', 'render' => fn($r) => e(tgl($r['tanggal_evaluasi']))],
            ['label' => 'Pemain', 'render' => fn($r) => '<strong>' . e($r['nama_pemain']) . '</strong><br><small class="text-muted">' . e($r['nama_klub']) . '</small>'],
            ['label' => 'Pelatih', 'render' => fn($r) => e($r['nama_pelatih'])],
            ['label' => 'Teknik', 'class' => 'num-cell', 'render' => fn($r) => '<span class="rating">' . (int)$r['penilaian_teknik'] . '</span>'],
            ['label' => 'Fisik', 'class' => 'num-cell', 'render' => fn($r) => '<span class="rating">' . (int)$r['penilaian_fisik'] . '</span>'],
            ['label' => 'Kerja sama', 'class' => 'num-cell', 'render' => fn($r) => '<span class="rating">' . (int)$r['penilaian_kerja_sama'] . '</span>'],
            ['label' => 'Rata-rata', 'class' => 'num-cell', 'render' => fn($r) => '<strong>' . number_format(($r['penilaian_teknik'] + $r['penilaian_fisik'] + $r['penilaian_kerja_sama']) / 3, 1) . '</strong>'],
            ['label' => 'Catatan', 'render' => fn($r) => '<small>' . e(mb_strimwidth($r['catatan_pelatih'] ?? '', 0, 70, '…')) . '</small>'],
        ],
    ],
];
crud($config);
