<?php
$config = [
    'key' => 'klub', 'title' => 'Data Klub', 'singular' => 'Klub', 'table' => 'klub', 'pk' => 'id_klub',
    'fields' => [
        ['name' => 'nama_klub', 'label' => 'Nama Klub', 'required' => true, 'max' => 100],
        ['name' => 'negara', 'label' => 'Negara', 'required' => true, 'max' => 60],
        ['name' => 'kota', 'label' => 'Kota', 'required' => true, 'max' => 60],
        ['name' => 'stadion', 'label' => 'Stadion', 'required' => true, 'max' => 100],
        ['name' => 'tahun_berdiri', 'label' => 'Tahun Berdiri', 'type' => 'number', 'required' => true, 'min' => 1850, 'max' => (int)date('Y'), 'col' => 4],
        ['name' => 'logo_klub', 'label' => 'Logo Klub', 'type' => 'image', 'dir' => 'klub', 'col' => 8, 'help' => 'JPG/PNG/WEBP, maks. 2 MB. Kosongkan jika tidak diubah.'],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        if (db_val('SELECT COUNT(*) FROM klub WHERE nama_klub = ? AND id_klub <> ?', [$d['nama_klub'], $id]) > 0)
            $err[] = 'Nama klub sudah terdaftar.';
    },
    'list' => [
        'select' => 'k.*, (SELECT COUNT(*) FROM pemain WHERE id_klub = k.id_klub) AS jml_pemain',
        'from' => 'klub k',
        'search' => ['k.nama_klub', 'k.kota', 'k.negara', 'k.stadion'],
        'order' => 'k.nama_klub ASC',
        'cols' => [
            ['label' => 'Klub', 'render' => fn($r) => '<div class="d-flex align-items-center gap-2">' . avatar($r['logo_klub'], 'klub', $r['nama_klub'], 36) . '<strong>' . e($r['nama_klub']) . '</strong></div>'],
            ['label' => 'Kota, Negara', 'render' => fn($r) => e($r['kota'] . ', ' . $r['negara'])],
            ['label' => 'Stadion', 'render' => fn($r) => e($r['stadion'] ?? '-')],
            ['label' => 'Berdiri', 'class' => 'num-cell', 'render' => fn($r) => e($r['tahun_berdiri'] ?? '-')],
            ['label' => 'Pemain', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['jml_pemain']],
        ],
    ],
    'detail' => function (array $r) {
        $pemain = db_all('SELECT p.*, ps.nama_posisi FROM pemain p JOIN posisi ps ON ps.id_posisi = p.id_posisi WHERE p.id_klub = ? ORDER BY p.nomor_punggung', [$r['id_klub']]);
        $pelatih = db_all('SELECT * FROM pelatih WHERE id_klub = ?', [$r['id_klub']]);
        echo '<div class="panel"><div class="panel-body d-flex gap-3 align-items-center">' . avatar($r['logo_klub'], 'klub', $r['nama_klub'], 84)
           . '<div><h2 class="mb-1" style="font-size:34px;font-weight:700">' . e($r['nama_klub']) . '</h2><div class="text-muted">'
           . e($r['kota'] . ', ' . $r['negara']) . ' · Stadion ' . e($r['stadion'] ?? '-') . ' · Berdiri ' . e($r['tahun_berdiri'] ?? '-') . '</div></div></div></div>';
        echo '<div class="row g-3"><div class="col-lg-8"><div class="panel"><div class="panel-head"><h2>Skuad (' . count($pemain) . ')</h2></div><div class="table-responsive"><table class="table table-pitch mb-0"><thead><tr><th>No</th><th>Nama</th><th>Posisi</th><th>Status</th></tr></thead><tbody>';
        foreach ($pemain as $p) echo '<tr><td><span class="shirt">' . (int)$p['nomor_punggung'] . '</span></td><td><a href="index.php?open=pemain&act=detail&id=' . $p['id_pemain'] . '">' . e($p['nama_pemain']) . '</a></td><td>' . e($p['nama_posisi']) . '</td><td>' . badge_status($p['status_pemain']) . '</td></tr>';
        if (!$pemain) echo '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada pemain.</td></tr>';
        echo '</tbody></table></div></div></div><div class="col-lg-4"><div class="panel"><div class="panel-head"><h2>Pelatih</h2></div><div class="panel-body">';
        foreach ($pelatih as $pl) echo '<div class="mb-2"><strong>' . e($pl['nama_pelatih']) . '</strong><br><small class="text-muted">' . e($pl['pengalaman'] ?? '') . '</small></div>';
        if (!$pelatih) echo '<span class="text-muted">Belum ada pelatih.</span>';
        echo '</div></div></div></div>';
    },
];
crud($config);
