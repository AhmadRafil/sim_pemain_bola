<?php
$config = [
    'key' => 'statistik', 'title' => 'Statistik Pemain', 'singular' => 'Statistik', 'table' => 'statistik_pemain', 'pk' => 'id_statistik',
    'fields' => [
        ['name' => 'id_pertandingan', 'label' => 'Pertandingan (yang sudah selesai)', 'type' => 'select', 'required' => true, 'col' => 6,
            'sql' => "SELECT m.id_pertandingan, CONCAT(kh.nama_klub, ' ', m.skor_tuan_rumah, '-', m.skor_tamu, ' ', kt.nama_klub, ' (', DATE_FORMAT(m.tanggal_pertandingan, '%d/%m/%Y'), ')')
                      FROM pertandingan m JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu
                      WHERE m.status_pertandingan = 'selesai' ORDER BY m.tanggal_pertandingan DESC"],
        ['name' => 'id_pemain', 'label' => 'Pemain', 'type' => 'select', 'required' => true, 'col' => 6,
            'sql' => "SELECT p.id_pemain, CONCAT(p.nama_pemain, ' — ', k.nama_klub, ' #', p.nomor_punggung) FROM pemain p JOIN klub k ON k.id_klub = p.id_klub ORDER BY k.nama_klub, p.nomor_punggung"],
        ['name' => 'menit_bermain', 'label' => 'Menit Bermain', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 150, 'col' => 3],
        ['name' => 'jumlah_gol', 'label' => 'Gol', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 20, 'col' => 3],
        ['name' => 'jumlah_assist', 'label' => 'Assist', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 20, 'col' => 3],
        ['name' => 'jumlah_shot', 'label' => 'Tembakan', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 60, 'col' => 3, 'help' => 'Total tembakan, termasuk yang menjadi gol.'],
        ['name' => 'kartu_kuning', 'label' => 'Kartu Kuning', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 2, 'col' => 3],
        ['name' => 'kartu_merah', 'label' => 'Kartu Merah', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 1, 'col' => 3],
        ['name' => 'jumlah_saves', 'label' => 'Penyelamatan', 'type' => 'number', 'required' => true, 'default' => 0, 'min' => 0, 'max' => 40, 'col' => 3],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        $m = db_row('SELECT id_klub_tuan_rumah AS h, id_klub_tamu AS t, skor_tuan_rumah AS sh, skor_tamu AS st, status_pertandingan AS s FROM pertandingan WHERE id_pertandingan = ?', [$d['id_pertandingan']]);
        $p = db_row('SELECT id_klub FROM pemain WHERE id_pemain = ?', [$d['id_pemain']]);
        if (!$m || !$p) { $err[] = 'Pemain atau pertandingan tidak ditemukan.'; return; }
        if ($m['s'] !== 'selesai') $err[] = 'Statistik hanya dapat diinput untuk pertandingan yang sudah selesai.';
        $klub = (int)$p['id_klub'];
        if (!in_array($klub, [(int)$m['h'], (int)$m['t']], true)) { $err[] = 'Pemain bukan anggota salah satu klub pada pertandingan ini.'; return; }
        if (db_val('SELECT COUNT(*) FROM statistik_pemain WHERE id_pemain = ? AND id_pertandingan = ? AND id_statistik <> ?', [$d['id_pemain'], $d['id_pertandingan'], $id]) > 0)
            $err[] = 'Statistik pemain ini pada pertandingan tersebut sudah ada. Gunakan Edit.';
        if ($d['jumlah_shot'] < $d['jumlah_gol']) $err[] = 'Jumlah tembakan tidak boleh lebih kecil dari jumlah gol.';
        if ($d['menit_bermain'] == 0 && ($d['jumlah_gol'] || $d['jumlah_assist'] || $d['jumlah_shot'] || $d['jumlah_saves'] || $d['kartu_kuning'] || $d['kartu_merah']))
            $err[] = 'Pemain dengan 0 menit bermain tidak boleh memiliki gol, assist, tembakan, penyelamatan, atau kartu.';
        $skorKlub = (int)($klub === (int)$m['h'] ? $m['sh'] : $m['st']);
        $golLain = (int)db_val('SELECT COALESCE(SUM(s.jumlah_gol),0) FROM statistik_pemain s JOIN pemain p ON p.id_pemain = s.id_pemain
                                WHERE s.id_pertandingan = ? AND p.id_klub = ? AND s.id_statistik <> ?', [$d['id_pertandingan'], $klub, $id]);
        if ($golLain + $d['jumlah_gol'] > $skorKlub)
            $err[] = "Total gol pemain klub ini ($golLain + {$d['jumlah_gol']}) melebihi skor klub pada pertandingan ($skorKlub).";
    },
    'list' => [
        'select' => 's.*, p.nama_pemain, p.nomor_punggung, k.nama_klub, m.tanggal_pertandingan, m.skor_tuan_rumah, m.skor_tamu, kh.nama_klub AS home, kt.nama_klub AS away',
        'from' => 'statistik_pemain s JOIN pemain p ON p.id_pemain = s.id_pemain JOIN klub k ON k.id_klub = p.id_klub
                   JOIN pertandingan m ON m.id_pertandingan = s.id_pertandingan
                   JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu',
        'search' => ['p.nama_pemain', 'k.nama_klub'],
        'order' => 'm.tanggal_pertandingan DESC, k.nama_klub, p.nomor_punggung',
        'filters' => [
            ['name' => 'klub', 'label' => 'Semua klub', 'col' => 'p.id_klub', 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub'],
            ['name' => 'match', 'label' => 'Semua pertandingan', 'col' => 's.id_pertandingan',
             'sql' => "SELECT m.id_pertandingan, CONCAT(kh.nama_klub, ' vs ', kt.nama_klub, ' (', DATE_FORMAT(m.tanggal_pertandingan, '%d/%m/%Y'), ')') FROM pertandingan m JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu WHERE m.status_pertandingan = 'selesai' ORDER BY m.tanggal_pertandingan DESC"],
        ],
        'cols' => [
            ['label' => 'Pemain', 'render' => fn($r) => '<strong>' . e($r['nama_pemain']) . '</strong><br><small class="text-muted">' . e($r['nama_klub']) . ' #' . (int)$r['nomor_punggung'] . '</small>'],
            ['label' => 'Pertandingan', 'render' => fn($r) => '<small>' . e(tgl($r['tanggal_pertandingan'])) . '<br>' . e($r['home']) . ' ' . (int)$r['skor_tuan_rumah'] . '-' . (int)$r['skor_tamu'] . ' ' . e($r['away']) . '</small>'],
            ['label' => 'Menit', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['menit_bermain']],
            ['label' => 'Gol', 'class' => 'num-cell', 'render' => fn($r) => '<strong>' . (int)$r['jumlah_gol'] . '</strong>'],
            ['label' => 'Assist', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['jumlah_assist']],
            ['label' => 'KK', 'class' => 'num-cell', 'render' => fn($r) => $r['kartu_kuning'] ? '<span class="card-y">' . (int)$r['kartu_kuning'] . '</span>' : '<span class="card-0">0</span>'],
            ['label' => 'KM', 'class' => 'num-cell', 'render' => fn($r) => $r['kartu_merah'] ? '<span class="card-r">' . (int)$r['kartu_merah'] . '</span>' : '<span class="card-0">0</span>'],
            ['label' => 'Tembakan', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['jumlah_shot']],
            ['label' => 'Save', 'class' => 'num-cell', 'render' => fn($r) => (int)$r['jumlah_saves']],
        ],
    ],
];
crud($config);
