<?php
// Laporan: ?open=laporan&jenis=pemain|statistik|pertandingan|performa  (+ filter, &export=xls)
$jenisValid = ['pemain', 'statistik', 'pertandingan', 'performa'];
$jenis = in_array($_GET['jenis'] ?? '', $jenisValid, true) ? $_GET['jenis'] : 'pemain';
$judulMap = [
    'pemain' => 'Laporan Data Pemain', 'statistik' => 'Laporan Statistik Pemain',
    'pertandingan' => 'Laporan Hasil Pertandingan', 'performa' => 'Laporan Performa Pemain',
];
$judul = $judulMap[$jenis];
$PAGE_TITLE = $judul;

// ---- baca filter ----
$tglOk = fn($s) => (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s) && strtotime($s)) ? $s : '';
$f = [
    'q'      => trim($_GET['q'] ?? ''),
    'klub'   => (int)($_GET['klub'] ?? 0),
    'posisi' => (int)($_GET['posisi'] ?? 0),
    'status' => $_GET['status'] ?? '',
    'dari'   => $tglOk($_GET['dari'] ?? ''),
    'sampai' => $tglOk($_GET['sampai'] ?? ''),
];
$w = []; $p = [];
$add = function (string $sql, ...$vals) use (&$w, &$p) { $w[] = $sql; foreach ($vals as $v) $p[] = $v; };

$cols = []; $rows = []; $totalIdx = []; $chart = null;

