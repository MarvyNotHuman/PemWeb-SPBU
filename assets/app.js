const $ = (s, r = document) => r.querySelector(s);
const ang = n => Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
const rp = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const formTrx = $('#form-transaksi');
if (formTrx) {
  const box = $('#preview'), btn = $('button', formTrx);
  let timer;
  const tampil = (kelas, html) => { box.className = 'alert alert-' + kelas + ' mb-0'; box.innerHTML = html; };

  async function hitung() {
    const kid = formTrx.kendaraan_id.value, bid = formTrx.bbm_id.value, liter = parseFloat(formTrx.liter.value), isi = parseFloat(formTrx.isi_tangki.value);
    if (!kid || !bid || !(liter > 0) || isNaN(isi)) {
      btn.disabled = true;
      return tampil('secondary', 'Pilih kendaraan, isi tangki saat ini, BBM, dan jumlah liter.');
    }
    try {
      const res = await fetch('api/hitung.php?' + new URLSearchParams({ kendaraan_id: kid, bbm_id: bid, liter, isi }));
      const j = await res.json();
      btn.disabled = !j.ok;
      if (!j.ok) return tampil('danger', '⚠ ' + esc(j.pesan));
      tampil('success',
        `Liter subsidi: <b>${j.liter_subsidi}</b> &middot; Liter non-subsidi: <b>${j.liter_non_subsidi}</b><br>` +
        `Sisa kuota subsidi hari ini: <b>${j.sisa_subsidi === null ? 'tanpa batas' : j.sisa_subsidi + ' L'}</b><br>` +
        `Sisa kapasitas tangki: <b>${ang(j.sisa_tangki)} L</b> &middot; Isi setelah pengisian: <b>${ang(j.isi_setelah)} / ${ang(j.kapasitas)} L</b><hr class="my-2"><span class="fs-5">Total: <b>${rp(j.total)}</b></span>`);
    } catch {
      btn.disabled = true;
      tampil('warning', 'Gagal menghubungi server.');
    }
  }
  formTrx.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(hitung, 300); });
  formTrx.addEventListener('change', hitung);
  formTrx.addEventListener('submit', e => { if (btn.disabled) e.preventDefault(); });
  hitung();
}

