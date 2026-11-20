<?php
$config = [
    'key' => 'pemain', 'title' => 'Data Pemain', 'singular' => 'Pemain', 'table' => 'pemain', 'pk' => 'id_pemain',
    'fields' => [
        ['name' => 'nama_pemain', 'label' => 'Nama Pemain', 'required' => true, 'max' => 100],
        ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'required' => true, 'not_future' => true, 'col' => 3],
        ['name' => 'kewarganegaraan', 'label' => 'Kewarganegaraan', 'required' => true, 'max' => 60, 'col' => 3],
        ['name' => 'id_klub', 'label' => 'Klub', 'type' => 'select', 'required' => true, 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub', 'col' => 4],
        ['name' => 'id_posisi', 'label' => 'Posisi', 'type' => 'select', 'required' => true, 'sql' => 'SELECT id_posisi, nama_posisi FROM posisi ORDER BY id_posisi', 'col' => 4],
        ['name' => 'nomor_punggung', 'label' => 'Nomor Punggung', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 99, 'col' => 4, 'help' => 'Harus unik di dalam satu klub.'],
        ['name' => 'tinggi_badan', 'label' => 'Tinggi (cm)', 'type' => 'number', 'min' => 120, 'max' => 230, 'col' => 4],
        ['name' => 'berat_badan', 'label' => 'Berat (kg)', 'type' => 'number', 'min' => 30, 'max' => 150, 'col' => 4],
        ['name' => 'status_pemain', 'label' => 'Status', 'type' => 'select', 'required' => true, 'default' => 'aktif', 'col' => 4,
            'options' => ['aktif' => 'Aktif', 'cedera' => 'Cedera', 'dipinjamkan' => 'Dipinjamkan', 'nonaktif' => 'Nonaktif']],
        ['name' => 'foto_pemain', 'label' => 'Foto Pemain', 'type' => 'image', 'dir' => 'pemain', 'col' => 12, 'help' => 'JPG/PNG/WEBP, maks. 2 MB. Kosongkan jika tidak diubah.'],
    ],
    'validate' => function (array &$d, int $id, array &$err) {
        if (db_val('SELECT COUNT(*) FROM pemain WHERE id_klub = ? AND nomor_punggung = ? AND id_pemain <> ?', [$d['id_klub'], $d['nomor_punggung'], $id]) > 0)
            $err[] = 'Nomor punggung ' . $d['nomor_punggung'] . ' sudah dipakai pemain lain di klub tersebut.';
    },
    'list' => [
        'select' => 'p.*, k.nama_klub, ps.nama_posisi',
        'from' => 'pemain p JOIN klub k ON k.id_klub = p.id_klub JOIN posisi ps ON ps.id_posisi = p.id_posisi',
        'search' => ['p.nama_pemain', 'p.kewarganegaraan', 'k.nama_klub'],
        'order' => 'k.nama_klub ASC, p.nomor_punggung ASC',
        'filters' => [
            ['name' => 'klub', 'label' => 'Semua klub', 'col' => 'p.id_klub', 'sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub'],
            ['name' => 'posisi', 'label' => 'Semua posisi', 'col' => 'p.id_posisi', 'sql' => 'SELECT id_posisi, nama_posisi FROM posisi ORDER BY id_posisi'],
        ],
        'cols' => [
            ['label' => 'No', 'render' => fn($r) => '<span class="shirt">' . (int)$r['nomor_punggung'] . '</span>'],
            ['label' => 'Pemain', 'render' => fn($r) => '<div class="d-flex align-items-center gap-2">' . avatar($r['foto_pemain'], 'pemain', $r['nama_pemain'], 40)
                . '<div><a class="fw-semibold text-decoration-none" href="index.php?open=pemain&act=detail&id=' . $r['id_pemain'] . '">' . e($r['nama_pemain']) . '</a><br><small class="text-muted">' . e($r['kewarganegaraan']) . ', ' . usia($r['tanggal_lahir']) . ' th</small></div></div>'],
            ['label' => 'Klub', 'render' => fn($r) => e($r['nama_klub'])],
            ['label' => 'Posisi', 'render' => fn($r) => e($r['nama_posisi'])],
            ['label' => 'Status', 'render' => fn($r) => badge_status($r['status_pemain'])],
        ],
    ],
    'detail' => function (array $r) {
        $id = (int)$r['id_pemain'];
        $p = db_row('SELECT p.*, k.nama_klub, ps.nama_posisi FROM pemain p JOIN klub k ON k.id_klub = p.id_klub JOIN posisi ps ON ps.id_posisi = p.id_posisi WHERE p.id_pemain = ?', [$id]);
        $t = db_row('SELECT COUNT(*) AS main, COALESCE(SUM(menit_bermain),0) AS menit, COALESCE(SUM(jumlah_gol),0) AS gol, COALESCE(SUM(jumlah_assist),0) AS assist,
                     COALESCE(SUM(kartu_kuning),0) AS kk, COALESCE(SUM(kartu_merah),0) AS km, COALESCE(SUM(jumlah_shot),0) AS shot, COALESCE(SUM(jumlah_saves),0) AS saves
                     FROM statistik_pemain WHERE id_pemain = ?', [$id]);
        $ev = db_all('SELECT e.*, pl.nama_pelatih FROM evaluasi_pemain e JOIN pelatih pl ON pl.id_pelatih = e.id_pelatih WHERE e.id_pemain = ? ORDER BY e.tanggal_evaluasi DESC', [$id]);
        $hist = db_all('SELECT s.*, m.tanggal_pertandingan, m.skor_tuan_rumah, m.skor_tamu, kh.nama_klub AS home, kt.nama_klub AS away
                        FROM statistik_pemain s JOIN pertandingan m ON m.id_pertandingan = s.id_pertandingan
                        JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu
                        WHERE s.id_pemain = ? ORDER BY m.tanggal_pertandingan DESC', [$id]);

        echo '<div class="panel"><div class="panel-body d-flex flex-wrap gap-4 align-items-center">' . avatar($p['foto_pemain'], 'pemain', $p['nama_pemain'], 120)
           . '<div class="flex-grow-1"><div class="d-flex align-items-center gap-2 mb-1"><span class="shirt" style="width:40px;height:40px;font-size:22px">' . (int)$p['nomor_punggung'] . '</span>'
           . '<h2 class="m-0" style="font-size:38px;font-weight:700">' . e($p['nama_pemain']) . '</h2>' . badge_status($p['status_pemain']) . '</div>'
           . '<div class="text-muted mb-2">' . e($p['nama_posisi']) . ' · ' . e($p['nama_klub']) . '</div>'
           . '<div>' . e($p['kewarganegaraan']) . ' · Lahir ' . tgl($p['tanggal_lahir']) . ' (' . usia($p['tanggal_lahir']) . ' th) · '
           . e($p['tinggi_badan'] ?? '-') . ' cm · ' . e($p['berat_badan'] ?? '-') . ' kg</div></div></div></div>';

        echo '<div class="row g-3 mb-3">';
        foreach ([['Penampilan', $t['main']], ['Menit', $t['menit']], ['Gol', $t['gol']], ['Assist', $t['assist']], ['Kartu kuning', $t['kk']], ['Kartu merah', $t['km']], ['Tembakan', $t['shot']], ['Penyelamatan', $t['saves']]] as [$l, $v])
            echo '<div class="col-6 col-md-3 col-xl"><div class="tile"><div class="num">' . (int)$v . '</div><div class="lbl">' . e($l) . '</div></div></div>';
        echo '</div><div class="row g-3"><div class="col-lg-7"><div class="panel"><div class="panel-head"><h2>Riwayat pertandingan</h2></div><div class="table-responsive"><table class="table table-pitch mb-0"><thead><tr><th>Tanggal</th><th>Pertandingan</th><th class="text-center">Mnt</th><th class="text-center">G</th><th class="text-center">A</th><th class="text-center">KK</th><th class="text-center">KM</th></tr></thead><tbody>';
        foreach ($hist as $h) echo '<tr><td>' . tgl($h['tanggal_pertandingan']) . '</td><td>' . e($h['home']) . ' <span class="score-pill">' . (int)$h['skor_tuan_rumah'] . '-' . (int)$h['skor_tamu'] . '</span> ' . e($h['away']) . '</td>'
            . '<td class="num-cell">' . (int)$h['menit_bermain'] . '</td><td class="num-cell">' . (int)$h['jumlah_gol'] . '</td><td class="num-cell">' . (int)$h['jumlah_assist'] . '</td><td class="num-cell">' . (int)$h['kartu_kuning'] . '</td><td class="num-cell">' . (int)$h['kartu_merah'] . '</td></tr>';
        if (!$hist) echo '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada statistik pertandingan.</td></tr>';
        echo '</tbody></table></div></div></div><div class="col-lg-5"><div class="panel"><div class="panel-head"><h2>Evaluasi pelatih</h2></div><div class="panel-body">';
        foreach ($ev as $x) echo '<div class="mb-3 pb-3 border-bottom"><div class="d-flex justify-content-between"><strong>' . tgl($x['tanggal_evaluasi']) . '</strong><small class="text-muted">' . e($x['nama_pelatih']) . '</small></div>'
            . '<div class="my-1">Teknik <span class="rating">' . (int)$x['penilaian_teknik'] . '</span> Fisik <span class="rating">' . (int)$x['penilaian_fisik'] . '</span> Kerja sama <span class="rating">' . (int)$x['penilaian_kerja_sama'] . '</span></div>'
            . '<div class="text-muted">' . e($x['catatan_pelatih'] ?? '') . '</div></div>';
        if (!$ev) echo '<span class="text-muted">Belum ada evaluasi.</span>';
        echo '</div></div></div></div>';
    },
];
crud($config);
