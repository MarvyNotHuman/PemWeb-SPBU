<?php
// Pembuat berkas ekspor tanpa library tambahan: CSV, XLSX (ZipArchive), dan PDF (penulis PDF sederhana).
// $kolom : [['key','label','tipe'(teks|int|des|rp),'lebar'(pt, untuk PDF)], ...]
// $baris : array asosiatif per baris;  $total : asosiatif (opsional) untuk baris total.

function kolom_audit(): array {
    return [
        ['key' => 'no', 'label' => 'No', 'tipe' => 'int', 'lebar' => 24],
        ['key' => 'tanggal', 'label' => 'Waktu', 'tipe' => 'teks', 'lebar' => 84],
        ['key' => 'plat', 'label' => 'Plat', 'tipe' => 'teks', 'lebar' => 60],
        ['key' => 'jenis', 'label' => 'Jenis', 'tipe' => 'teks', 'lebar' => 40],
        ['key' => 'pemilik', 'label' => 'Pemilik', 'tipe' => 'teks', 'lebar' => 100],
        ['key' => 'bbm', 'label' => 'BBM', 'tipe' => 'teks', 'lebar' => 92],
        ['key' => 'isi_tangki_awal', 'label' => 'Tangki awal', 'tipe' => 'des', 'lebar' => 52],
        ['key' => 'liter', 'label' => 'Liter', 'tipe' => 'des', 'lebar' => 42],
        ['key' => 'liter_subsidi', 'label' => 'L. Subsidi', 'tipe' => 'des', 'lebar' => 50],
        ['key' => 'liter_non_subsidi', 'label' => 'L. Non-sub', 'tipe' => 'des', 'lebar' => 52],
        ['key' => 'total', 'label' => 'Total (Rp)', 'tipe' => 'rp', 'lebar' => 68],
        ['key' => 'metode', 'label' => 'Metode', 'tipe' => 'teks', 'lebar' => 42],
        ['key' => 'kasir', 'label' => 'Kasir', 'tipe' => 'teks', 'lebar' => 77],
    ];
}

function ekspor_angka(string $tipe, $v): int|float { return $tipe === 'int' ? (int)$v : (float)$v; }

function kirim_unduhan(string $nama, string $mime, string $isi): never {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $nama . '"');
    header('Content-Length: ' . strlen($isi));
    header('Cache-Control: no-store');
    echo $isi;
    exit;
}

// ---------------------------------------------------------------- CSV
function bangun_csv(array $kolom, array $baris, array $total = []): string {
    $f = fopen('php://temp', 'r+');
    fwrite($f, "\xEF\xBB\xBF");                                   // BOM agar Excel membaca UTF-8
    $tulis = fn(array $r) => fputcsv($f, $r, ',', '"', '\\');
    $aman = fn(string $s) => preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;   // cegah formula injection
    $nilai = fn(array $b, array $k) => !isset($b[$k['key']]) ? '' :
        ($k['tipe'] === 'teks' ? $aman((string)$b[$k['key']]) : ekspor_angka($k['tipe'], $b[$k['key']]));
    $tulis(array_column($kolom, 'label'));
    foreach ($baris as $b) $tulis(array_map(fn($k) => $nilai($b, $k), $kolom));
    if ($total) $tulis(array_map(fn($k) => $nilai($total, $k), $kolom));
    rewind($f);
    return stream_get_contents($f);
}

