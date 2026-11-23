// Kasir PC: titik awal tampilan. Memakai layar & modul Kasir HP, ditambah layar khusus PC
// (kasir satu layar, pengaturan printer Windows, pasang perangkat tanpa kamera) dan dukungan keyboard.

import '@hp/style.css';
import './desktop.css';

import * as db from '@hp/db.js';
import { getToken } from '@hp/api.js';
import { loadCatalog, loadMembers, goodsCount } from '@hp/catalog.js';
import { startMonitor, refreshCounts, pruneOldSales, syncInfo } from '@hp/sync.js';
import { register, go, currentView } from '@hp/router.js';
import { closeTopSheet, toast } from '@hp/ui.js';
import { bus } from '@hp/bus.js';
import { printer } from './platform/native.js';

import { downloadView } from '@hp/views/download.js';
import { lockView, addCashierView } from '@hp/views/lock.js';
import { riwayatView } from '@hp/views/riwayat.js';
import { pairView } from './views/pair.js';
import { kasirView } from './views/kasir.js';
import { pengaturanView } from './views/pengaturan.js';

const bridge = window.kasirPc || null;

/** Layar pilih kasir: sorot kasir terakhir supaya cukup Enter lalu ketik PIN. */
function lockViewPc(root, params) {
  const r = lockView(root, params);
  let tries = 0;
  const focusLast = () => {
    const btn = root.querySelector(`.cashier[data-id="${db.kv('lastCashierId')}"]`) || root.querySelector('.cashier');
    if (btn) btn.focus();
    else if (root.isConnected && tries++ < 40) setTimeout(focusLast, 25);
  };
  focusLast();
  return r;
}

register('pair', pairView);
register('download', downloadView);
register('lock', lockViewPc);
register('addCashier', addCashierView);
register('kasir', kasirView);
register('riwayat', riwayatView);
register('pengaturan', pengaturanView);

function checkPrinter() {
  const p = db.kv('printer');
  if (p && p.address) printer.status(p.address).catch(() => {});
}

async function boot() {
  await db.openDb();
  await db.loadKv();
  await Promise.all([loadCatalog(), loadMembers()]);
  pruneOldSales().catch(() => {});

  const device = db.kv('device');
  if (device && device.code) document.title = `Kasir PC — ${device.code}`;

  const token = await getToken();
  if (!db.kv('server') || !token) {
    go('pair');
    return;
  }
  startMonitor();
  refreshCounts();
  checkPrinter();
  if (!goodsCount() && !db.kv('catalogSince')) go('download');
  else go('lock');
}

// Jumlah transaksi belum terkirim -> peringatan saat jendela ditutup (electron/main.js).
bus.on('sync', () => bridge && bridge.setPending(syncInfo.pending));
bus.on('device', (d) => d && d.code && (document.title = `Kasir PC — ${d.code}`));
window.addEventListener('focus', checkPrinter);

// ---- Keyboard untuk komponen Kasir HP yang dibuat untuk layar sentuh ----
const isField = (el) => el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable);
const topOverlay = () => {
  const all = document.querySelectorAll('body > .overlay');
  return all.length ? all[all.length - 1] : null;
};

window.addEventListener('keydown', (e) => {
  if (document.querySelector('.busy')) return;
  const ov = topOverlay();

  // Papan PIN: ketik angka, Backspace, Enter.
  const pad = ov ? ov.querySelector('.pin-keys') : document.querySelector('.pin-keys');
  if (pad && !isField(e.target)) {
    let k = null;
    if (/^[0-9]$/.test(e.key)) k = e.key;
    else if (e.key === 'Backspace') k = 'del';
    else if (e.key === 'Enter') k = 'ok';
    if (k) {
      e.preventDefault();
      const b = pad.querySelector(`[data-k="${k}"]`);
      if (b) b.click();
      return;
    }
  }

  if (e.key === 'Escape' && !e.defaultPrevented) {
    if (ov) {
      e.preventDefault();
      closeTopSheet();
      return;
    }
    const cur = currentView();
    if (cur && cur.back) {
      e.preventDefault();
      cur.back();
    }
  }
});

// Jendela (sheet) baru: fokus ke kolom isian pertama, atau ke tombol utama supaya Enter = OK.
new MutationObserver((muts) => {
  for (const m of muts) {
    for (const n of m.addedNodes) {
      if (!(n instanceof HTMLElement) || !n.classList.contains('overlay')) continue;
      setTimeout(() => {
        if (!n.isConnected || n.contains(document.activeElement)) return;
        if (n.querySelector('.pin-keys')) {
          document.activeElement && document.activeElement.blur && document.activeElement.blur();
          return;
        }
        const field = n.querySelector('input:not([type=checkbox]), textarea');
        const btn = n.querySelector('.btn-row .btn-primary, .btn-row .btn-danger');
        (field || btn || n.querySelector('.sheet-x'))?.focus();
      }, 30);
    }
  }
}).observe(document.body, { childList: true });

window.addEventListener('unhandledrejection', (e) => {
  console.error(e.reason);
  toast('Kesalahan: ' + ((e.reason && e.reason.message) || e.reason), 'error', 4000);
});

boot().catch((e) => {
  console.error(e);
  document.getElementById('app').innerHTML = `<div class="boot error">Aplikasi gagal dibuka: ${String(e && e.message ? e.message : e)}</div>`;
});