if ($jenis === 'pemain') {
    $show = ['q', 'klub', 'posisi', 'status'];
    if ($f['q'] !== '') $add('(p.nama_pemain LIKE ? OR p.kewarganegaraan LIKE ?)', "%{$f['q']}%", "%{$f['q']}%");
    if ($f['klub']) $add('p.id_klub = ?', $f['klub']);
    if ($f['posisi']) $add('p.id_posisi = ?', $f['posisi']);
    if (in_array($f['status'], ['aktif', 'cedera', 'dipinjamkan', 'nonaktif'], true)) $add('p.status_pemain = ?', $f['status']); else $f['status'] = '';
    $data = db_all('SELECT p.*, k.nama_klub, ps.nama_posisi FROM pemain p JOIN klub k ON k.id_klub = p.id_klub JOIN posisi ps ON ps.id_posisi = p.id_posisi'
        . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY k.nama_klub, p.nomor_punggung', $p);
    $cols = ['Nama Pemain', 'Klub', 'Posisi', 'No', 'Tgl Lahir', 'Usia', 'Kewarganegaraan', 'Tinggi (cm)', 'Berat (kg)', 'Status'];
    foreach ($data as $r) $rows[] = [$r['nama_pemain'], $r['nama_klub'], $r['nama_posisi'], $r['nomor_punggung'], tgl($r['tanggal_lahir']), usia($r['tanggal_lahir']),
        $r['kewarganegaraan'], $r['tinggi_badan'] ?? '-', $r['berat_badan'] ?? '-', ucfirst($r['status_pemain'])];

} elseif ($jenis === 'statistik') {
    $show = ['q', 'klub', 'posisi', 'dari', 'sampai'];
    if ($f['q'] !== '') $add('p.nama_pemain LIKE ?', "%{$f['q']}%");
    if ($f['klub']) $add('p.id_klub = ?', $f['klub']);
    if ($f['posisi']) $add('p.id_posisi = ?', $f['posisi']);
    if ($f['dari']) $add('m.tanggal_pertandingan >= ?', $f['dari'] . ' 00:00:00');
    if ($f['sampai']) $add('m.tanggal_pertandingan <= ?', $f['sampai'] . ' 23:59:59');
    $data = db_all('SELECT p.nama_pemain, k.nama_klub, ps.nama_posisi, COUNT(*) AS main, SUM(s.menit_bermain) AS menit, SUM(s.jumlah_gol) AS gol,
                           SUM(s.jumlah_assist) AS assist, SUM(s.kartu_kuning) AS kk, SUM(s.kartu_merah) AS km, SUM(s.jumlah_shot) AS shot, SUM(s.jumlah_saves) AS saves
                    FROM statistik_pemain s JOIN pemain p ON p.id_pemain = s.id_pemain JOIN klub k ON k.id_klub = p.id_klub
                    JOIN posisi ps ON ps.id_posisi = p.id_posisi JOIN pertandingan m ON m.id_pertandingan = s.id_pertandingan'
        . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
        . ' GROUP BY p.id_pemain, p.nama_pemain, k.nama_klub, ps.nama_posisi ORDER BY gol DESC, assist DESC, p.nama_pemain', $p);
    $cols = ['Pemain', 'Klub', 'Posisi', 'Main', 'Menit', 'Gol', 'Assist', 'Kartu Kuning', 'Kartu Merah', 'Tembakan', 'Penyelamatan'];
    foreach ($data as $r) $rows[] = [$r['nama_pemain'], $r['nama_klub'], $r['nama_posisi'], (int)$r['main'], (int)$r['menit'], (int)$r['gol'], (int)$r['assist'],
        (int)$r['kk'], (int)$r['km'], (int)$r['shot'], (int)$r['saves']];
    $totalIdx = range(3, 10);
    $top = array_slice($data, 0, 10);
    $chart = ['labels' => array_column($top, 'nama_pemain'), 'sets' => [['Gol', array_map('intval', array_column($top, 'gol')), '#1f7a4d'], ['Assist', array_map('intval', array_column($top, 'assist')), '#f2c230']]];

} elseif ($jenis === 'pertandingan') {
    $show = ['klub', 'status', 'dari', 'sampai'];
    if ($f['klub']) $add('(m.id_klub_tuan_rumah = ? OR m.id_klub_tamu = ?)', $f['klub'], $f['klub']);
    if (in_array($f['status'], ['dijadwalkan', 'selesai', 'ditunda', 'dibatalkan'], true)) $add('m.status_pertandingan = ?', $f['status']); else $f['status'] = '';
    if ($f['dari']) $add('m.tanggal_pertandingan >= ?', $f['dari'] . ' 00:00:00');
    if ($f['sampai']) $add('m.tanggal_pertandingan <= ?', $f['sampai'] . ' 23:59:59');
    $data = db_all('SELECT m.*, kh.nama_klub AS home, kt.nama_klub AS away FROM pertandingan m
                    JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu'
        . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY m.tanggal_pertandingan DESC', $p);
    $cols = ['Tanggal', 'Tuan Rumah', 'Skor', 'Tamu', 'Lokasi', 'Status'];
    foreach ($data as $r) $rows[] = [tgl($r['tanggal_pertandingan'], true), $r['home'],
        $r['status_pertandingan'] === 'selesai' ? $r['skor_tuan_rumah'] . ' - ' . $r['skor_tamu'] : '-', $r['away'], $r['lokasi'], ucfirst($r['status_pertandingan'])];

} else { // performa
    $show = ['q', 'klub', 'posisi', 'dari', 'sampai'];
    if ($f['q'] !== '') $add('p.nama_pemain LIKE ?', "%{$f['q']}%");
    if ($f['klub']) $add('p.id_klub = ?', $f['klub']);
    if ($f['posisi']) $add('p.id_posisi = ?', $f['posisi']);
    if ($f['dari']) $add('e.tanggal_evaluasi >= ?', $f['dari']);
    if ($f['sampai']) $add('e.tanggal_evaluasi <= ?', $f['sampai']);
    $data = db_all('SELECT p.id_pemain, p.nama_pemain, k.nama_klub, ps.nama_posisi, COUNT(*) AS n,
                           AVG(e.penilaian_teknik) AS teknik, AVG(e.penilaian_fisik) AS fisik, AVG(e.penilaian_kerja_sama) AS kerja,
                           AVG((e.penilaian_teknik + e.penilaian_fisik + e.penilaian_kerja_sama) / 3) AS rata,
                           (SELECT COALESCE(SUM(jumlah_gol),0) FROM statistik_pemain WHERE id_pemain = p.id_pemain) AS gol,
                           (SELECT COALESCE(SUM(jumlah_assist),0) FROM statistik_pemain WHERE id_pemain = p.id_pemain) AS assist
                    FROM evaluasi_pemain e JOIN pemain p ON p.id_pemain = e.id_pemain JOIN klub k ON k.id_klub = p.id_klub JOIN posisi ps ON ps.id_posisi = p.id_posisi'
        . ($w ? ' WHERE ' . implode(' AND ', $w) : '')
        . ' GROUP BY p.id_pemain, p.nama_pemain, k.nama_klub, ps.nama_posisi ORDER BY rata DESC, p.nama_pemain', $p);
    $cols = ['Pemain', 'Klub', 'Posisi', 'Jml Evaluasi', 'Teknik', 'Fisik', 'Kerja Sama', 'Rata-rata', 'Gol', 'Assist'];
    foreach ($data as $r) $rows[] = [$r['nama_pemain'], $r['nama_klub'], $r['nama_posisi'], (int)$r['n'], number_format($r['teknik'], 1), number_format($r['fisik'], 1),
        number_format($r['kerja'], 1), number_format($r['rata'], 1), (int)$r['gol'], (int)$r['assist']];
    $top = array_slice($data, 0, 10);
    $chart = ['labels' => array_column($top, 'nama_pemain'), 'sets' => [['Rata-rata nilai', array_map(fn($x) => round((float)$x, 1), array_column($top, 'rata')), '#1f7a4d']]];
}

// baris total
$totalRow = null;
if ($totalIdx && $rows) {
    $totalRow = array_fill(0, count($cols), '');
    $totalRow[0] = 'Total';
    foreach ($totalIdx as $i) $totalRow[$i] = array_sum(array_column($rows, $i));
}

// deskripsi filter untuk judul cetak
$ket = [];
$namaKlub = fn($id) => db_val('SELECT nama_klub FROM klub WHERE id_klub = ?', [$id]);
if ($f['klub']) $ket[] = 'Klub: ' . $namaKlub($f['klub']);
if ($f['posisi']) $ket[] = 'Posisi: ' . db_val('SELECT nama_posisi FROM posisi WHERE id_posisi = ?', [$f['posisi']]);
if ($f['status']) $ket[] = 'Status: ' . ucfirst($f['status']);
if ($f['q'] !== '') $ket[] = 'Kata kunci: ' . $f['q'];
if ($f['dari'] || $f['sampai']) $ket[] = 'Periode: ' . ($f['dari'] ? tgl($f['dari']) : 'awal') . ' s.d. ' . ($f['sampai'] ? tgl($f['sampai']) : 'sekarang');
$ketTxt = $ket ? implode(' | ', $ket) : 'Semua data';

// ---- ekspor Excel (HTML table yang dapat dibuka Excel) ----
if (($_GET['export'] ?? '') === 'xls') {
    $RAW = true;
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $jenis . '_' . date('Ymd_His') . '.xls"');
    echo '<html><head><meta charset="utf-8"></head><body><table border="1">';
    echo '<tr><th colspan="' . count($cols) . '">' . e($judul) . '</th></tr>';
    echo '<tr><td colspan="' . count($cols) . '">' . e($ketTxt) . ' — dicetak ' . e(date('d/m/Y H:i')) . '</td></tr><tr>';
    foreach ($cols as $c) echo '<th>' . e($c) . '</th>';
    echo '</tr>';
    foreach (array_merge($rows, $totalRow ? [$totalRow] : []) as $r) {
        echo '<tr>';
        foreach ($r as $v) echo '<td>' . e($v) . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    return;
}

// ---- tampilan ----
$klubOpt = field_opts(['sql' => 'SELECT id_klub, nama_klub FROM klub ORDER BY nama_klub']);
$posOpt = field_opts(['sql' => 'SELECT id_posisi, nama_posisi FROM posisi ORDER BY id_posisi']);
$statusOpt = $jenis === 'pemain'
    ? ['aktif' => 'Aktif', 'cedera' => 'Cedera', 'dipinjamkan' => 'Dipinjamkan', 'nonaktif' => 'Nonaktif']
    : ['dijadwalkan' => 'Dijadwalkan', 'selesai' => 'Selesai', 'ditunda' => 'Ditunda', 'dibatalkan' => 'Dibatalkan'];
$selectHtml = function (string $name, string $label, array $opts, $cur) {
    $h = '<div class="col-md-3 col-lg-2"><select class="form-select form-select-sm" name="' . $name . '"><option value="">' . e($label) . '</option>';
    foreach ($opts as $v => $l) $h .= '<option value="' . e($v) . '"' . ((string)$cur === (string)$v ? ' selected' : '') . '>' . e($l) . '</option>';
    return $h . '</select></div>';
};
?>
<div class="panel">
  <div class="panel-head d-flex flex-wrap gap-2 justify-content-between align-items-center no-print">
    <h2><?= e($judul) ?></h2>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
      <a class="btn btn-success btn-sm" href="<?= e(qs(['export' => 'xls'])) ?>"><i class="bi bi-file-earmark-excel"></i> Ekspor Excel</a>
    </div>
  </div>
  <div class="panel-body">
    <form method="get" class="row g-2 mb-3 no-print">
      <input type="hidden" name="open" value="laporan"><input type="hidden" name="jenis" value="<?= e($jenis) ?>">
      <?php
      if (in_array('q', $show)) echo '<div class="col-md-3 col-lg-3"><input class="form-control form-control-sm" type="search" name="q" value="' . e($f['q']) . '" placeholder="Cari nama..."></div>';
      if (in_array('klub', $show)) echo $selectHtml('klub', 'Semua klub', $klubOpt, $f['klub']);
      if (in_array('posisi', $show)) echo $selectHtml('posisi', 'Semua posisi', $posOpt, $f['posisi']);
      if (in_array('status', $show)) echo $selectHtml('status', 'Semua status', $statusOpt, $f['status']);
      if (in_array('dari', $show)) echo '<div class="col-6 col-md-2"><input class="form-control form-control-sm" type="date" name="dari" value="' . e($f['dari']) . '" title="Dari tanggal"></div>'
          . '<div class="col-6 col-md-2"><input class="form-control form-control-sm" type="date" name="sampai" value="' . e($f['sampai']) . '" title="Sampai tanggal"></div>';
      ?>
      <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Terapkan</button> <a class="btn btn-link btn-sm" href="index.php?open=laporan&jenis=<?= e($jenis) ?>">Reset</a></div>
    </form>

    <div class="print-only mb-3"><h3 class="mb-0"><?= e($judul) ?></h3><div><?= e($ketTxt) ?></div><small>Dicetak: <?= e(date('d/m/Y H:i')) ?> oleh <?= e($_SESSION['nama']) ?></small></div>

    <div class="table-responsive"><table class="table table-pitch table-sm align-middle">
      <thead><tr><th>No</th><?php foreach ($cols as $c) echo '<th>' . e($c) . '</th>'; ?></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr><td class="text-muted"><?= $i + 1 ?></td><?php foreach ($r as $v) echo '<td>' . e($v) . '</td>'; ?></tr>
      <?php endforeach; ?>
      <?php if ($totalRow): ?><tr class="fw-bold table-light"><td></td><?php foreach ($totalRow as $v) echo '<td>' . e($v) . '</td>'; ?></tr><?php endif; ?>
      <?php if (!$rows): ?><tr><td colspan="<?= count($cols) + 1 ?>" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <small class="text-muted"><?= count($rows) ?> baris · <?= e($ketTxt) ?></small>

    <?php if ($chart && $rows): ?>
      <hr><h3 class="fs-5">Grafik (10 teratas)</h3>
      <div style="max-width:900px"><canvas id="chLap" height="110" role="img" aria-label="Grafik laporan"></canvas></div>
    <?php endif; ?>
  </div>
</div>
<?php
if ($chart && $rows) {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $sets = array_map(fn($s) => ['label' => $s[0], 'data' => $s[1], 'backgroundColor' => $s[2]], $chart['sets']);
    $EXTRA_JS = '<script>new Chart(document.getElementById("chLap"), { type: "bar", data: { labels: ' . json_encode($chart['labels'], $flags)
        . ', datasets: ' . json_encode($sets, $flags) . ' }, options: { scales: { y: { beginAtZero: true' . ($jenis === 'performa' ? ', max: 10' : ', ticks: { precision: 0 }') . ' } }, plugins: { legend: { position: "bottom" } } } });</script>';
}
