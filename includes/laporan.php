<?php
// Filter + query audit transaksi (dipakai audit.php dan audit_ekspor.php agar hasil tampilan = hasil ekspor)
const AUDIT_DARI = 'FROM transaksi t JOIN kendaraan k ON k.id=t.kendaraan_id
    JOIN bahan_bakar b ON b.id=t.bbm_id JOIN users u ON u.id=t.user_id';

function tgl_valid(mixed $s): ?string {
    if (!is_string($s)) return null;
    $d = DateTime::createFromFormat('!Y-m-d', (string)$s);
    return $d && $d->format('Y-m-d') === $s ? $s : null;
}
function tgl_id(string $s): string { return date('d/m/Y', strtotime($s)); }

// Mengembalikan [filter ternormalisasi, klausa WHERE, tipe bind, parameter]
function audit_filter(mysqli $db, array $g): array {
    $hariIni = query($db, 'SELECT CURDATE() d')[0]['d'];
    $sampai = tgl_valid($g['sampai'] ?? null) ?? $hariIni;
    $dari = tgl_valid($g['dari'] ?? null) ?? date('Y-m-d', strtotime("$sampai -6 days"));
    if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

    $f = ['dari' => $dari, 'sampai' => $sampai, 'bbm' => (int)($g['bbm'] ?? 0), 'kasir' => (int)($g['kasir'] ?? 0),
          'metode' => in_array($g['metode'] ?? '', ['Tunai', 'QRIS', 'Debit'], true) ? $g['metode'] : '',
          'q' => is_string($g['q'] ?? null) ? bersih($g['q']) : ''];

    $w = ['t.tanggal >= ?', 't.tanggal < DATE_ADD(?, INTERVAL 1 DAY)']; $tp = 'ss'; $p = [$dari, $sampai];
    if ($f['bbm'])    { $w[] = 't.bbm_id = ?';  $tp .= 'i'; $p[] = $f['bbm']; }
    if ($f['kasir'])  { $w[] = 't.user_id = ?'; $tp .= 'i'; $p[] = $f['kasir']; }
    if ($f['metode']) { $w[] = 't.metode = ?';  $tp .= 's'; $p[] = $f['metode']; }
    if ($f['q'] !== '') {
        $w[] = "(REPLACE(k.plat,' ','') LIKE ? OR k.pemilik LIKE ?)"; $tp .= 'ss';
        $p[] = '%' . str_replace(' ', '', $f['q']) . '%'; $p[] = '%' . $f['q'] . '%';
    }
    return [$f, 'WHERE ' . implode(' AND ', $w), $tp, $p];
}

function audit_ringkas(mysqli $db, string $where, string $tp, array $p): array {
    $r = query($db, "SELECT COUNT(*) trx, COALESCE(SUM(t.liter),0) liter, COALESCE(SUM(t.liter_subsidi),0) sub,
        COALESCE(SUM(t.liter_non_subsidi),0) non, COALESCE(SUM(t.total),0) omzet " . AUDIT_DARI . " $where", $tp, $p)[0];
    return ['trx' => (int)$r['trx'], 'liter' => (float)$r['liter'], 'sub' => (float)$r['sub'], 'non' => (float)$r['non'], 'omzet' => (float)$r['omzet']];
}

function audit_rekap_bbm(mysqli $db, string $where, string $tp, array $p): array {
    return query($db, "SELECT b.nama, COUNT(*) trx, SUM(t.liter) liter, SUM(t.total) omzet " . AUDIT_DARI .
        " $where GROUP BY b.id, b.nama ORDER BY omzet DESC", $tp, $p);
}

function audit_baris(mysqli $db, string $where, string $tp, array $p, ?int $batas = null, int $off = 0): array {
    $sql = "SELECT t.id, t.tanggal, k.plat, k.jenis, k.pemilik, b.nama bbm, t.isi_tangki_awal, t.liter, t.liter_subsidi, t.liter_non_subsidi,
        t.total, t.metode, u.nama kasir " . AUDIT_DARI . " $where ORDER BY t.tanggal DESC, t.id DESC";
    if ($batas !== null) { $sql .= ' LIMIT ? OFFSET ?'; $tp .= 'ii'; $p[] = $batas; $p[] = $off; }
    return query($db, $sql, $tp, $p);
}
