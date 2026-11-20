<?php
/**
 * Mesin CRUD berbasis konfigurasi. Setiap modul (klub, pemain, dst.) cukup mendefinisikan
 * array konfigurasi lalu memanggil crud($config).
 *
 * Konfigurasi: key, title, table, pk, fields[], list[], validate?, can_delete?, detail?
 * Semua query memakai prepared statement; semua POST wajib token CSRF; hak akses lewat can().
 */

function field_opts(array $f): array {
    static $cache = [];
    if (isset($f['options'])) return $f['options'];
    $k = md5($f['sql']);
    if (!isset($cache[$k])) {
        $cache[$k] = [];
        foreach (db_all($f['sql']) as $r) {
            $v = array_values($r);
            $cache[$k][(string)$v[0]] = (string)$v[1];
        }
    }
    return $cache[$k];
}

function deny_page(): void {
    http_response_code(403);
    echo '<div class="alert alert-danger">Anda tidak memiliki hak akses untuk tindakan ini.</div>';
}

/** Ambil & validasi input form sesuai definisi field. */
function crud_collect(array $c, int $id, array &$err): array {
    $d = [];
    foreach ($c['fields'] as $f) {
        $n = $f['name'];
        $t = $f['type'] ?? 'text';
        $L = $f['label'];
        $req = !empty($f['required']);

        if ($t === 'image') {
            $file = $_FILES[$n] ?? null;
            if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $res = upload_image($file, $f['dir'], $uerr);
                if ($res === null) $err[] = "$L: $uerr"; else $d[$n] = $res;
            }
            continue;
        }

        $v = trim((string)($_POST[$n] ?? ''));

        if ($t === 'password') {
            if ($v === '') { if (!$id) $err[] = "$L wajib diisi."; continue; }
            if (strlen($v) < 6) { $err[] = "$L minimal 6 karakter."; continue; }
            $d[$f['column']] = password_hash($v, PASSWORD_DEFAULT);
            continue;
        }

        if ($v === '') {
            if ($req) $err[] = "$L wajib diisi.";
            $d[$n] = null;
            continue;
        }

        switch ($t) {
            case 'number':
                if (!is_numeric($v)) { $err[] = "$L harus berupa angka."; break; }
                if (empty($f['decimal']) && filter_var($v, FILTER_VALIDATE_INT) === false) { $err[] = "$L harus berupa bilangan bulat."; break; }
                $num = !empty($f['decimal']) ? (float)$v : (int)$v;
                if (isset($f['min']) && $num < $f['min']) { $err[] = "$L minimal {$f['min']}."; break; }
                if (isset($f['max']) && $num > $f['max']) { $err[] = "$L maksimal {$f['max']}."; break; }
                $v = $num;
                break;
            case 'date':
                $dt = DateTime::createFromFormat('Y-m-d', $v);
                if (!$dt || $dt->format('Y-m-d') !== $v) { $err[] = "$L bukan tanggal yang valid."; break; }
                if (!empty($f['not_future']) && $v > date('Y-m-d')) $err[] = "$L tidak boleh di masa depan.";
                break;
            case 'datetime':
                $dt = DateTime::createFromFormat('Y-m-d\TH:i', $v);
                if (!$dt) { $err[] = "$L bukan tanggal/waktu yang valid."; break; }
                $v = $dt->format('Y-m-d H:i:s');
                break;
            case 'select':
                if (!array_key_exists($v, field_opts($f))) $err[] = "$L tidak valid.";
                break;
            default: // text, textarea
                if (mb_strlen($v) > ($f['max'] ?? 255)) $err[] = "$L maksimal " . ($f['max'] ?? 255) . ' karakter.';
        }
        $d[$n] = $v;
    }
    return $d;
}

function crud_save(array $c, int $id, array $d): int {
    $cols = array_keys($d);
    if ($id) {
        $set = implode(',', array_map(fn($x) => "`$x`=?", $cols));
        db_exec("UPDATE `{$c['table']}` SET $set WHERE `{$c['pk']}`=?", [...array_values($d), $id]);
        return $id;
    }
    $names = implode(',', array_map(fn($x) => "`$x`", $cols));
    $ph = implode(',', array_fill(0, count($cols), '?'));
    db_exec("INSERT INTO `{$c['table']}` ($names) VALUES ($ph)", array_values($d));
    return db_insert_id();
}

function crud_image_fields(array $c): array {
    return array_values(array_filter($c['fields'], fn($f) => ($f['type'] ?? '') === 'image'));
}

