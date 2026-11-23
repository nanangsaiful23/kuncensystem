// Layar riwayat transaksi di HP: hari ini, belum terkirim, ditolak.

import * as db from '../db.js';
import { go, session } from '../router.js';
import { openSheet, busy, toast, confirmBox } from '../ui.js';
import { esc, rp, dayKey } from '../util.js';
import { printSale } from '../sales.js';
import { syncNow, refreshCounts } from '../sync.js';
import { mountStatus } from '../status.js';
import { bus } from '../bus.js';
import { saleRowHtml, saleDetailHtml } from './common.js';

export function riwayatView(root, { tab = 'today' } = {}) {
  const backTo = session.cashier ? 'kasir' : 'lock';
  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      <button type="button" class="icon-btn" data-a="back">←</button>
      <div class="tb-left"><div class="tb-title">Riwayat transaksi</div><div class="tb-sub">Tersimpan di HP ini</div></div>
      <div class="tb-right"><span data-status="conn"></span></div>
    </header>
    <div class="strip" data-status="strip"></div>
    <div data-status="alerts"></div>
    <div class="tabs">
      <button type="button" data-tab="today">Hari ini</button>
      <button type="button" data-tab="pending">Belum terkirim</button>
      <button type="button" data-tab="rejected">Ditolak</button>
    </div>
    <div class="content" id="list"></div>
  </div>`;
  const $ = (s) => root.querySelector(s);

  async function render() {
    root.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('on', b.dataset.tab === tab));
    let list;
    if (tab === 'today') list = await db.allByIndex('sales', 'day', dayKey());
    else list = await db.allByIndex('sales', 'status', tab);
    list.sort((a, b) => (a.sold_at < b.sold_at ? 1 : -1));

    let head = '';
    if (tab === 'today') {
      const sum = (arr) => arr.reduce((a, s) => a + s.total, 0);
      const cash = list.filter((s) => s.payment !== 'transfer');
      const tf = list.filter((s) => s.payment === 'transfer');
      const mine = session.cashier ? list.filter((s) => s.cashier_id === session.cashier.id) : [];
      head = `<div class="card summary">
        <div class="row-between"><span>${list.length} transaksi hari ini</span><b>Rp ${rp(sum(list))}</b></div>
        <div class="row-between muted small"><span>Tunai ${rp(sum(cash))}</span><span>Transfer ${rp(sum(tf))}</span></div>
        ${session.cashier ? `<div class="row-between muted small"><span>Oleh ${esc(session.cashier.name)}: ${mine.length} transaksi</span><span>${rp(sum(mine))}</span></div>` : ''}
      </div>`;
    }
    if (tab === 'rejected' && list.length) {
      head = `<div class="alert a-red">Transaksi ditolak tidak dikirim ulang otomatis. Admin bisa melihatnya di
        <b>Pengaturan › Kasir HP</b> (Transaksi HP ditolak) dan menginput manual bila perlu.</div>`;
    }
    $('#list').innerHTML =
      head + (list.length ? `<div class="sale-list">${list.map(saleRowHtml).join('')}</div>` : '<div class="empty">Tidak ada transaksi.</div>');
  }

  async function detail(uuid) {
    const s = await db.get('sales', uuid);
    if (!s) return;
    const sh = openSheet({
      title: 'Transaksi ' + s.receipt_no,
      full: true,
      html: `${saleDetailHtml(s)}
        <div class="btn-col">
          <button type="button" class="btn btn-block" data-d="print">🖨 Cetak ulang struk</button>
          ${s.status === 'rejected' ? '<button type="button" class="btn btn-block" data-d="retry">↻ Coba kirim lagi (setelah admin membereskan)</button>' : ''}
        </div>`,
    });
    sh.body.addEventListener('click', async (e) => {
      const d = e.target.closest('[data-d]');
      if (!d) return;
      if (d.dataset.d === 'print') {
        const b = busy('Mencetak…');
        try {
          await printSale(s, { reprint: true });
          toast('Struk dicetak ✓', 'ok');
        } catch (err) {
          toast(err.message, 'error', 4000);
        }
        b.done();
      }
      if (d.dataset.d === 'retry') {
        if (!(await confirmBox('Kirim ulang transaksi ini ke server? Kalau admin belum membereskannya, server akan tetap menolak.'))) return;
        s.status = 'pending';
        s.message = null;
        await db.put('sales', s);
        await refreshCounts();
        sh.close();
        const r = await syncNow({ catalog: 'none' });
        toast(r.error ? r.error.message : 'Selesai dikirim', r.error ? 'error' : 'ok', 3500);
      }
    });
  }

  root.addEventListener('click', (e) => {
    const t = e.target.closest('[data-tab]');
    if (t) {
      tab = t.dataset.tab;
      render();
    }
    const row = e.target.closest('.sale-row');
    if (row) detail(row.dataset.uuid);
    const a = e.target.closest('[data-a]');
    if (a && a.dataset.a === 'back') go(backTo);
  });

  const offStatus = mountStatus(root, (name) => {
    if (name === 'history-rejected') {
      tab = 'rejected';
      render();
    } else go(name === 'settings' ? 'pengaturan' : name);
  });
  const off = bus.on('sync', render);
  render();
  return {
    cleanup: () => {
      offStatus();
      off();
    },
    back: () => go(backTo),
  };
}
