<?php
require 'includes/init.php';
wajib_login();
$q = bersih($_GET['q'] ?? '');
$like = "%$q%";
$per = 5; $hal = halaman_aktif(); $off = ($hal - 1) * $per;
$total = (int)query($db, 'SELECT COUNT(*) n FROM kendaraan WHERE plat LIKE ? OR pemilik LIKE ?', 'ss', [$like, $like])[0]['n'];
$rows = query($db, 'SELECT * FROM kendaraan WHERE plat LIKE ? OR pemilik LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?', 'ssii', [$like, $like, $per, $off]);
$spbu = new SPBU($db);
$bbmSub = array_map([BahanBakar::class, 'fromRow'], query($db, 'SELECT * FROM bahan_bakar WHERE subsidi=1 ORDER BY id'));
$terpakai = $spbu->terpakaiHariIni(array_column($rows, 'id'));
$judul = 'Kendaraan';
require 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Data Kendaraan</h3><a href="kendaraan_form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Kendaraan baru</a></div>
<form class="input-group mb-3" method="get"><input name="q" class="form-control" placeholder="Cari plat atau pemilik..." value="<?= e($q) ?>"><button class="btn btn-outline-primary">Cari</button></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
<thead><tr><th>Plat</th><th>Jenis</th><th>Pemilik</th><th>Spesifikasi</th><th>Subsidi hari ini (sisa / kuota)</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $kObj = Kendaraan::fromRow($r); ?>
<tr><td><span class="plat fs-6 px-2" style="font-size:1rem!important"><?= e($r['plat']) ?></span></td>
<td><span class="badge text-bg-<?= $r['jenis'] === 'Mobil' ? 'primary' : 'warning' ?>"><i class="bi bi-<?= $r['jenis'] === 'Mobil' ? 'car-front-fill' : 'bicycle' ?> me-1"></i><?= e($r['jenis']) ?></span></td>
<td><?= e($r['pemilik']) ?></td><td><?= (int)$r['kapasitas_mesin'] ?> cc &middot; <?= (int)$r['jumlah_roda'] ?> roda</td>
<td style="min-width:210px">
<?php $adaSub = false; foreach ($bbmSub as $b):
    if (!$kObj->bolehMemakai($b)) continue;
    $adaSub = true; $batas = $kObj->batasSubsidiPerHari($b); $pakai = $terpakai[(int)$r['id']][$b->id] ?? 0; ?>
  <?php if (is_infinite($batas)): ?>
  <div class="small d-flex justify-content-between"><b><?= e($b->nama) ?></b><span class="text-success">tanpa batas</span></div>
  <?php else: $sisa = max(0, $batas - $pakai); ?>
  <div class="small d-flex justify-content-between"><b><?= e($b->nama) ?></b><span class="<?= $sisa <= 0 ? 'text-danger' : '' ?>">sisa <?= rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') ?> / <?= (int)$batas ?> L</span></div>
  <div class="gauge mb-1 <?= $sisa <= 0 ? 'penuh' : '' ?>" style="height:.4rem"><div style="width:<?= $sisa <= 0 ? 100 : round($sisa / $batas * 100) ?>%"></div></div>
  <?php endif; endforeach; if (!$adaSub): ?><span class="small text-muted">Tidak berhak BBM subsidi</span><?php endif; ?>
</td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-primary" href="transaksi.php?kendaraan_id=<?= (int)$r['id'] ?>" title="Isi BBM"><i class="bi bi-fuel-pump"></i></a>
<a class="btn btn-sm btn-outline-secondary" href="kendaraan_form.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
<?php if (user()['role'] === 'admin'): ?>
<form method="post" action="kendaraan_hapus.php" class="d-inline" onsubmit="return confirm('Hapus kendaraan ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
<?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="mt-3"><?= paginasi($total, $per, $hal, ['q' => $q]) ?></div>
<?php require 'includes/footer.php'; ?>