/** Titik masuk modul. */
function crud(array $c): void {
    global $PAGE_TITLE;
    $m = $c['key'];
    $PAGE_TITLE = $c['title'];
    $act = $_GET['act'] ?? 'list';
    $id = (int)($_GET['id'] ?? 0);
    $vals = null; $err = []; $old = null;
    $back = 'index.php?open=' . $m;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $pa = $_POST['_act'] ?? '';

        if ($pa === 'hapus') {
            if (!can($m, 'delete')) { deny_page(); return; }
            $did = (int)($_POST['id'] ?? 0);
            $row = db_row("SELECT * FROM `{$c['table']}` WHERE `{$c['pk']}`=?", [$did]);
            if (!$row) flash('danger', 'Data tidak ditemukan.');
            elseif (isset($c['can_delete']) && ($msg = $c['can_delete']($row))) flash('danger', $msg);
            else {
                try {
                    db_exec("DELETE FROM `{$c['table']}` WHERE `{$c['pk']}`=?", [$did]);
                    foreach (crud_image_fields($c) as $f) delete_upload($row[$f['name']] ?? null, $f['dir']);
                    flash('success', 'Data berhasil dihapus.');
                } catch (mysqli_sql_exception $ex) {
                    flash('danger', $ex->getCode() == 1451
                        ? 'Data tidak dapat dihapus karena masih dipakai oleh data lain.'
                        : 'Gagal menghapus data.');
                }
            }
            redirect($back);
        }

        if ($pa === 'simpan') {
            $id = (int)($_POST['_id'] ?? 0);
            if (!can($m, $id ? 'edit' : 'create')) { deny_page(); return; }
            $old = $id ? db_row("SELECT * FROM `{$c['table']}` WHERE `{$c['pk']}`=?", [$id]) : null;
            if ($id && !$old) { flash('danger', 'Data tidak ditemukan.'); redirect($back); }

            $d = crud_collect($c, $id, $err);
            if (!$err && isset($c['validate'])) $c['validate']($d, $id, $err);

            if (!$err) {
                try {
                    crud_save($c, $id, $d);
                    if ($old) foreach (crud_image_fields($c) as $f) {
                        if (!empty($d[$f['name']])) delete_upload($old[$f['name']] ?? null, $f['dir']);
                    }
                    flash('success', $id ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
                    redirect($back);
                } catch (mysqli_sql_exception $ex) {
                    foreach (crud_image_fields($c) as $f) if (!empty($d[$f['name']])) delete_upload($d[$f['name']], $f['dir']);
                    $err[] = match ((int)$ex->getCode()) {
                        1062 => 'Data duplikat: nilai yang sama sudah ada.',
                        1452 => 'Relasi data tidak valid (data induk tidak ditemukan).',
                        default => 'Gagal menyimpan data.',
                    };
                }
            } else {
                foreach (crud_image_fields($c) as $f) if (!empty($d[$f['name']])) delete_upload($d[$f['name']], $f['dir']);
            }
            $vals = $_POST;
            $act = $id ? 'edit' : 'tambah';
        }
    }

    switch ($act) {
        case 'tambah':
            if (!can($m, 'create')) { deny_page(); return; }
            crud_form($c, 0, $vals ?? [], null, $err);
            break;
        case 'edit':
            if (!can($m, 'edit')) { deny_page(); return; }
            $old = $old ?? db_row("SELECT * FROM `{$c['table']}` WHERE `{$c['pk']}`=?", [$id]);
            if (!$old) { flash('danger', 'Data tidak ditemukan.'); redirect($back); }
            if ($vals === null) {
                $vals = $old;
                foreach ($c['fields'] as $f) if (($f['type'] ?? '') === 'datetime' && !empty($old[$f['name']]))
                    $vals[$f['name']] = str_replace(' ', 'T', substr($old[$f['name']], 0, 16));
            }
            crud_form($c, $id, $vals, $old, $err);
            break;
        case 'detail':
            $row = isset($c['detail']) ? db_row("SELECT * FROM `{$c['table']}` WHERE `{$c['pk']}`=?", [$id]) : null;
            if (!$row) { flash('danger', 'Data tidak ditemukan.'); redirect($back); }
            echo '<div class="d-flex justify-content-between align-items-center mb-3"><a href="' . e($back) . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>';
            if (can($m, 'edit')) echo '<a href="' . e($back) . '&act=edit&id=' . $id . '" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>';
            echo '</div>';
            $c['detail']($row);
            break;
        default:
            crud_list($c);
    }
}