// ---------------------------------------------------------------- XLSX
function xl_kol(int $i): string {
    $s = '';
    for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) $s = chr(65 + ($n - 1) % 26) . $s;
    return $s;
}
function xl_teks(string $s): string {
    return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function bangun_xlsx(string $judul, array $kolom, array $baris, array $meta = [], array $total = []): string {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP zip belum aktif (php.ini: extension=zip).');
    $gayaAngka = ['int' => 3, 'rp' => 3, 'des' => 4];             // lihat styles.xml
    $r = 0; $xml = '';
    $sel = fn(int $c, int $row, string $teks, int $s = 0) => '<c r="' . xl_kol($c) . $row . '" t="inlineStr"' . ($s ? " s=\"$s\"" : '') . '><is><t xml:space="preserve">' . xl_teks($teks) . '</t></is></c>';

    $r++; $xml .= "<row r=\"$r\">" . $sel(0, $r, $judul, 7) . '</row>';
    foreach ($meta as $m) { $r++; $xml .= "<row r=\"$r\">" . $sel(0, $r, $m) . '</row>'; }
    $r += 2; $kepala = $r;
    $xml .= "<row r=\"$r\">";
    foreach ($kolom as $c => $k) $xml .= $sel($c, $r, $k['label'], 2);
    $xml .= '</row>';

    $isiBaris = function (array $b, bool $tebal) use (&$r, $kolom, $gayaAngka, $sel): string {
        $o = "<row r=\"$r\">";
        foreach ($kolom as $c => $k) {
            if (!isset($b[$k['key']])) continue;
            $v = $b[$k['key']];
            if ($k['tipe'] === 'teks') { $o .= $sel($c, $r, (string)$v, $tebal ? 1 : 0); continue; }
            $s = $tebal ? ($k['tipe'] === 'des' ? 6 : 5) : $gayaAngka[$k['tipe']];
            $o .= '<c r="' . xl_kol($c) . $r . '" s="' . $s . '"><v>' . ekspor_angka($k['tipe'], $v) . '</v></c>';
        }
        return $o . '</row>';
    };
    foreach ($baris as $b) { $r++; $xml .= $isiBaris($b, false); }
    if ($total) { $r++; $xml .= $isiBaris($total, true); }

    $lebar = '<cols>';
    foreach ($kolom as $c => $k) $lebar .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . round($k['lebar'] / 5.2 + 2, 1) . '" customWidth="1"/>';
    $lebar .= '</cols>';
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $kepala . '" topLeftCell="A' . ($kepala + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . $lebar . '<sheetData>' . $xml . '</sheetData></worksheet>';

    $h = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $berkas = [
        '[Content_Types].xml' => $h . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
        '_rels/.rels' => $h . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml' => $h . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Audit Transaksi" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => $h . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/styles.xml' => $h . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0A3D91"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="3" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            . '<xf numFmtId="2" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        'xl/worksheets/sheet1.xml' => $sheet,
    ];
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Gagal membuat berkas xlsx.');
    foreach ($berkas as $nama => $isi) $zip->addFromString($nama, $isi);
    $zip->close();
    $out = file_get_contents($tmp);
    unlink($tmp);
    return $out;
}

// ---------------------------------------------------------------- PDF
function pdf_str(string $s): string {
    $s = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $s);
}
function pdf_lebar(string $s, float $ukuran): float {          // perkiraan lebar teks Helvetica
    $w = 0.0;
    foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $c) {
        $w += ctype_digit($c) ? .556 : (str_contains(" .,:;|!ilI'", $c) ? .28 : (($c >= 'A' && $c <= 'Z') || $c === 'm' || $c === 'w' ? .69 : .52));
    }
    return $w * $ukuran;
}
function pdf_potong(string $s, float $maks, float $ukuran): string {
    if (pdf_lebar($s, $ukuran) <= $maks) return $s;
    while ($s !== '' && pdf_lebar($s . '..', $ukuran) > $maks) $s = mb_substr($s, 0, -1);
    return $s . '..';
}
function pdf_nilai(string $tipe, $v): string {
    return match ($tipe) {
        'int', 'rp' => number_format((float)$v, 0, ',', '.'),
        'des' => number_format((float)$v, 2, ',', '.'),
        default => (string)$v,
    };
}

