<?php
require 'includes/init.php';
wajib_admin();
$judul = 'Bahan Bakar';
require 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Bahan Bakar</h3>
<button class="btn btn-primary" id="btn-tambah">+ Tambah</button></div>
<div id="notif"></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0 align-middle" id="tabel-bbm">
<thead><tr><th>Nama</th><th>Mutu</th><th class="text-end">Harga/L</th><th>Status</th><th></th></tr></thead><tbody></tbody></table></div></div>

<div class="modal fade" id="modal-bbm" tabindex="-1"><div class="modal-dialog"><form class="modal-content" id="form-bbm">
  <div class="modal-header"><h5 class="modal-title">Data BBM</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body row g-3">
    <input type="hidden" name="id" value="0">
    <div class="col-12"><label class="form-label">Nama</label><input name="nama" class="form-control" required maxlength="40"></div>
    <div class="col-6"><label class="form-label">Harga/liter (Rp)</label><input type="number" name="harga" class="form-control" min="0" required></div>
    <div class="col-6"><label class="form-label">Harga non-subsidi (Rp)</label><input type="number" name="harga_non_subsidi" class="form-control" min="0" required></div>
    <div class="col-6"><label class="form-label">Angka mutu (RON/CN)</label><input type="number" name="angka_mutu" class="form-control" min="1" required></div>
    <div class="col-12">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="diesel" id="c1"><label class="form-check-label" for="c1">Diesel (angka mutu = CN)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="subsidi" id="c2"><label class="form-check-label" for="c2">Bersubsidi</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="kelebihan_ditolak" id="c3"><label class="form-check-label" for="c3">Kelebihan kuota ditolak (jika tidak: dihitung harga non-subsidi)</label></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
</form></div></div>
<?php $skrip = ['app.js']; require 'includes/footer.php'; ?>
