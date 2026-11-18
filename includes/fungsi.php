<?php
// Minggu 3: fungsi kustom & kode modular (di-include dari semua halaman)
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }          // sanitasi output
function bersih(?string $s): string { return trim(strip_tags((string)$s)); }                 // sanitasi input
function rupiah(float $n): string { return 'Rp' . number_format($n, 0, ',', '.'); }
function redirect(string $url): never { header("Location: $url"); exit; }

function flash(?string $pesan = null, string $tipe = 'success'): ?array {
    if ($pesan !== null) { $_SESSION['flash'] = [$pesan, $tipe]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_cek(): void {
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $t)) { http_response_code(419); die('Token tidak valid. Muat ulang halaman.'); }
}

// Minggu 9: pagination Bootstrap (dipakai kendaraan.php & riwayat.php)
function paginasi(int $total, int $perHal, int $hal, array $query = []): string {
    $jml = max(1, (int)ceil($total / $perHal));
    if ($jml <= 1) return '';
    $out = '<nav><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $jml; $i++) {
        $url = '?' . http_build_query($query + ['hal' => $i]);
        $out .= '<li class="page-item' . ($i === $hal ? ' active' : '') . '"><a class="page-link" href="' . e($url) . '">' . $i . '</a></li>';
    }
    return $out . '</ul></nav>';
}
function halaman_aktif(): int { return max(1, (int)($_GET['hal'] ?? 1)); }

// Minggu 7: helper prepared statement -> array of assoc
function query(mysqli $db, string $sql, string $tipe = '', array $param = []): array {
    $st = $db->prepare($sql);
    if ($param) $st->bind_param($tipe, ...$param);
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
