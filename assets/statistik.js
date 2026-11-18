(() => {
  const wadah = document.getElementById('statistik');
  if (!wadah || typeof Chart === 'undefined') return;
  const rp = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
  const ang = (n, d = 2) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: d });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const warna = ['#0a3d91', '#e4002b', '#ffb81c', '#1fa971', '#7a5cff', '#00a3c4', '#ff7a45'];
  const grafik = {};
  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

  function gambar(id, konfig) {
    grafik[id]?.destroy();
    grafik[id] = new Chart(document.getElementById(id), konfig);
  }
  const donat = (id, label, data, fmt) => gambar(id, {
    type: 'doughnut',
    data: { labels: label, datasets: [{ data, backgroundColor: warna, borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }, tooltip: { callbacks: { label: c => ` ${c.label}: ${fmt(c.parsed)}` } } } },
  });

  async function muat(hari) {
    let j;
    try { j = await (await fetch('api/statistik.php?hari=' + hari)).json(); } catch { return; }
    if (!j.ok) return;
    const r = j.ringkas;
    document.getElementById('st-omzet').textContent = rp(r.omzet);
    document.getElementById('st-trx').textContent = ang(r.trx, 0);
    document.getElementById('st-liter').textContent = ang(r.liter) + ' L';
    document.getElementById('st-rata').textContent = r.trx ? rp(r.omzet / r.trx) : 'Rp0';
    document.getElementById('st-kosong').hidden = r.trx > 0;

    const label = j.harian.map(h => h.tanggal.slice(8) + '/' + h.tanggal.slice(5, 7));
    gambar('g-harian', {
      data: { labels: label, datasets: [
        { type: 'bar', label: 'Omzet (Rp)', data: j.harian.map(h => h.omzet), backgroundColor: '#0a3d91', borderRadius: 6, yAxisID: 'y' },
        { type: 'line', label: 'Liter', data: j.harian.map(h => h.liter), borderColor: '#ffb81c', backgroundColor: '#ffb81c', tension: .3, yAxisID: 'y1' } ] },
      options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v >= 1e6 ? (v / 1e6) + ' jt' : v.toLocaleString('id-ID') } },
                  y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Liter' } },
                  x: { ticks: { maxTicksLimit: 15 } } },
        plugins: { tooltip: { callbacks: { label: c => c.dataset.yAxisID === 'y' ? ` Omzet: ${rp(c.parsed.y)}` : ` Liter: ${ang(c.parsed.y)}` } } } },
    });
    donat('g-bbm', j.bbm.map(b => b.nama), j.bbm.map(b => b.liter), v => ang(v) + ' L');
    donat('g-subsidi', ['Subsidi', 'Non-subsidi'], [r.sub, r.non], v => ang(v) + ' L');
    donat('g-metode', j.metode.map(m => m.metode), j.metode.map(m => m.omzet), rp);

    document.getElementById('st-top').innerHTML = j.top.length
      ? j.top.map((t, i) => `<tr><td>${i + 1}</td><td><b>${esc(t.plat)}</b><div class="small text-muted">${esc(t.pemilik)} &middot; ${esc(t.jenis)}</div></td>` +
          `<td class="text-end">${ang(t.liter)} L<div class="small text-muted">${ang(t.trx, 0)}x</div></td><td class="text-end">${rp(t.omzet)}</td></tr>`).join('')
      : '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr>';
  }

  const tombol = [...wadah.querySelectorAll('[data-hari]')];
  tombol.forEach(b => b.addEventListener('click', () => {
    tombol.forEach(x => x.classList.toggle('active', x === b));
    muat(b.dataset.hari);
  }));
  muat(7);
})();