const cari = $('#cari-kendaraan');
if (cari && formTrx) {
  const hid = formTrx.kendaraan_id, daftar = $('#hasil-kendaraan'), info = $('#kendaraan-terpilih');
  let timer, items = [], idx = -1, urut = 0;
  const ubah = () => hid.dispatchEvent(new Event('change', { bubbles: true }));

  function gambar() {
    daftar.innerHTML = items.length
      ? items.map((k, i) => `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center${i === idx ? ' active' : ''}" data-i="${i}">` +
          `<span><b>${esc(k.plat)}</b> <span class="small">${esc(k.pemilik)}</span></span>` +
          `<span class="small">${esc(k.jenis)} &middot; tangki maks ${ang(k.kapasitas)} L</span></button>`).join('')
      : `<div class="list-group-item"><div class="text-muted mb-2">Kendaraan tidak ditemukan.</div>` +
        `<a class="btn btn-sm btn-primary" href="kendaraan_form.php?plat=${encodeURIComponent(cari.value.trim())}"><i class="bi bi-plus-lg me-1"></i>Tambah kendaraan baru</a></div>`;
    daftar.hidden = false;
  }
  async function muat() {
    const saya = ++urut;
    try {
      const j = await (await fetch('api/cari_kendaraan.php?q=' + encodeURIComponent(cari.value.trim()))).json();
      if (saya !== urut) return;                       // abaikan respon lama
      items = j.data; idx = -1; gambar();
    } catch { daftar.hidden = true; }
  }
  function pilih(k) {
    hid.value = k.id;
    cari.value = `${k.plat} - ${k.pemilik}`;
    info.textContent = `${k.jenis} · tangki maks ${ang(k.kapasitas)} L`;
    formTrx.isi_tangki.max = k.kapasitas;
    daftar.hidden = true;
    ubah();
    formTrx.isi_tangki.focus();
  }
  cari.addEventListener('input', () => {
    hid.value = ''; info.textContent = ''; formTrx.isi_tangki.removeAttribute('max'); ubah();     // ketik ulang = batalkan pilihan
    clearTimeout(timer); timer = setTimeout(muat, 250);
  });
  cari.addEventListener('focus', () => { cari.select(); if (!hid.value) muat(); });
  cari.addEventListener('keydown', e => {
    if (daftar.hidden || !items.length) return;
    if (e.key === 'ArrowDown') { idx = (idx + 1) % items.length; gambar(); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { idx = (idx - 1 + items.length) % items.length; gambar(); e.preventDefault(); }
    else if (e.key === 'Enter' && idx >= 0) { pilih(items[idx]); e.preventDefault(); }
    else if (e.key === 'Escape') daftar.hidden = true;
  });
  daftar.addEventListener('mousedown', e => {
    const b = e.target.closest('[data-i]');
    if (b) { e.preventDefault(); pilih(items[b.dataset.i]); }
  });
  document.addEventListener('click', e => { if (!e.target.closest('#cari-kendaraan, #hasil-kendaraan')) daftar.hidden = true; });
}

// ---------- Form kendaraan: wizard 3 langkah + pratinjau (aturan sama dengan Mobil.java / Motor.java) ----------
const formKend = $('#form-kendaraan');
if (formKend) {
  const f = formKend.elements, cfg = JSON.parse(formKend.dataset.cfg);
  const steps = [...formKend.querySelectorAll('.step')], dots = [...document.querySelectorAll('.stepper li')];
  const jenis = () => formKend.querySelector('[name=jenis]:checked')?.value || 'Mobil';
  const num = el => parseFloat(el.value) || 0;
  let cur = 0;
  formKend.classList.add('wizard');

  function tampilStep(n) {
    cur = n;
    steps.forEach((s, i) => s.hidden = i !== n);
    dots.forEach((d, i) => { d.classList.toggle('aktif', i === n); d.classList.toggle('selesai', i < n); });
    $('#btn-prev').hidden = n === 0;
    $('#btn-next').hidden = n === steps.length - 1;
    $('#btn-simpan').style.display = n === steps.length - 1 ? '' : 'none';
  }
  function aturJenis() {                                     // Motor: roda & penumpang tetap 2
    const motor = jenis() === 'Motor';
    if (motor) { f.jumlah_roda.value = 2; f.max_penumpang.value = 2; }
    else { if (num(f.jumlah_roda) === 2) f.jumlah_roda.value = 4; if (num(f.max_penumpang) === 2) f.max_penumpang.value = 5; }
    f.jumlah_roda.readOnly = f.max_penumpang.readOnly = motor;
  }
  function validasi() {
    const r = num(f.jumlah_roda);
    f.jumlah_roda.setCustomValidity(jenis() === 'Mobil' && !(r === 4 || r >= 6) ? 'Roda mobil: 4 atau 6 ke atas' : '');
    f.kapasitas_tangki.setCustomValidity(num(f.kapasitas_tangki) > 0 ? '' : 'Kapasitas harus lebih dari 0');
    f.plat.setCustomValidity(/^[A-Za-z]{1,2} ?\d{1,4} ?[A-Za-z]{0,3}$/.test(f.plat.value.trim()) ? '' : 'Format plat: R 1234 AB');
  }
  function status(b) {                                       // kelayakan + batas subsidi per hari
    const motor = jenis() === 'Motor', cc = num(f.kapasitas_mesin), roda = num(f.jumlah_roda);
    if (motor && b.diesel) return [false, 'Motor tidak boleh memakai diesel'];
    if (b.subsidi && !b.diesel && cc > (motor ? cfg.ccMotor : cfg.ccMobil)) return [false, `Di atas ${motor ? cfg.ccMotor : cfg.ccMobil} cc`];
    if (!b.subsidi || motor) return [true, 'Tanpa batas'];
    return [true, 'Kuota ' + (!b.diesel ? cfg.pertalite : roda >= 6 ? cfg.solar6 : cfg.solar4) + ' L/hari'];
  }
  function render() {
    const motor = jenis() === 'Motor', kap = num(f.kapasitas_tangki);
    $('#pv-plat').textContent = f.plat.value.trim().toUpperCase() || 'R 0000 XX';
    $('#pv-pemilik').textContent = f.pemilik.value.trim() || 'Nama pemilik';
    $('#pv-jenis').textContent = jenis();
    $('#pv-spek').innerHTML = [
      ['Kapasitas mesin', (num(f.kapasitas_mesin) || '-') + ' cc'],
      ['Jumlah roda', (num(f.jumlah_roda) || '-') + (motor ? ' (tetap)' : '')],
      ['Maks. penumpang', (num(f.max_penumpang) || '-') + ' orang' + (motor ? ' (tetap)' : '')],
      ['Kapasitas tangki maks.', (kap || '-') + ' liter'],
    ].map(([k, v]) => `<li><span>${k}</span><b>${esc(v)}</b></li>`).join('');
    $('#pv-bbm').innerHTML = cfg.bbm.map(b => {
      const [ok, ket] = status(b);
      return `<div class="d-flex justify-content-between align-items-center py-1 border-bottom"><span>${esc(b.nama)}${b.subsidi ? ' <span class="badge text-bg-warning">subsidi</span>' : ''}</span>` +
        `<span class="${ok ? 'text-success' : 'text-danger'}"><i class="bi bi-${ok ? 'check-circle' : 'x-circle'}"></i> ${esc(ket)}</span></div>`;
    }).join('');
  }
  function lanjut() {                                        // validasi per langkah
    validasi();
    const invalid = [...steps[cur].querySelectorAll('input,select')].find(el => !el.checkValidity());
    if (invalid) { formKend.classList.add('was-validated'); invalid.reportValidity(); return false; }
    return true;
  }
  $('#btn-next').addEventListener('click', () => { if (lanjut()) tampilStep(cur + 1); });
  $('#btn-prev').addEventListener('click', () => tampilStep(cur - 1));
  formKend.addEventListener('change', e => { if (e.target.name === 'jenis') aturJenis(); validasi(); render(); });
  formKend.addEventListener('input', () => { validasi(); render(); });
  formKend.addEventListener('keydown', e => { if (e.key === 'Enter' && cur < steps.length - 1) { e.preventDefault(); $('#btn-next').click(); } });
  formKend.addEventListener('submit', e => {
    validasi();
    if (!formKend.checkValidity()) {
      e.preventDefault(); formKend.classList.add('was-validated');
      const i = steps.findIndex(s => [...s.querySelectorAll('input,select')].some(el => !el.checkValidity()));
      tampilStep(Math.max(0, i)); [...steps[Math.max(0, i)].querySelectorAll('input')].find(el => !el.checkValidity())?.reportValidity();
    }
  });
  // jika server mengembalikan error, buka langkah yang berisi field bermasalah
  const awal = steps.findIndex(s => s.querySelector('.is-invalid'));
  aturJenis(); validasi(); render(); tampilStep(awal > 0 ? awal : 0);
}

const tbody = $('#tabel-bbm tbody');
if (tbody) {
  const csrf = $('meta[name=csrf]').content;
  const modal = new bootstrap.Modal('#modal-bbm');
  const form = $('#form-bbm');
  let data = [];

  const notif = (pesan, ok) => {
    $('#notif').innerHTML = `<div class="alert alert-${ok ? 'success' : 'danger'} alert-dismissible">${esc(pesan)}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
  };
  async function api(action, isi = {}) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(isi).forEach(([k, v]) => fd.append(k, v));
    try {
      const res = await fetch('api/bbm.php', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd });
      return await res.json();
    } catch {
      return { ok: false, pesan: 'Permintaan gagal. Muat ulang halaman.' };
    }
  }
  async function muat() {
    const j = await api('list');
    if (!j.ok) return notif(j.pesan, false);
    data = j.data;
    tbody.innerHTML = data.map(b => `<tr>
      <td>${esc(b.nama)}</td><td>${b.diesel == 1 ? 'CN' : 'RON'} ${esc(b.angka_mutu)}</td>
      <td class="text-end">${rp(+b.harga)}</td>
      <td>${b.subsidi == 1 ? `<span class="badge text-bg-warning">Subsidi${b.kelebihan_ditolak == 1 ? ' (lebih kuota ditolak)' : ' (lebih kuota ' + rp(+b.harga_non_subsidi) + ')'}</span>` : '<span class="badge text-bg-secondary">Non-subsidi</span>'}</td>
      <td class="text-end text-nowrap"><button class="btn btn-sm btn-outline-secondary" data-ubah="${b.id}">Ubah</button>
      <button class="btn btn-sm btn-outline-danger" data-hapus="${b.id}">Hapus</button></td></tr>`).join('')
      || '<tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>';
  }
  const isiForm = b => {
    form.reset();
    form.elements.id.value = b ? b.id : 0;
    ['nama', 'harga', 'harga_non_subsidi', 'angka_mutu'].forEach(k => form[k].value = b ? b[k] : '');
    ['diesel', 'subsidi', 'kelebihan_ditolak'].forEach(k => form[k].checked = !!b && b[k] == 1);
    modal.show();
  };
  $('#btn-tambah').addEventListener('click', () => isiForm(null));
  tbody.addEventListener('click', async e => {
    const u = e.target.dataset.ubah, h = e.target.dataset.hapus;
    if (u) isiForm(data.find(b => b.id == u));
    if (h && confirm('Hapus BBM ini?')) { const j = await api('hapus', { id: h }); notif(j.pesan, j.ok); muat(); }
  });
  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!form.checkValidity()) return form.reportValidity();
    const j = await api('simpan', Object.fromEntries(new FormData(form)));
    notif(j.pesan, j.ok);
    if (j.ok) { modal.hide(); muat(); }
  });
  muat();
}

// ---------- Audit: rentang tanggal cepat ----------
const formAudit = $('#form-audit');
if (formAudit) {
  const fmt = d => d.toLocaleDateString('sv-SE');                 // yyyy-mm-dd
  formAudit.querySelectorAll('[data-rentang]').forEach(b => b.addEventListener('click', () => {
    const n = new Date();
    let a = new Date(n), z = new Date(n);
    switch (b.dataset.rentang) {
      case '7': a.setDate(n.getDate() - 6); break;
      case '30': a.setDate(n.getDate() - 29); break;
      case 'bulan': a = new Date(n.getFullYear(), n.getMonth(), 1); break;
      case 'lalu': a = new Date(n.getFullYear(), n.getMonth() - 1, 1); z = new Date(n.getFullYear(), n.getMonth(), 0); break;
    }
    formAudit.dari.value = fmt(a); formAudit.sampai.value = fmt(z);
    formAudit.requestSubmit();
  }));
}
