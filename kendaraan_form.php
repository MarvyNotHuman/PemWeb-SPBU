<?php
require 'includes/init.php';
wajib_login();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$d = ['jenis' => 'Mobil', 'plat' => mb_substr(strtoupper(bersih($_GET['plat'] ?? '')), 0, 12), 'pemilik' => '',
      'kapasitas_mesin' => '', 'jumlah_roda' => 4, 'max_penumpang' => 5, 'kapasitas_tangki' => ''];
if ($id) {
    $r = query($db, 'SELECT * FROM kendaraan WHERE id=?', 'i', [$id]);
    if (!$r) { flash('Data tidak ditemukan.', 'danger'); redirect('kendaraan.php'); }
    $d = $r[0];
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    // Sanitasi
    $d['jenis'] = $_POST['jenis'] ?? '';
    $d['plat'] = strtoupper(preg_replace('/\s+/', ' ', bersih($_POST['plat'] ?? '')));
    $d['pemilik'] = bersih($_POST['pemilik'] ?? '');
    foreach (['kapasitas_mesin', 'jumlah_roda', 'max_penumpang'] as $k) $d[$k] = filter_var($_POST[$k] ?? '', FILTER_VALIDATE_INT);
    $d['kapasitas_tangki'] = filter_var($_POST['kapasitas_tangki'] ?? '', FILTER_VALIDATE_FLOAT);
    // Validasi
    if (!in_array($d['jenis'], ['Mobil', 'Motor'], true)) $err['jenis'] = 'Jenis tidak valid.';
    if (!preg_match('/^[A-Z]{1,2} ?\d{1,4} ?[A-Z]{0,3}$/', $d['plat'])) $err['plat'] = 'Format plat tidak valid (contoh: R 1234 AB).';
    if (mb_strlen($d['pemilik']) < 2 || mb_strlen($d['pemilik']) > 60) $err['pemilik'] = 'Nama pemilik 2-60 karakter.';
    if ($d['kapasitas_mesin'] === false || $d['kapasitas_mesin'] < 1) $err['kapasitas_mesin'] = 'Kapasitas mesin harus bilangan bulat > 0.';
    if ($d['jenis'] === 'Motor') { $d['jumlah_roda'] = 2; $d['max_penumpang'] = 2; }
    elseif ($d['jumlah_roda'] === false || !Mobil::rodaValid($d['jumlah_roda'])) $err['jumlah_roda'] = 'Jumlah roda mobil: 4, atau 6 ke atas.';
    if ($d['max_penumpang'] === false || $d['max_penumpang'] < 1) $err['max_penumpang'] = 'Penumpang harus > 0.';
    if ($d['kapasitas_tangki'] === false || $d['kapasitas_tangki'] <= 0 || $d['kapasitas_tangki'] > 9999) $err['kapasitas_tangki'] = 'Kapasitas tangki harus > 0.';

    if (!$err) {
        try {
            $p = [$d['jenis'], $d['plat'], $d['pemilik'], $d['kapasitas_mesin'], $d['jumlah_roda'], $d['max_penumpang'], $d['kapasitas_tangki']];
            if ($id) {
                $st = $db->prepare('UPDATE kendaraan SET jenis=?,plat=?,pemilik=?,kapasitas_mesin=?,jumlah_roda=?,max_penumpang=?,kapasitas_tangki=? WHERE id=?');
                $p[] = $id;
                $st->bind_param('sssiiidi', ...$p);
            } else {
                $st = $db->prepare('INSERT INTO kendaraan (jenis,plat,pemilik,kapasitas_mesin,jumlah_roda,max_penumpang,kapasitas_tangki) VALUES (?,?,?,?,?,?,?)');
                $st->bind_param('sssiiid', ...$p);
            }
            $st->execute();
            $kid = $id ?: $db->insert_id;
            flash($id ? 'Kendaraan diperbarui.' : 'Kendaraan ditambahkan.');
            redirect(isset($_POST['lanjut']) ? "transaksi.php?kendaraan_id=$kid" : 'kendaraan.php');
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) $err['plat'] = 'Plat nomor sudah terdaftar.';
            else { error_log($e->getMessage()); $err['umum'] = 'Terjadi kesalahan database.'; }
        }
    }
}
$judul = $id ? 'Ubah Kendaraan' : 'Kendaraan Baru';
require 'includes/header.php';
$bbmCfg = query($db, 'SELECT nama,diesel,subsidi FROM bahan_bakar ORDER BY id');
$cfg = ['bbm' => array_map(fn($b) => ['nama' => $b['nama'], 'diesel' => (bool)$b['diesel'], 'subsidi' => (bool)$b['subsidi']], $bbmCfg),
        'ccMobil' => Mobil::BATAS_CC_BBM_SUBSIDI, 'ccMotor' => Motor::BATAS_CC_BBM_SUBSIDI,
        'pertalite' => Mobil::BATAS_PERTALITE, 'solar4' => Mobil::BATAS_SOLAR_RODA_4, 'solar6' => Mobil::BATAS_SOLAR_RODA_6_KE_ATAS];