function crud_form(array $c, int $id, array $vals, ?array $old, array $err): void {
    $m = $c['key'];
    $judul = ($id ? 'Edit ' : 'Tambah ') . $c['singular'];
    echo '<div class="panel"><div class="panel-head"><h2>' . e($judul) . '</h2></div><div class="panel-body">';
    if ($err) {
        echo '<div class="alert alert-danger"><strong>Data belum bisa disimpan:</strong><ul class="mb-0">';
        foreach ($err as $x) echo '<li>' . e($x) . '</li>';
        echo '</ul></div>';
    }
    echo '<form method="post" action="index.php?open=' . e($m) . '" enctype="multipart/form-data" class="row g-3" novalidate>';
    echo csrf_field() . '<input type="hidden" name="_act" value="simpan"><input type="hidden" name="_id" value="' . $id . '">';

    foreach ($c['fields'] as $f) {
        $n = $f['name']; $t = $f['type'] ?? 'text';
        $req = !empty($f['required']);
        $v = $vals[$n] ?? ($f['default'] ?? '');
        $lbl = e($f['label']) . ($req || ($t === 'password' && !$id) ? ' <span class="text-danger">*</span>' : '');
        echo '<div class="col-md-' . ($f['col'] ?? 6) . '"><label class="form-label" for="f_' . e($n) . '">' . $lbl . '</label>';
        $common = ' class="form-control" id="f_' . e($n) . '" name="' . e($n) . '"';
        switch ($t) {
            case 'textarea':
                echo '<textarea' . $common . ' rows="3" maxlength="' . ($f['max'] ?? 1000) . '">' . e($v) . '</textarea>'; break;
            case 'select':
                echo '<select class="form-select" id="f_' . e($n) . '" name="' . e($n) . '"><option value="">— Pilih —</option>';
                foreach (field_opts($f) as $ov => $ol) echo '<option value="' . e($ov) . '"' . ((string)$v === (string)$ov ? ' selected' : '') . '>' . e($ol) . '</option>';
                echo '</select>'; break;
            case 'image':
                if ($old && !empty($old[$n])) echo '<div class="mb-2">' . avatar($old[$n], $f['dir'], $f['label'], 64) . '</div>';
                echo '<input type="file"' . $common . ' accept="image/jpeg,image/png,image/webp">';
                break;
            case 'password':
                echo '<input type="password"' . $common . ' autocomplete="new-password">'; break;
            case 'number':
                echo '<input type="number"' . $common . ' value="' . e($v) . '"'
                   . (isset($f['min']) ? ' min="' . $f['min'] . '"' : '') . (isset($f['max']) ? ' max="' . $f['max'] . '"' : '')
                   . (!empty($f['decimal']) ? ' step="0.1"' : ' step="1"') . '>'; break;
            case 'date':
                echo '<input type="date"' . $common . ' value="' . e($v) . '">'; break;
            case 'datetime':
                echo '<input type="datetime-local"' . $common . ' value="' . e($v) . '">'; break;
            default:
                echo '<input type="text"' . $common . ' value="' . e($v) . '" maxlength="' . ($f['max'] ?? 255) . '">';
        }
        if (!empty($f['help'])) echo '<div class="form-text">' . e($f['help']) . '</div>';
        echo '</div>';
    }
    echo '<div class="col-12 d-flex gap-2 pt-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan</button>'
       . '<a class="btn btn-outline-secondary" href="index.php?open=' . e($m) . '">Batal</a></div></form></div></div>';
}

