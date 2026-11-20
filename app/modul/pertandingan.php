<?php
$sqlKlub = 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub';
$config = [
    'key' => 'pertandingan', 'title' => 'Data Pertandingan', 'singular' => 'Pertandingan', 'table' => 'pertandingan', 'pk' => 'id_pertandingan',
    'fields' => [
        ['name' => 'id_klub_tuan_rumah', 'label' => 'Klub Tuan Rumah', 'type' => 'select', 'required' => true, 'sql' => $sqlKlub],
        ['name' => 'id_klub_tamu', 'label' => 'Klub Tamu', 'type' => 'select', 'required' => true, 'sql' => $sqlKlub],
        ['name' => 'tanggal_pertandingan', 'label' => 'Tanggal & Waktu', 'type' => 'datetime', 'required' => true],
        ['name' => 'lokasi', 'label' => 'Lokasi', 'required' => true, 'max' => 150],
        ['name' => 'status_pertandingan', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'dijadwalkan', 'col' => 4,
            'options' => ['dijadwalkan' => 'Dijadwalkan', 'selesai' => 'Selesai', 'ditunda' => 'Ditunda', 'dibatalkan' => 'Dibatalkan']],
        ['name' => 'skor_tuan_rumah', 'label' => 'Skor Tuan Rumah', 'type' => 'number', 'min' => 0, 'max' => 30, 'col' => 4, 'help' => 'Isi hanya jika status Selesai.'],
        ['name' => 'skor_tamu', 'label' => 'Skor Tamu', 'type' => 'number', 'min' => 0, 'max' => 30, 'col' => 4],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        if ($d['id_klub_tuan_rumah'] == $d['id_klub_tamu']) $err[] = 'Klub tuan rumah dan klub tamu tidak boleh sama.';
        if ($d['status_pertandingan'] === 'selesai') {
            if ($d['skor_tuan_rumah'] === null || $d['skor_tamu'] === null) $err[] = 'Pertandingan berstatus Selesai wajib memiliki kedua skor.';
        } else {
            $d['skor_tuan_rumah'] = null; $d['skor_tamu'] = null;   // skor hanya untuk pertandingan selesai
        }
        if ($id && !$err) {
            $orphan = db_val('SELECT COUNT(*) FROM statistik_pemain s JOIN pemain p ON p.id_pemain = s.id_pemain
                              WHERE s.id_pertandingan = ? AND p.id_klub NOT IN (?, ?)', [$id, $d['id_klub_tuan_rumah'], $d['id_klub_tamu']]);
            if ($orphan > 0) $err[] = 'Klub tidak dapat diubah: sudah ada statistik pemain dari klub yang tidak ikut pertandingan ini.';
            if ($d['status_pertandingan'] !== 'selesai' && db_val('SELECT COUNT(*) FROM statistik_pemain WHERE id_pertandingan = ?', [$id]) > 0)
                $err[] = 'Status tidak dapat diubah dari Selesai karena sudah ada statistik pemain.';
        }
    },
    'list' => [
        'select' => 'm.*, kh.nama_klub AS home, kt.nama_klub AS away',
        'from' => 'pertandingan m JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu',
        'search' => ['kh.nama_klub', 'kt.nama_klub', 'm.lokasi'],
        'order' => 'm.tanggal_pertandingan DESC',
        'filters' => [
            ['name' => 'klub', 'label' => 'Semua klub', 'raw' => '(m.id_klub_tuan_rumah = ? OR m.id_klub_tamu = ?)', 'n' => 2, 'sql' => $sqlKlub],
            ['name' => 'status', 'label' => 'Semua status', 'col' => 'm.status_pertandingan', 'options' => ['dijadwalkan' => 'Dijadwalkan', 'selesai' => 'Selesai', 'ditunda' => 'Ditunda', 'dibatalkan' => 'Dibatalkan']],
        ],
        'cols' => [
            ['label' => 'Tanggal', 'render' => fn($r) => e(tgl($r['tanggal_pertandingan'], true))],
            ['label' => 'Pertandingan', 'render' => fn($r) => '<strong>' . e($r['home']) . '</strong> '
                . ($r['status_pertandingan'] === 'selesai' ? '<span class="score-pill">' . (int)$r['skor_tuan_rumah'] . ' - ' . (int)$r['skor_tamu'] . '</span>' : '<span class="text-muted">vs</span>')
                . ' <strong>' . e($r['away']) . '</strong>'],
            ['label' => 'Lokasi', 'render' => fn($r) => e($r['lokasi'])],
            ['label' => 'Status', 'render' => fn($r) => badge_status($r['status_pertandingan'])],
        ],
    ],
];
crud($config);
