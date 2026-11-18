<?php
require_once __DIR__ . '/init.php';
$judul ??= 'SPBU UNSOED';
$kelasMain ??= 'container pb-4';
$pesanFlash = flash();
$ini = basename($_SERVER['SCRIPT_NAME']);
function nav_item(string $file, string $label, string $ikon, array $alias = []): string {
    global $ini;
    $aktif = in_array($ini, [$file, ...$alias], true) ? ' active' : '';
    return "<li class=\"nav-item\"><a class=\"nav-link$aktif\" href=\"$file\"><i class=\"bi bi-$ikon me-1\"></i>$label</a></li>";
}
?><!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf_token() ?>">
<title><?= e($judul) ?> | SPBU UNSOED</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head><body>
<?php if (user()): ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-spbu mb-4"><div class="container">
  <a class="navbar-brand" href="index.php"><i class="bi bi-fuel-pump-fill me-2"></i>SPBU UNSOED</a>
  <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#m"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="m">
    <ul class="navbar-nav me-auto">
      <?= nav_item('index.php', 'Dashboard', 'speedometer2') ?>
      <?= nav_item('transaksi.php', 'Transaksi', 'cash-coin', ['struk.php']) ?>
      <?= nav_item('riwayat.php', 'Riwayat', 'clock-history') ?>
      <?= nav_item('kendaraan.php', 'Kendaraan', 'car-front-fill', ['kendaraan_form.php']) ?>
      <?= nav_item('audit.php', 'Audit', 'clipboard-data') ?>
      <?php if (user()['role'] === 'admin') echo nav_item('bbm.php', 'Bahan Bakar', 'droplet-half'); ?>
    </ul>
    <span class="navbar-text me-3 small"><i class="bi bi-person-circle me-1"></i><?= e(user()['nama']) ?> <span class="badge bg-light text-primary"><?= e(user()['role']) ?></span></span>
    <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
  </div></div></nav>
<?php endif; ?>
<main class="<?= e($kelasMain) ?>">
<?php if ($pesanFlash): ?><div class="alert alert-<?= e($pesanFlash[1]) ?> alert-dismissible shadow-sm"><?= e($pesanFlash[0]) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
