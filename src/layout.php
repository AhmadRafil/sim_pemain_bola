<?php
function nav_menu(): array {
    return [
        ['Dashboard', 'dashboard', null, 'bi-grid-1x2'],
        'Data master' => [
            ['Data Klub', 'klub', null, 'bi-shield'],
            ['Data Pemain', 'pemain', null, 'bi-person-badge'],
            ['Data Posisi', 'posisi', null, 'bi-diagram-3'],
            ['Data Pelatih', 'pelatih', null, 'bi-clipboard2-pulse'],
        ],
        'Operasional' => [
            ['Data Pertandingan', 'pertandingan', null, 'bi-calendar-event'],
            ['Statistik Pemain', 'statistik', null, 'bi-bar-chart-line'],
            ['Evaluasi Pemain', 'evaluasi', null, 'bi-card-checklist'],
        ],
        'Laporan' => [
            ['Laporan Data Pemain', 'laporan', 'pemain', 'bi-file-earmark-text'],
            ['Laporan Statistik Pemain', 'laporan', 'statistik', 'bi-file-earmark-bar-graph'],
            ['Laporan Hasil Pertandingan', 'laporan', 'pertandingan', 'bi-file-earmark-ruled'],
            ['Laporan Performa Pemain', 'laporan', 'performa', 'bi-file-earmark-medical'],
        ],
        'Pengguna' => [
            ['Data Pengguna', 'users', null, 'bi-people'],
            ['Pengaturan Akun', 'akun', null, 'bi-gear'],
        ],
    ];
}

function layout_start(string $title, string $open): void {
    global $BASE;
    $jenis = $_GET['jenis'] ?? 'pemain';
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . e($title) . ' · Manajemen Pemain</title>'
       . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
       . '<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">'
       . '<link href="' . $BASE . 'src/style.css" rel="stylesheet"></head><body>';

    echo '<aside class="sidebar" id="sidebar"><div class="brand"><span class="brand-ball"></span><span>Liga<br>Pemain</span></div><nav>';
    foreach (nav_menu() as $group => $items) {
        if (isset($items[0]) && is_string($items[0])) { $items = [$items]; $group = ''; }
        $visible = array_filter($items, fn($i) => can($i[1], 'view'));
        if (!$visible) continue;
        if ($group !== '') echo '<div class="nav-group">' . e($group) . '</div>';
        foreach ($visible as [$label, $mod, $j, $icon]) {
            $active = $open === $mod && ($j === null || $j === $jenis);
            $href = 'index.php?open=' . $mod . ($j ? '&jenis=' . $j : '');
            echo '<a class="nav-link-x' . ($active ? ' active' : '') . '" href="' . e($href) . '"><i class="bi ' . $icon . '"></i>' . e($label) . '</a>';
        }
    }
    echo '<a class="nav-link-x mt-3" href="' . $BASE . 'logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a></nav></aside>';

    echo '<div class="main"><header class="topbar"><button class="btn btn-sm btn-outline-secondary d-lg-none" id="toggleSidebar" aria-label="Menu"><i class="bi bi-list"></i></button>'
       . '<div class="topbar-title">' . e($title) . '</div><div class="ms-auto d-flex align-items-center gap-2">'
       . '<span class="text-end lh-sm d-none d-sm-block"><strong>' . e($_SESSION['nama']) . '</strong><br><small class="text-muted">' . e(ucfirst($_SESSION['role'])) . '</small></span>'
       . avatar(null, '', $_SESSION['nama'], 36) . '</div></header><main class="content">' . flash_show();
}

function layout_end(): void {
    global $BASE, $EXTRA_JS;
    echo '</main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>'
       . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>'
       . '<script src="' . $BASE . 'src/app.js"></script>' . ($EXTRA_JS ?? '') . '</body></html>';
}
