// Layar: unduh data barang & member pertama kali (harus online).

import * as db from '../db.js';
import { go } from '../router.js';
import { esc } from '../util.js';
import { pullData, startMonitor } from '../sync.js';

export function downloadView(root) {
  startMonitor();
  const device = db.kv('device') || {};
  root.innerHTML = `
  <div class="screen">
    <header class="topbar"><div class="tb-left"><div class="tb-title">Unduh data toko</div>
      <div class="tb-sub">${esc(device.name || '')} (${esc(device.code || '')})</div></div></header>
    <div class="content">
      <div class="card center">
        <div class="spinner big" id="spin"></div>
        <p id="msg">Menyiapkan…</p>
        <p class="muted small">Data barang, harga, dan member diunduh sekali ke HP. Setelah ini kasir tetap bisa jualan walau internet mati.</p>
        <div id="err" class="error" hidden></div>
        <div class="btn-row" id="retry-row" hidden>
          <button type="button" class="btn" data-a="settings">Pengaturan</button>
          <button type="button" class="btn btn-primary" data-a="retry">Coba lagi</button>
        </div>
      </div>
    </div>
  </div>`;
  const $ = (s) => root.querySelector(s);
  let alive = true;

  async function run() {
    $('#err').hidden = true;
    $('#retry-row').hidden = true;
    $('#spin').hidden = false;
    try {
      const r = await pullData(true, (t) => alive && ($('#msg').textContent = t));
      if (!alive) return;
      $('#msg').textContent = `Selesai: ${r.goods} barang, ${r.members} member.`;
      const cashiers = await db.all('cashiers');
      setTimeout(() => alive && go(cashiers.length ? 'lock' : 'addCashier'), 600);
    } catch (e) {
      if (!alive) return;
      $('#spin').hidden = true;
      $('#msg').textContent = 'Gagal mengunduh data.';
      $('#err').hidden = false;
      $('#err').textContent = e.message;
      if (e.detail) {
        const d = document.createElement('div');
        d.className = 'muted small';
        d.textContent = 'Detail: ' + e.detail;
        $('#err').appendChild(d);
      }
      $('#retry-row').hidden = false;
    }
  }

  root.addEventListener('click', (e) => {
    const a = e.target.closest('[data-a]');
    if (!a) return;
    if (a.dataset.a === 'retry') run();
    if (a.dataset.a === 'settings') go('pengaturan');
  });
  run();
  return { cleanup: () => (alive = false) };
}
