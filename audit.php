<?php
require 'includes/init.php';
wajib_login();
require_once 'includes/laporan.php';
[$f, $where, $tp, $p] = audit_filter($db, $_GET);
$ring = audit_ringkas($db, $where, $tp, $p);
$rekap = audit_rekap_bbm($db, $where, $tp, $p);
$per = 20; $hal = halaman_aktif(); $off = ($hal - 1) * $per;
$rows = audit_baris($db, $where, $tp, $p, $per, $off);
$bbmList = query($db, 'SELECT id, nama FROM bahan_bakar ORDER BY id');
$kasirList = query($db, 'SELECT id, nama FROM users ORDER BY nama');
$metodeList = array_values(array_unique(array_merge(['Tunai', 'QRIS'], array_column(query($db, 'SELECT DISTINCT metode FROM transaksi'), 'metode'))));
$ekspor = fn(string $fmt) => 'audit_ekspor.php?' . http_build_query($f + ['format' => $fmt]);
$judul = 'Audit Transaksi';
require 'includes/header.php';
$kartu = [['Transaksi', number_format($ring['trx'], 0, ',', '.'), 'receipt', 'primary'],
          ['Total liter', number_format($ring['liter'], 2, ',', '.') . ' L', 'droplet-fill', 'warning'],
          ['Subsidi / non-subsidi', number_format($ring['sub'], 2, ',', '.') . ' / ' . number_format($ring['non'], 2, ',', '.') . ' L', 'patch-check', 'success'],
          ['Total omzet', rupiah($ring['omzet']), 'wallet2', 'danger']];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h3 class="mb-0"><i class="bi bi-clipboard-data text-primary me-2"></i>Audit Transaksi</h3>
  <div class="btn-group">
    <a class="btn btn-outline-success" href="<?= e($ekspor('xlsx')) ?>"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
    <a class="btn btn-outline-secondary" href="<?= e($ekspor('csv')) ?>"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
    <a class="btn btn-outline-danger" href="<?= e($ekspor('pdf')) ?>"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
  </div>
</div>

<form id="form-audit" method="get" class="card card-body mb-4">
  <div class="row g-3 align-items-end">
    <div class="col-6 col-md-3"><label class="form-label">Dari tanggal</label><input type="date" name="dari" class="form-control" value="<?= e($f['dari']) ?>" required></div>
    <div class="col-6 col-md-3"><label class="form-label">Sampai tanggal</label><input type="date" name="sampai" class="form-control" value="<?= e($f['sampai']) ?>" required></div>
    <div class="col-12 col-md-6"><label class="form-label d-block">Rentang cepat</label>
      <div class="btn-group btn-group-sm flex-wrap">
        <?php foreach (['1' => 'Hari ini', '7' => '7 hari', '30' => '30 hari', 'bulan' => 'Bulan ini', 'lalu' => 'Bulan lalu'] as $k => $l): ?>
          <button type="button" class="btn btn-outline-primary" data-rentang="<?= $k ?>"><?= $l ?></button><?php endforeach; ?>
      </div></div>
    <div class="col-6 col-md-3"><label class="form-label">Bahan bakar</label><select name="bbm" class="form-select"><option value="0">Semua</option>
      <?php foreach ($bbmList as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $f['bbm'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['nama']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label">Metode</label><select name="metode" class="form-select"><option value="">Semua</option>
      <?php foreach ($metodeList as $m): ?><option <?= $f['metode'] === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label">Kasir</label><select name="kasir" class="form-select"><option value="0">Semua</option>
      <?php foreach ($kasirList as $k): ?><option value="<?= (int)$k['id'] ?>" <?= $f['kasir'] === (int)$k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label">Plat / pemilik</label><input name="q" class="form-control" value="<?= e($f['q']) ?>" placeholder="Cari..."></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button><a href="audit.php" class="btn btn-outline-secondary">Reset</a></div>
  </div>
</form>

<div class="text-muted small mb-2">Periode <b><?= tgl_id($f['dari']) ?></b> s.d. <b><?= tgl_id($f['sampai']) ?></b></div>
<div class="row g-3 mb-4">
<?php foreach ($kartu as [$label, $nilai, $ikon, $warna]): ?>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="ikon bg-<?= $warna ?>-subtle text-<?= $warna ?>-emphasis"><i class="bi bi-<?= $ikon ?>"></i></div><div><small><?= e($label) ?></small><b class="fs-5"><?= e($nilai) ?></b></div></div></div>
<?php endforeach; ?>
</div>

<?php if ($rekap): ?>
<div class="card mb-4"><div class="card-header">Rekap per bahan bakar</div><div class="table-responsive"><table class="table mb-0">
  <thead><tr><th>BBM</th><th class="text-end">Transaksi</th><th class="text-end">Liter</th><th class="text-end">Omzet</th></tr></thead><tbody>
  <?php foreach ($rekap as $r): ?><tr><td><?= e($r['nama']) ?></td><td class="text-end"><?= (int)$r['trx'] ?></td><td class="text-end"><?= number_format((float)$r['liter'], 2, ',', '.') ?></td><td class="text-end"><?= rupiah((float)$r['omzet']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>

<div class="card"><div class="card-header">Rincian transaksi</div><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
<thead><tr><th>#</th><th>Waktu</th><th>Plat</th><th>Pemilik</th><th>BBM</th><th class="text-end">Tangki awal</th><th class="text-end">Liter</th><th class="text-end">Subsidi</th><th class="text-end">Non-sub</th><th class="text-end">Total</th><th>Metode</th><th>Kasir</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td class="text-nowrap"><?= e($r['tanggal']) ?></td><td><b><?= e($r['plat']) ?></b></td><td><?= e($r['pemilik']) ?></td><td><?= e($r['bbm']) ?></td>
<td class="text-end"><?= e($r['isi_tangki_awal']) ?></td><td class="text-end"><?= e($r['liter']) ?></td><td class="text-end"><?= e($r['liter_subsidi']) ?></td><td class="text-end"><?= e($r['liter_non_subsidi']) ?></td>
<td class="text-end"><?= rupiah((float)$r['total']) ?></td><td><?= e($r['metode']) ?></td><td><?= e($r['kasir']) ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="struk.php?id=<?= (int)$r['id'] ?>" title="Struk"><i class="bi bi-receipt"></i></a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="13" class="text-center text-muted py-4">Tidak ada transaksi pada filter ini.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="mt-3"><?= paginasi($ring['trx'], $per, $hal, $f) ?></div>
<?php $skrip = ['app.js']; require 'includes/footer.php'; ?>
