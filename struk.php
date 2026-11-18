<?php
require 'includes/init.php';
wajib_login();
$r = query($db, 'SELECT t.*, k.plat, k.jenis, k.pemilik, b.nama bbm, b.harga, b.harga_non_subsidi, b.subsidi, u.nama kasir
    FROM transaksi t JOIN kendaraan k ON k.id=t.kendaraan_id JOIN bahan_bakar b ON b.id=t.bbm_id JOIN users u ON u.id=t.user_id WHERE t.id=?', 'i', [(int)($_GET['id'] ?? 0)]);
if (!$r) { flash('Transaksi tidak ditemukan.', 'danger'); redirect('riwayat.php'); }
$t = $r[0];
$judul = 'Struk #' . $t['id'];
$kelasMain = 'container py-4';
require 'includes/header.php';
?>
<div class="receipt-shell">
  <div class="card receipt-card mx-auto">
    <div class="card-body font-monospace small p-3">
      <div class="text-center fw-bold"><?= e(SPBU::NAMA) ?><br><?= e(SPBU::LOKASI) ?></div><hr>
      No. <?= (int)$t['id'] ?> &middot; <?= e($t['tanggal']) ?><br>Kasir : <?= e($t['kasir']) ?><br>
      Kendaraan: <?= e($t['jenis']) ?> <?= e($t['plat']) ?><br>Pemilik : <?= e($t['pemilik']) ?><hr>
      BBM : <?= e($t['bbm']) ?> (<?= rupiah((float)$t['harga']) ?>/L)<br>Tangki awal: <?= e($t['isi_tangki_awal']) ?> L<br>Liter: <?= e($t['liter']) ?> L<br>Tangki akhir: <?= e(round($t['isi_tangki_awal'] + $t['liter'], 2)) ?> L<br>
      <?php if ($t['subsidi']): ?>&nbsp;&nbsp;Subsidi : <?= e($t['liter_subsidi']) ?> x <?= rupiah((float)$t['harga']) ?><br>
      &nbsp;&nbsp;Non-sub : <?= e($t['liter_non_subsidi']) ?> x <?= rupiah((float)$t['harga_non_subsidi']) ?><br><?php endif; ?>
      Bayar : <?= e($t['metode']) ?><hr><div class="fs-6 fw-bold">TOTAL <?= rupiah((float)$t['total']) ?></div>
    </div>
  </div>
  <div class="receipt-actions text-center mt-3">
    <button class="btn btn-outline-primary" type="button" onclick="window.print()">Cetak</button>
    <a class="btn btn-primary" href="transaksi.php">Transaksi baru</a>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
