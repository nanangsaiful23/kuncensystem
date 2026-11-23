// Layar setelah transaksi disimpan: kembalian besar, status kirim, cetak ulang, transaksi baru.

import * as db from '../db.js';
import { go } from '../router.js';
import { busy, toast } from '../ui.js';
import { esc, rp } from '../util.js';
import { printSale } from '../sales.js';
import { bus } from '../bus.js';
import { statusHtml } from './common.js';

export function selesaiView(root, { uuid, printError = null, printed = false }) {
  root.innerHTML = `<div class="screen"><div class="content"><div class="card center">Memuat…</div></div></div>`;
  let sale = null;

  async function load() {
    sale = await db.get('sales', uuid);
    if (!sale) return go('kasir');
    render();
  }

  function render() {
    root.innerHTML = `
    <div class="screen">
      <header class="topbar"><div class="tb-left"><div class="tb-title">Transaksi tersimpan ✓</div>
        <div class="tb-sub">${esc(sale.receipt_no)}</div></div></header>
      <div class="content">
        <div class="card center done">
          <div class="muted">Total ${rp(sale.total)} · ${sale.payment === 'transfer' ? 'Transfer' : 'Tunai ' + rp(sale.money_paid)}</div>
          ${sale.payment === 'transfer' ? '<div class="done-big">LUNAS</div>' : `<div class="muted">Kembalian</div><div class="done-big">${rp(sale.money_returned)}</div>`}
          <div id="st">${statusHtml(sale)}</div>
          <div class="muted small">Tersimpan di HP. ${sale.status === 'sent' ? '' : 'Dikirim ke server otomatis saat online.'}</div>
        </div>
        ${printError ? `<div class="alert a-red">Struk gagal dicetak: ${esc(printError)}</div>` : printed ? '<div class="alert a-green">Struk dicetak ✓</div>' : ''}
        <div class="btn-col">
          <button type="button" class="btn btn-block" data-a="print">${printed ? '🖨 Cetak ulang struk' : '🖨 Cetak struk'}</button>
          <button type="button" class="btn btn-primary btn-block big" data-a="new">Transaksi baru</button>
        </div>
      </div>
    </div>`;
  }

  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    if (!a || !sale) return;
    if (a.dataset.a === 'new') go('kasir');
    if (a.dataset.a === 'print') {
      const b = busy('Mencetak…');
      try {
        await printSale(sale, { reprint: printed || sale.printed > 0 });
        printError = null;
        printed = true;
        toast('Struk dicetak ✓', 'ok');
      } catch (err) {
        printError = err.message;
      }
      b.done();
      sale = (await db.get('sales', uuid)) || sale;
      render();
    }
  });

  // Status kirim berubah (mis. tersinkron di latar belakang)
  const off = bus.on('sync', async () => {
    const fresh = await db.get('sales', uuid);
    if (fresh && sale && fresh.status !== sale.status) {
      sale = fresh;
      render();
    }
  });

  load();
  return { cleanup: off, back: () => go('kasir') };
}
