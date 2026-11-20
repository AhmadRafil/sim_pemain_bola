<?php
$PAGE_TITLE = 'Dashboard';

$jml = [
    'Pemain'     => (int)db_val('SELECT COUNT(*) FROM pemain'),
    'Klub'       => (int)db_val('SELECT COUNT(*) FROM klub'),
    'Pelatih'    => (int)db_val('SELECT COUNT(*) FROM pelatih'),
    'Pertandingan' => (int)db_val('SELECT COUNT(*) FROM pertandingan'),
];
$totalGol = (int)db_val('SELECT COALESCE(SUM(jumlah_gol),0) FROM statistik_pemain');
$selesai = (int)db_val("SELECT COUNT(*) FROM pertandingan WHERE status_pertandingan = 'selesai'");

$joinKlub = 'JOIN klub kh ON kh.id_klub = m.id_klub_tuan_rumah JOIN klub kt ON kt.id_klub = m.id_klub_tamu';
$last = db_row("SELECT m.*, kh.nama_klub AS home, kt.nama_klub AS away FROM pertandingan m $joinKlub
                WHERE m.status_pertandingan = 'selesai' ORDER BY m.tanggal_pertandingan DESC LIMIT 1");
$next = db_all("SELECT m.*, kh.nama_klub AS home, kt.nama_klub AS away FROM pertandingan m $joinKlub
                WHERE m.status_pertandingan = 'dijadwalkan' AND m.tanggal_pertandingan >= NOW() ORDER BY m.tanggal_pertandingan ASC LIMIT 4");

$scorers = db_all('SELECT p.id_pemain, p.nama_pemain, p.foto_pemain, k.nama_klub,
                          SUM(s.jumlah_gol) AS gol, SUM(s.jumlah_assist) AS assist
                   FROM statistik_pemain s JOIN pemain p ON p.id_pemain = s.id_pemain JOIN klub k ON k.id_klub = p.id_klub
                   GROUP BY p.id_pemain, p.nama_pemain, p.foto_pemain, k.nama_klub
                   HAVING gol > 0 ORDER BY gol DESC, assist DESC, p.nama_pemain LIMIT 5');
$best = db_all('SELECT p.id_pemain, p.nama_pemain, p.foto_pemain, k.nama_klub, COUNT(*) AS n,
                       ROUND(AVG((e.penilaian_teknik + e.penilaian_fisik + e.penilaian_kerja_sama) / 3), 1) AS rata
                FROM evaluasi_pemain e JOIN pemain p ON p.id_pemain = e.id_pemain JOIN klub k ON k.id_klub = p.id_klub
                GROUP BY p.id_pemain, p.nama_pemain, p.foto_pemain, k.nama_klub
                ORDER BY rata DESC, n DESC, p.nama_pemain LIMIT 5');
$posisi = db_all('SELECT ps.nama_posisi, COUNT(p.id_pemain) AS n FROM posisi ps LEFT JOIN pemain p ON p.id_posisi = ps.id_posisi
                  GROUP BY ps.id_posisi, ps.nama_posisi ORDER BY ps.id_posisi');
?>
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <section class="scoreboard h-100">
      <?php if ($last): ?>
        <div class="sb-meta">Hasil terakhir · <?= e(tgl($last['tanggal_pertandingan'])) ?> · <?= e($last['lokasi']) ?></div>
        <div class="sb-row">
          <div class="sb-team text-end"><?= e($last['home']) ?></div>
          <div class="sb-score"><span><?= (int)$last['skor_tuan_rumah'] ?></span><i>:</i><span><?= (int)$last['skor_tamu'] ?></span></div>
          <div class="sb-team"><?= e($last['away']) ?></div>
        </div>
      <?php else: ?>
        <div class="sb-meta">Belum ada pertandingan yang selesai.</div>
        <div class="sb-team mt-2">Tambahkan hasil pertandingan agar papan skor terisi.</div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-4">
    <div class="panel h-100 mb-0"><div class="panel-head"><h2>Pertandingan berikutnya</h2></div><div class="panel-body">
      <?php foreach ($next as $n): ?>
        <div class="mb-2"><strong><?= e($n['home']) ?> vs <?= e($n['away']) ?></strong><br><small class="text-muted"><?= e(tgl($n['tanggal_pertandingan'], true)) ?></small></div>
      <?php endforeach; if (!$next) echo '<span class="text-muted">Tidak ada jadwal mendatang.</span>'; ?>
    </div></div>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($jml as $l => $v): ?>
    <div class="col-6 col-lg"><div class="tile"><div class="num"><?= $v ?></div><div class="lbl">Jumlah <?= strtolower($l) ?><?= $l === 'Pertandingan' ? " ($selesai selesai)" : '' ?></div></div></div>
  <?php endforeach; ?>
  <div class="col-12 col-lg"><div class="tile t-gol"><div class="num"><?= $totalGol ?></div><div class="lbl">Total gol tercatat</div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="panel h-100 mb-0"><div class="panel-head"><h2>Top pencetak gol</h2></div><div class="panel-body"><canvas id="chGol" height="120" aria-label="Grafik top pencetak gol" role="img"></canvas></div></div></div>
  <div class="col-lg-4"><div class="panel h-100 mb-0"><div class="panel-head"><h2>Komposisi posisi</h2></div><div class="panel-body"><canvas id="chPos" height="200" aria-label="Grafik komposisi posisi" role="img"></canvas></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6"><div class="panel"><div class="panel-head"><h2>Statistik terbaik</h2></div><div class="panel-body">
    <?php foreach ($scorers as $s): ?>
      <div class="rank-row"><?= avatar($s['foto_pemain'], 'pemain', $s['nama_pemain'], 38) ?>
        <div><a class="fw-semibold text-decoration-none" href="index.php?open=pemain&act=detail&id=<?= $s['id_pemain'] ?>"><?= e($s['nama_pemain']) ?></a><br><small class="text-muted"><?= e($s['nama_klub']) ?> · <?= (int)$s['assist'] ?> assist</small></div>
        <div class="big"><?= (int)$s['gol'] ?> <small class="text-muted fs-6">gol</small></div></div>
    <?php endforeach; if (!$scorers) echo '<span class="text-muted">Belum ada statistik gol.</span>'; ?>
  </div></div></div>
  <div class="col-lg-6"><div class="panel"><div class="panel-head"><h2>Performa terbaik (rata-rata evaluasi)</h2></div><div class="panel-body">
    <?php foreach ($best as $b): ?>
      <div class="rank-row"><?= avatar($b['foto_pemain'], 'pemain', $b['nama_pemain'], 38) ?>
        <div><a class="fw-semibold text-decoration-none" href="index.php?open=pemain&act=detail&id=<?= $b['id_pemain'] ?>"><?= e($b['nama_pemain']) ?></a><br><small class="text-muted"><?= e($b['nama_klub']) ?> · <?= (int)$b['n'] ?> evaluasi</small></div>
        <div class="big"><?= number_format((float)$b['rata'], 1) ?></div></div>
    <?php endforeach; if (!$best) echo '<span class="text-muted">Belum ada evaluasi.</span>'; ?>
  </div></div></div>
</div>
<?php
$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$EXTRA_JS = '<script>
const gol = ' . json_encode($scorers, $flags) . ';
const pos = ' . json_encode($posisi, $flags) . ';
new Chart(document.getElementById("chGol"), { type: "bar",
  data: { labels: gol.map(x => x.nama_pemain), datasets: [
    { label: "Gol", data: gol.map(x => +x.gol), backgroundColor: "#1f7a4d" },
    { label: "Assist", data: gol.map(x => +x.assist), backgroundColor: "#f2c230" } ] },
  options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: "bottom" } } } });
new Chart(document.getElementById("chPos"), { type: "doughnut",
  data: { labels: pos.map(x => x.nama_posisi), datasets: [{ data: pos.map(x => +x.n), backgroundColor: ["#0d3b2a", "#1f7a4d", "#7fbf9a", "#f2c230", "#d64035", "#9aa89f"] }] },
  options: { plugins: { legend: { position: "bottom" } } } });
</script>';