function crud_list(array $c): void {
    $m = $c['key'];
    $L = $c['list'];
    $where = []; $p = [];

    $q = trim($_GET['q'] ?? '');
    if ($q !== '' && !empty($L['search'])) {
        $parts = [];
        foreach ($L['search'] as $s) { $parts[] = "$s LIKE ?"; $p[] = "%$q%"; }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    foreach ($L['filters'] ?? [] as $f) {
        $v = trim($_GET[$f['name']] ?? '');
        if ($v === '') continue;
        if (isset($f['raw'])) { $where[] = $f['raw']; for ($i = 0; $i < ($f['n'] ?? 1); $i++) $p[] = $v; }
        else { $where[] = "{$f['col']} = ?"; $p[] = $v; }
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = (int)db_val("SELECT COUNT(*) FROM {$L['from']} $w", $p);
    $per = $L['per_page'] ?? 10;
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    $rows = db_all("SELECT {$L['select']} FROM {$L['from']} $w ORDER BY {$L['order']} LIMIT ? OFFSET ?",
                   array_merge($p, [$per, ($page - 1) * $per]));

    $canEdit = can($m, 'edit'); $canDel = can($m, 'delete'); $hasDetail = isset($c['detail']);

    echo '<div class="panel"><div class="panel-head d-flex flex-wrap gap-2 justify-content-between align-items-center"><h2>' . e($c['title']) . '</h2>';
    if (can($m, 'create')) echo '<a class="btn btn-primary btn-sm" href="index.php?open=' . e($m) . '&act=tambah"><i class="bi bi-plus-lg"></i> Tambah ' . e($c['singular']) . '</a>';
    echo '</div><div class="panel-body">';

    // pencarian + filter
    echo '<form method="get" class="row g-2 mb-3"><input type="hidden" name="open" value="' . e($m) . '">';
    if (!empty($L['search'])) echo '<div class="col-md-4"><input class="form-control" type="search" name="q" value="' . e($q) . '" placeholder="Cari ' . e(strtolower($c['singular'])) . '..."></div>';
    foreach ($L['filters'] ?? [] as $f) {
        $opts = isset($f['options']) ? $f['options'] : field_opts(['sql' => $f['sql']]);
        echo '<div class="col-md-3"><select class="form-select" name="' . e($f['name']) . '" data-autosubmit><option value="">' . e($f['label']) . '</option>';
        foreach ($opts as $ov => $ol) echo '<option value="' . e($ov) . '"' . ((string)($_GET[$f['name']] ?? '') === (string)$ov ? ' selected' : '') . '>' . e($ol) . '</option>';
        echo '</select></div>';
    }
    echo '<div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button> '
       . '<a class="btn btn-link" href="index.php?open=' . e($m) . '">Reset</a></div></form>';

    echo '<div class="table-responsive"><table class="table table-pitch align-middle"><thead><tr><th style="width:48px">No</th>';
    foreach ($L['cols'] as $col) echo '<th class="' . e($col['class'] ?? '') . '">' . e($col['label']) . '</th>';
    if ($hasDetail || $canEdit || $canDel) echo '<th class="text-end">Aksi</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
        $n = count($L['cols']) + 2;
        echo '<tr><td colspan="' . $n . '" class="text-center text-muted py-5">' . ($total === 0 && !$where
            ? 'Belum ada data ' . e(strtolower($c['singular'])) . '.' . (can($m, 'create') ? ' Klik "Tambah ' . e($c['singular']) . '" untuk memulai.' : '')
            : 'Tidak ada data yang cocok dengan pencarian atau filter.') . '</td></tr>';
    }
    foreach ($rows as $i => $r) {
        $pk = $r[$c['pk']];
        echo '<tr><td class="text-muted">' . (($page - 1) * $per + $i + 1) . '</td>';
        foreach ($L['cols'] as $col) echo '<td class="' . e($col['class'] ?? '') . '">' . $col['render']($r) . '</td>';
        if ($hasDetail || $canEdit || $canDel) {
            echo '<td class="text-end text-nowrap">';
            if ($hasDetail) echo '<a class="btn btn-sm btn-outline-secondary" title="Detail" href="index.php?open=' . e($m) . '&act=detail&id=' . $pk . '"><i class="bi bi-eye"></i></a> ';
            if ($canEdit) echo '<a class="btn btn-sm btn-outline-primary" title="Edit" href="index.php?open=' . e($m) . '&act=edit&id=' . $pk . '"><i class="bi bi-pencil"></i></a> ';
            if ($canDel) echo '<form method="post" action="index.php?open=' . e($m) . '" class="d-inline" data-confirm="Hapus data ini? Tindakan tidak dapat dibatalkan.">'
                . csrf_field() . '<input type="hidden" name="_act" value="hapus"><input type="hidden" name="id" value="' . $pk . '">'
                . '<button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>';
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    // pagination
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><small class="text-muted">' . $total . ' data</small>';
    if ($pages > 1) {
        echo '<nav><ul class="pagination pagination-sm mb-0">';
        $from = max(1, $page - 2); $to = min($pages, $page + 2);
        echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e(qs(['page' => $page - 1])) . '">&laquo;</a></li>';
        for ($i = $from; $i <= $to; $i++) echo '<li class="page-item' . ($i == $page ? ' active' : '') . '"><a class="page-link" href="' . e(qs(['page' => $i])) . '">' . $i . '</a></li>';
        echo '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '"><a class="page-link" href="' . e(qs(['page' => $page + 1])) . '">&raquo;</a></li></ul></nav>';
    }
    echo '</div></div></div>';
}