function bangun_pdf(string $judul, array $kolom, array $baris, array $meta = [], array $total = []): string {
    $W = 841.89; $H = 595.28; $mx = 28; $atas = 34; $bawah = 38; $tinggi = 15; $uk = 7.5;     // A4 landscape
    $skala = min(1, ($W - 2 * $mx) / array_sum(array_column($kolom, 'lebar')));
    $xs = []; $lb = []; $x = $mx;
    foreach ($kolom as $i => $k) { $lb[$i] = $k['lebar'] * $skala; $xs[$i] = $x; $x += $lb[$i]; }
    $lebarTabel = $x - $mx;

    $teks = fn(float $x, float $y, string $s, float $ukuran, bool $tebal = false) =>
        sprintf("BT /%s %.1F Tf %.2F %.2F Td (%s) Tj ET\n", $tebal ? 'F2' : 'F1', $ukuran, $x, $y, pdf_str($s));
    $gambar = function (array $vals, float $y, bool $tebal, ?array $isi, bool $putih) use ($kolom, $xs, $lb, $uk, $tinggi, $lebarTabel, $mx, $teks): string {
        $o = $isi ? sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n", $isi[0], $isi[1], $isi[2], $mx, $y, $lebarTabel, $tinggi) : '';
        $o .= $putih ? "1 g\n" : "0 g\n";
        foreach ($kolom as $i => $k) {
            $s = (string)($vals[$i] ?? '');
            if ($s === '') continue;
            $s = pdf_potong($s, $lb[$i] - 6, $uk);
            $px = $k['tipe'] === 'teks' ? $xs[$i] + 3 : $xs[$i] + $lb[$i] - 3 - pdf_lebar($s, $uk);
            $o .= $teks($px, $y + 4.5, $s, $uk, $tebal);
        }
        return $o . "0 g\n";
    };
    $format = fn(array $r) => array_map(fn($k) => isset($r[$k['key']]) ? pdf_nilai($k['tipe'], $r[$k['key']]) : '', $kolom);

    $kepala = array_column($kolom, 'label');
    $biru = [0.039, 0.239, 0.569];
    $hal = []; $buf = ''; $y = $H - $atas;
    $buf .= $teks($mx, $y - 14, $judul, 14, true); $y -= 24;
    foreach ($meta as $m) { $buf .= $teks($mx, $y - 9, $m, 8.5); $y -= 12; }
    $y -= 8;
    $y -= $tinggi; $buf .= $gambar($kepala, $y, true, $biru, true);

    $item = array_map(fn($b) => [$format($b), false], $baris);
    if ($total) $item[] = [$format($total), true];
    if (!$baris) { $y -= $tinggi; $buf .= $teks($mx + 3, $y + 4.5, 'Tidak ada transaksi pada rentang ini.', $uk); }
    foreach ($item as $n => [$vals, $tebal]) {
        if ($y - $tinggi < $bawah) {
            $hal[] = $buf; $buf = ''; $y = $H - $atas;
            $buf .= $teks($mx, $y - 10, $judul . ' (lanjutan)', 10, true); $y -= 20;
            $y -= $tinggi; $buf .= $gambar($kepala, $y, true, $biru, true);
        }
        $y -= $tinggi;
        $isi = $tebal ? [1.0, 0.95, 0.8] : ($n % 2 ? [0.955, 0.965, 0.99] : null);
        $buf .= $gambar($vals, $y, $tebal, $isi, false);
    }
    $hal[] = $buf;

    $jml = count($hal);
    $obj = [1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $kids = [];
    foreach ($hal as $i => $isi) {
        $pg = 5 + 2 * $i; $ct = $pg + 1; $kids[] = "$pg 0 R";
        $isi .= $teks($W - $mx - 70, 18, 'Halaman ' . ($i + 1) . ' dari ' . $jml, 8);
        $obj[$pg] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', $W, $H, $ct);
        $obj[$ct] = '<< /Length ' . strlen($isi) . " >>\nstream\n$isi\nendstream";
    }
    $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $jml . ' >>';
    ksort($obj);

    $out = "%PDF-1.4\n"; $off = [];
    foreach ($obj as $no => $isi) { $off[$no] = strlen($out); $out .= "$no 0 obj\n$isi\nendobj\n"; }
    $xref = strlen($out); $maks = max(array_keys($obj));
    $out .= "xref\n0 " . ($maks + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= $maks; $i++) $out .= sprintf("%010d 00000 n \n", $off[$i]);
    return $out . 'trailer
<< /Size ' . ($maks + 1) . ' /Root 1 0 R /Info << /Title (' . pdf_str($judul) . ") >> >>\nstartxref\n$xref\n%%EOF";
}
