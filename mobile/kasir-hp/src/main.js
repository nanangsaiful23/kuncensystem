// Kasir HP: titik awal aplikasi.

import { App } from '@capacitor/app';
import * as db from './db.js';
import { getToken } from './api.js';
import { loadCatalog, loadMembers, goodsCount } from './catalog.js';
import { startMonitor, refreshCounts, pruneOldSales } from './sync.js';
import { printer, isNative } from './native.js';
import { register, go, currentView } from './router.js';
import { closeTopSheet, toast } from './ui.js';
import { bus } from './bus.js';
import { isScanning, stopScan } from './scanner.js';

import { pairView } from './views/pair.js';
import { downloadView } from './views/download.js';
import { lockView, addCashierView } from './views/lock.js';
import { kasirView } from './views/kasir.js';
import { bayarView } from './views/bayar.js';
import { selesaiView } from './views/selesai.js';
import { riwayatView } from './views/riwayat.js';
import { pengaturanView } from './views/pengaturan.js';

register('pair', pairView);
register('download', downloadView);
register('lock', lockView);
register('addCashier', addCashierView);
register('kasir', kasirView);
register('bayar', bayarView);
register('selesai', selesaiView);
register('riwayat', riwayatView);
register('pengaturan', pengaturanView);

async function boot() {
  await db.openDb();
  await db.loadKv();
  await Promise.all([loadCatalog(), loadMembers()]);
  pruneOldSales().catch(() => {});

  const token = await getToken();
  if (!db.kv('server') || !token) {
    go('pair');
    return;
  }
  startMonitor();
  refreshCounts();
  connectSavedPrinter();
  if (!goodsCount() && !db.kv('catalogSince')) go('download');
  else go('lock');
}

/** Sambungkan printer yang sudah dipilih (diam-diam, tidak mengganggu bila gagal). */
export function connectSavedPrinter() {
  const p = db.kv('printer');
  if (isNative && p && p.address && !printer.state().connected) {
    printer.connect(p.address).catch(() => {});
  }
}

// Transaksi yang dihapus admin di web ikut dihapus dari riwayat: beri tahu kasir.
bus.on('removed', (list) => {
  const what = list.length === 1 ? `Transaksi ${list[0].receipt_no}` : `${list.length} transaksi`;
  toast(`${what} dihapus admin di web, ikut dihapus dari riwayat.`, 'warn', 5000);
});
// Transaksi yang diubah admin di web (jumlah, member, catatan, uang bayar): riwayat ikut diperbarui.
bus.on('edited', (list) => {
  const what = list.length === 1 ? `Transaksi ${list[0].receipt_no}` : `${list.length} transaksi`;
  toast(`${what} diubah admin di web, riwayat ikut diperbarui.`, 'warn', 5000);
});

// Tombol kembali Android
try {
  App.addListener('backButton', () => {
    if (isScanning()) return stopScan();
    if (closeTopSheet()) return;
    const cur = currentView();
    if (cur && cur.back) return cur.back();
    App.minimizeApp();
  });
  App.addListener('appStateChange', ({ isActive }) => {
    if (isActive && currentView() && currentView().name !== 'pair') connectSavedPrinter();
  });
} catch (e) {
  /* browser */
}

window.addEventListener('unhandledrejection', (e) => {
  console.error(e.reason);
  toast('Kesalahan: ' + ((e.reason && e.reason.message) || e.reason), 'error', 4000);
});

boot().catch((e) => {
  console.error(e);
  document.getElementById('app').innerHTML =
    `<div class="boot error">Aplikasi gagal dibuka: ${String(e && e.message ? e.message : e)}</div>`;
});
