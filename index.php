<?php
require 'includes/init.php';
wajib_login();
$s = query($db, "SELECT (SELECT COUNT(*) FROM kendaraan) kend,
    (SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal)=CURDATE()) trx,
    (SELECT COALESCE(SUM(liter),0) FROM transaksi WHERE DATE(tanggal)=CURDATE()) ltr,
    (SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE(tanggal)=CURDATE()) omzet")[0];
$terbaru = query($db, 'SELECT t.id, t.tanggal, k.plat, b.nama bbm, t.liter, t.total FROM transaksi t
    JOIN kendaraan k ON k.id=t.kendaraan_id JOIN bahan_bakar b ON b.id=t.bbm_id ORDER BY t.id DESC LIMIT 5');
$bbm = query($db, 'SELECT * FROM bahan_bakar ORDER BY id');
$judul = 'Dashboard';
require 'includes/header.php';
$kartu = [['Kendaraan terdaftar', $s['kend'], 'car-front-fill', 'primary'], ['Transaksi hari ini', $s['trx'], 'receipt', 'success'],
          ['Liter terjual', number_format((float)$s['ltr'], 2, ',', '.') . ' L', 'droplet-fill', 'warning'], ['Omzet hari ini', rupiah((float)$s['omzet']), 'wallet2', 'danger']];
?>
<div class="hero mb-4">
  <span class="badge rounded-pill mb-2"><?= e(SPBU::KODE) ?> &middot; <?= e(SPBU::LOKASI) ?></span>
  <h2 class="fw-bold mb-1">Halo, <?= e(user()['nama']) ?> 👋</h2>
  <p class="opacity-75 mb-3">Siap melayani pengisian BBM hari ini di <?= e(SPBU::NAMA) ?>.</p>
  <a href="transaksi.php" class="btn btn-light text-primary me-2"><i class="bi bi-fuel-pump me-1"></i>Transaksi baru</a>
  <a href="kendaraan_form.php" class="btn btn-outline-light"><i class="bi bi-plus-lg me-1"></i>Kendaraan baru</a>
</div>
<div class="row g-3 mb-4">
<?php foreach ($kartu as [$label, $nilai, $ikon, $warna]): ?>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="ikon bg-<?= $warna ?>-subtle text-<?= $warna ?>-emphasis"><i class="bi bi-<?= $ikon ?>"></i></div><div><small><?= e($label) ?></small><b><?= e($nilai) ?></b></div></div></div>
<?php endforeach; ?>
</div>
<div id="statistik" class="mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Statistik penjualan</h5>
    <div class="d-flex gap-2">
      <div class="btn-group btn-group-sm"><button class="btn btn-outline-primary active" data-hari="7">7 hari</button><button class="btn btn-outline-primary" data-hari="30">30 hari</button><button class="btn btn-outline-primary" data-hari="90">90 hari</button></div>
      <a href="audit.php" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-data me-1"></i>Audit &amp; ekspor</a>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <?php foreach ([['Omzet periode', 'st-omzet', 'wallet2', 'danger'], ['Transaksi', 'st-trx', 'receipt', 'primary'], ['Liter terjual', 'st-liter', 'droplet-fill', 'warning'], ['Rata-rata / transaksi', 'st-rata', 'calculator', 'success']] as [$l, $id, $ik, $w]): ?>
    <div class="col-6 col-lg-3"><div class="card stat"><div class="ikon bg-<?= $w ?>-subtle text-<?= $w ?>-emphasis"><i class="bi bi-<?= $ik ?>"></i></div><div><small><?= $l ?></small><b class="fs-5" id="<?= $id ?>">-</b></div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="alert alert-secondary py-2 small" id="st-kosong" hidden>Belum ada transaksi pada periode ini.</div>
  <div class="row g-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-header">Omzet &amp; liter per hari</div><div class="card-body"><div class="chart-box"><canvas id="g-harian"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header">Liter per bahan bakar</div><div class="card-body"><div class="chart-box"><canvas id="g-bbm"></canvas></div></div></div></div>
    <div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-header">Subsidi vs non-subsidi (liter)</div><div class="card-body"><div class="chart-box kecil"><canvas id="g-subsidi"></canvas></div></div></div></div>
    <div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-header">Omzet per metode bayar</div><div class="card-body"><div class="chart-box kecil"><canvas id="g-metode"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header">5 kendaraan teratas (omzet)</div><div class="table-responsive"><table class="table table-sm mb-0"><tbody id="st-top"></tbody></table></div></div></div>
  </div>
</div>

<h5 class="mb-3">Harga bahan bakar</h5>
<?php foreach ([0 => ['Bensin', 'droplet-fill'], 1 => ['Diesel', 'truck']] as $dsl => [$grup, $ikon]):
    $item = array_filter($bbm, fn($b) => (int)$b['diesel'] === $dsl);
    if (!$item) continue; ?>
<div class="text-muted small fw-semibold text-uppercase mb-2"><i class="bi bi-<?= $ikon ?> me-1"></i><?= $grup ?></div>
<div class="bbm-grid mb-4">
<?php foreach ($item as $b): $sub = (bool)$b['subsidi']; ?>
  <div class="bbm-card <?= $sub ? 'sub' : '' ?>">
    <div class="d-flex justify-content-between align-items-start">
      <span class="bbm-nama"><?= e($b['nama']) ?></span>
      <span class="bbm-mutu"><?= $b['diesel'] ? 'CN' : 'RON' ?> <?= (int)$b['angka_mutu'] ?></span>
    </div>
    <div class="bbm-harga"><?= rupiah((float)$b['harga']) ?><small>/liter</small></div>
    <div class="bbm-foot">
      <?php if ($sub): ?><span class="badge text-bg-warning"><i class="bi bi-patch-check me-1"></i>Subsidi</span>
        <small class="text-muted"><?= $b['kelebihan_ditolak'] ? 'Lebih kuota ditolak' : 'Lebih kuota ' . rupiah((float)$b['harga_non_subsidi']) ?></small>
      <?php else: ?><span class="badge text-bg-light border">Non-subsidi</span><?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<div class="card"><div class="card-header d-flex justify-content-between"><span>Transaksi terbaru</span><a href="riwayat.php" class="small">Lihat semua</a></div>
<div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Waktu</th><th>Plat</th><th>BBM</th><th class="text-end">Liter</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
<?php foreach ($terbaru as $t): ?>
<tr><td><?= e($t['tanggal']) ?></td><td><b><?= e($t['plat']) ?></b></td><td><?= e($t['bbm']) ?></td><td class="text-end"><?= e($t['liter']) ?></td><td class="text-end"><?= rupiah((float)$t['total']) ?></td><td><a href="struk.php?id=<?= (int)$t['id'] ?>"><i class="bi bi-receipt"></i></a></td></tr>
<?php endforeach; if (!$terbaru): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td></tr><?php endif; ?>
</tbody></table></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php $skrip = ['statistik.js']; require 'includes/footer.php'; ?>
