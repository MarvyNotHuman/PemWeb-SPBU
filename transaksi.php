<?php
require 'includes/init.php';
wajib_login();
$bbmList = query($db, 'SELECT * FROM bahan_bakar ORDER BY id');
$in = ['kendaraan_id' => (int)($_GET['kendaraan_id'] ?? 0) ?: '', 'bbm_id' => '', 'liter' => '', 'isi' => '', 'metode' => 'Tunai'];
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $in['kendaraan_id'] = (int)($_POST['kendaraan_id'] ?? 0);
    $in['bbm_id'] = (int)($_POST['bbm_id'] ?? 0);
    $in['liter'] = filter_var($_POST['liter'] ?? '', FILTER_VALIDATE_FLOAT);
    $in['isi'] = filter_var($_POST['isi_tangki'] ?? '', FILTER_VALIDATE_FLOAT);
    $in['metode'] = in_array($_POST['metode'] ?? '', ['Tunai', 'QRIS'], true) ? $_POST['metode'] : 'Tunai';
    $kr = query($db, 'SELECT * FROM kendaraan WHERE id=?', 'i', [$in['kendaraan_id']]);
    $br = query($db, 'SELECT * FROM bahan_bakar WHERE id=?', 'i', [$in['bbm_id']]);
    $errIsi = ($kr && $in['isi'] !== false) ? SPBU::cekIsiTangki($in['isi'], (float)$kr[0]['kapasitas_tangki']) : null;

    if (!$kr) {
        $galat = 'Pilih kendaraan dari hasil pencarian. Jika belum terdaftar, tambahkan kendaraan baru terlebih dahulu.';
    } elseif (!$br) {
        $galat = 'Pilih bahan bakar yang valid.';
    } elseif ($in['isi'] === false) {
        $galat = 'Isi tangki saat ini wajib diisi (angka, isi 0 jika tangki kosong).';
    } elseif ($errIsi) {
        $galat = $errIsi;
    } elseif ($in['liter'] === false) {
        $galat = 'Jumlah liter harus berupa angka.';
    } else {
        $k = Kendaraan::fromRow($kr[0], $in['isi']);       // objek polimorfik: Mobil atau Motor, dengan isi tangki saat tiba
        $b = BahanBakar::fromRow($br[0]);
        $r = (new SPBU($db))->rincian($k, $b, $in['liter']);
        if (!$r['ok']) {
            $galat = $r['pesan'];
        } else {
            $db->begin_transaction();
            try {
                $total = $k->prosesPengisian($b, $in['liter'], $r['liter_subsidi']);
                $pesan = $k->bayar($total, $in['metode']);
                $uid = user()['id'];
                $st = $db->prepare('INSERT INTO transaksi (kendaraan_id,bbm_id,user_id,isi_tangki_awal,liter,liter_subsidi,liter_non_subsidi,total,metode) VALUES (?,?,?,?,?,?,?,?,?)');
                $st->bind_param('iiiddddds', $in['kendaraan_id'], $in['bbm_id'], $uid, $in['isi'], $in['liter'], $r['liter_subsidi'], $r['liter_non_subsidi'], $total, $in['metode']);
                $st->execute();
                $trxId = $db->insert_id;
                $db->commit();
                flash($pesan);
                redirect("struk.php?id=$trxId");
            } catch (Throwable $e) {
                $db->rollback();
                error_log($e->getMessage());
                $galat = 'Transaksi gagal disimpan. Coba lagi.';
            }
        }
    }
}
$pilih = $in['kendaraan_id'] ? (query($db, 'SELECT * FROM kendaraan WHERE id=?', 'i', [(int)$in['kendaraan_id']])[0] ?? null) : null;
$judul = 'Transaksi';
require 'includes/header.php';
?>
<h3 class="mb-3"><i class="bi bi-cash-coin text-primary me-2"></i>Transaksi Pengisian BBM</h3>
<?php if ($galat): ?><div class="alert alert-danger"><?= e($galat) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-6">
<form id="form-transaksi" method="post" class="card card-body shadow-sm" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3 position-relative"><label class="form-label">Kendaraan</label>
    <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" id="cari-kendaraan" class="form-control" placeholder="Cari plat atau nama pemilik..." autocomplete="off" value="<?= $pilih ? e($pilih['plat'] . ' - ' . $pilih['pemilik']) : '' ?>"></div>
    <input type="hidden" name="kendaraan_id" value="<?= $pilih ? (int)$pilih['id'] : '' ?>">
    <div id="hasil-kendaraan" class="list-group shadow position-absolute w-100" style="z-index:20;max-height:320px;overflow:auto" hidden></div>
    <div class="d-flex justify-content-between flex-wrap small mt-2">
      <span id="kendaraan-terpilih" class="text-muted"><?= $pilih ? e($pilih['jenis'] . ' · tangki maks ' . (float)$pilih['kapasitas_tangki'] . ' L') : '' ?></span>
      <a href="kendaraan_form.php" class="text-decoration-none"><i class="bi bi-plus-circle me-1"></i>Kendaraan belum terdaftar? Tambah baru</a>
    </div></div>
  <div class="mb-3"><label class="form-label">Isi tangki saat ini (liter)</label>
    <input type="number" name="isi_tangki" min="0" <?= $pilih ? 'max="' . (float)$pilih['kapasitas_tangki'] . '"' : '' ?> step="0.01" class="form-control" placeholder="0 jika tangki kosong" value="<?= e($in['isi'] === false ? '' : $in['isi']) ?>" required>
    <div class="form-text">Kondisi tangki saat kendaraan tiba di SPBU. Dicatat pada transaksi ini, bukan di data kendaraan.</div></div>
  <div class="mb-3"><label class="form-label">Bahan bakar</label><select name="bbm_id" class="form-select" required>
    <option value="">-- pilih --</option>
    <?php foreach ($bbmList as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)$in['bbm_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e("{$b['nama']} - " . rupiah((float)$b['harga']) . '/L' . ($b['subsidi'] ? ' (subsidi)' : '')) ?></option><?php endforeach; ?></select></div>
  <div class="mb-3"><label class="form-label">Jumlah liter</label><input type="number" name="liter" min="0.01" step="0.01" class="form-control" value="<?= e($in['liter'] === false ? '' : $in['liter']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Metode pembayaran</label><select name="metode" class="form-select"><?php foreach (['Tunai', 'QRIS'] as $m): ?><option <?= $in['metode'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select></div>
  <button class="btn btn-primary" disabled>Proses &amp; bayar</button>
</form></div>
<div class="col-lg-6"><div class="card shadow-sm"><div class="card-header">Rincian</div>
  <div class="card-body"><div id="preview" class="alert alert-secondary mb-0">Pilih kendaraan, isi tangki saat ini, BBM, dan jumlah liter.</div></div></div></div></div>
<?php $skrip = ['app.js']; require 'includes/footer.php'; ?>
