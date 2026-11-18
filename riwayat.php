<?php
require 'includes/init.php';
wajib_login();
$q = bersih($_GET['q'] ?? '');
$like = "%$q%";
$per = 10; $hal = halaman_aktif(); $off = ($hal - 1) * $per;
$dari = 'FROM transaksi t JOIN kendaraan k ON k.id=t.kendaraan_id JOIN bahan_bakar b ON b.id=t.bbm_id
         WHERE k.plat LIKE ? OR k.pemilik LIKE ? OR b.nama LIKE ?';
$total = (int)query($db, "SELECT COUNT(*) n $dari", 'sss', [$like, $like, $like])[0]['n'];
$rows = query($db, "SELECT t.id,t.tanggal,t.liter,t.total,t.metode,k.plat,k.pemilik,b.nama bbm $dari ORDER BY t.id DESC LIMIT ? OFFSET ?", 'sssii', [$like, $like, $like, $per, $off]);
$judul = 'Riwayat';
require 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Transaksi</h3>
<?php if (user()['role'] === 'admin' && $total): ?>
<form method="post" action="riwayat_hapus.php" onsubmit="return confirm('Hapus SEMUA riwayat transaksi secara permanen? Kuota subsidi hari ini juga akan kembali penuh.')"><?= csrf_field() ?><input type="hidden" name="aksi" value="semua"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3 me-1"></i>Kosongkan riwayat</button></form>
<?php endif; ?></div>
<form class="input-group mb-3" method="get"><input name="q" class="form-control" placeholder="Cari plat, pemilik, atau BBM..." value="<?= e($q) ?>"><button class="btn btn-outline-primary">Cari</button></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Waktu</th><th>Plat</th><th>Pemilik</th><th>BBM</th><th class="text-end">Liter</th><th class="text-end">Total</th><th>Bayar</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= e($r['tanggal']) ?></td><td><?= e($r['plat']) ?></td><td><?= e($r['pemilik']) ?></td><td><?= e($r['bbm']) ?></td><td class="text-end"><?= e($r['liter']) ?></td><td class="text-end"><?= rupiah((float)$r['total']) ?></td><td><?= e($r['metode']) ?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="struk.php?id=<?= (int)$r['id'] ?>" title="Struk"><i class="bi bi-receipt"></i></a>
<?php if (user()['role'] === 'admin'): ?><form method="post" action="riwayat_hapus.php" class="d-inline" onsubmit="return confirm('Hapus transaksi #<?= (int)$r['id'] ?> secara permanen?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="mt-3"><?= paginasi($total, $per, $hal, ['q' => $q]) ?></div>
<?php require 'includes/footer.php'; ?>