function fld(string $nama, string $label, array $d, array $err, string $tipe = 'text', string $extra = '', string $hint = '', string $kolom = 'col-md-6'): void {
    echo "<div class=\"$kolom\"><label class=\"form-label\" for=\"$nama\">" . e($label) . "</label><input id=\"$nama\" name=\"$nama\" type=\"$tipe\" class=\"form-control"
        . (isset($err[$nama]) ? ' is-invalid' : '') . '" value="' . e($d[$nama]) . "\" required $extra><div class=\"invalid-feedback\">" . e($err[$nama] ?? 'Wajib diisi dengan benar.') . '</div>'
        . ($hint ? '<div class="form-text">' . e($hint) . '</div>' : '') . '</div>';
}
?>
<div class="mb-3"><a href="kendaraan.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Data kendaraan</a>
<h3 class="mb-0"><i class="bi bi-car-front-fill text-primary me-2"></i><?= e($judul) ?></h3></div>
<?php if (isset($err['umum'])): ?><div class="alert alert-danger"><?= e($err['umum']) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-7">
<div class="card"><div class="card-body p-4">
<ol class="stepper"><li><span>1</span>Jenis &amp; pemilik</li><li><span>2</span>Spesifikasi</li></ol>
<form id="form-kendaraan" method="post" novalidate data-cfg="<?= e(json_encode($cfg)) ?>">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">

  <section class="step row g-3">
    <div class="col-12"><label class="form-label">Jenis kendaraan</label>
      <div class="row g-3 pilih-jenis">
      <?php foreach (['Mobil' => 'car-front-fill', 'Motor' => 'bicycle'] as $j => $ik): ?>
        <div class="col-6"><input type="radio" name="jenis" id="j-<?= $j ?>" value="<?= $j ?>" <?= $d['jenis'] === $j ? 'checked' : '' ?>><label for="j-<?= $j ?>"><i class="bi bi-<?= $ik ?>"></i><b><?= $j ?></b></label></div>
      <?php endforeach; ?></div>
      <?php if (isset($err['jenis'])): ?><div class="text-danger small mt-1"><?= e($err['jenis']) ?></div><?php endif; ?></div>
    <?php fld('pemilik', 'Nama pemilik', $d, $err, 'text', 'maxlength="60"'); fld('plat', 'Nomor plat', $d, $err, 'text', 'maxlength="12" placeholder="R 1234 AB" style="text-transform:uppercase"'); ?>
  </section>

  <section class="step row g-3">
    <?php fld('kapasitas_mesin', 'Kapasitas mesin (cc)', $d, $err, 'number', 'min="1"');
          fld('kapasitas_tangki', 'Kapasitas tangki maksimum (liter)', $d, $err, 'number', 'min="0.1" step="0.01"', 'Isi tangki saat ini tidak disimpan di sini; diinput pada setiap transaksi.');
          fld('jumlah_roda', 'Jumlah roda', $d, $err, 'number', 'min="2"', 'Mobil: 4, atau 6 ke atas. Motor tetap 2.');
          fld('max_penumpang', 'Maks. penumpang (orang)', $d, $err, 'number', 'min="1"', 'Motor ditetapkan 2 orang.'); ?>
  </section>

  <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
    <button type="button" id="btn-prev" class="btn btn-outline-secondary" hidden><i class="bi bi-arrow-left"></i> Kembali</button>
    <span class="ms-auto d-flex gap-2">
      <button type="button" id="btn-next" class="btn btn-primary" hidden>Lanjut <i class="bi bi-arrow-right"></i></button>
      <span id="btn-simpan" class="d-flex gap-2"><button class="btn btn-outline-primary"><i class="bi bi-check2 me-1"></i>Simpan</button>
      <button class="btn btn-merah" name="lanjut" value="1"><i class="bi bi-fuel-pump me-1"></i>Simpan &amp; isi BBM</button></span>
    </span>
  </div>
</form></div></div></div>

<div class="col-lg-5"><div class="sticky-side"><div class="card"><div class="card-body p-4">
  <div class="text-muted small mb-2">Kartu kendaraan (pratinjau)</div>
  <div class="d-flex align-items-center gap-3 mb-3"><span class="plat" id="pv-plat">R 0000 XX</span><div><b id="pv-pemilik">Nama pemilik</b><div class="small text-muted" id="pv-jenis">Mobil</div></div></div>
  <ul class="spek mb-3" id="pv-spek"></ul>
  <div class="fw-semibold small mb-2 mt-3">Kelayakan BBM &amp; kuota subsidi per hari</div>
  <div id="pv-bbm" class="small"></div>
</div></div></div></div></div>
<?php $skrip = ['app.js']; require 'includes/footer.php'; ?>
