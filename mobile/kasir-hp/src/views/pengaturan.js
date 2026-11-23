// Layar pengaturan: sambungan (tes koneksi), data, printer, struk, kasir di HP, pasang ulang.

import * as db from '../db.js';
import { go, session } from '../router.js';
import { openSheet, busy, toast, confirmBox } from '../ui.js';
import { esc, fmtDateTime, ago } from '../util.js';
import { conn, ping, syncInfo, syncNow, pullData, removedNote, SLOW_MS } from '../sync.js';
import { goodsCount, membersCount } from '../catalog.js';
import { printer } from '../native.js';
import { printTest } from '../sales.js';
import { mountStatus, CONN_LABEL } from '../status.js';
import { bus } from '../bus.js';

const APP_VERSION = '1.0.0';

export function pengaturanView(root) {
  const backTo = session.cashier ? 'kasir' : goodsCount() ? 'lock' : 'download';

  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      <button type="button" class="icon-btn" data-a="back">←</button>
      <div class="tb-left"><div class="tb-title">Pengaturan</div><div class="tb-sub">Kasir HP ${APP_VERSION}</div></div>
      <div class="tb-right"><span data-status="conn"></span></div>
    </header>
    <div class="content" id="body"></div>
  </div>`;
  const $ = (s) => root.querySelector(s);
  let testResult = null; // hasil "Tes koneksi" terakhir

  function connSection() {
    const d = db.kv('device') || {};
    const [label, cls] = CONN_LABEL[conn.state] || CONN_LABEL.unknown;
    let result = '';
    if (testResult) {
      if (testResult.ok) {
        const skew = conn.skewMs != null ? Math.round(conn.skewMs / 1000) : null;
        result = `<div class="alert ${testResult.latency > SLOW_MS ? 'a-yellow' : 'a-green'}">
          <b>${testResult.latency > SLOW_MS ? '⚠ Tersambung tapi lambat' : '✓ Tersambung'}</b> (${testResult.latency} ms)<br>
          Jam server: <b>${esc(fmtDateTime(testResult.serverTime, true))}</b><br>
          Jam HP: ${esc(fmtDateTime(new Date(), true))}${skew != null && Math.abs(skew) > 60 ? ` <b>(selisih ${Math.round(skew / 60)} menit — atur jam otomatis)</b>` : ''}<br>
          Perangkat di server: ${esc(testResult.device ? testResult.device.name + ' (' + testResult.device.code + ')' : '-')}
        </div>`;
      } else {
        const e = testResult.error;
        let help = '';
        if (e.kind === 'device_unknown') help = 'Perangkat belum terdaftar atau sudah dihapus. Minta kode pasang baru ke admin, lalu tekan "Pasang ulang perangkat".';
        else if (e.kind === 'device_inactive') help = 'Admin menonaktifkan HP ini di halaman Kasir HP. Minta admin mengaktifkan lagi.';
        else if (e.kind === 'network' || e.kind === 'timeout')
          help = 'Cek: data seluler/Wi-Fi HP menyala, bisa membuka internet, dan alamat server benar. Transaksi tetap bisa dibuat; nanti dikirim otomatis.';
        else if (e.kind === 'notfound' || e.kind === 'badresponse') help = 'Alamat server kemungkinan salah atau Wi-Fi meminta login.';
        result = `<div class="alert a-red"><b>✗ ${esc(e.message)}</b>${help ? `<br>${esc(help)}` : ''}
          ${e.status ? `<br><small>Kode HTTP: ${e.status}</small>` : ''}${e.detail ? `<br><small>Detail: ${esc(e.detail)}</small>` : ''}</div>`;
      }
    }
    return `
      <div class="card">
        <div class="card-title">Sambungan ke server</div>
        <div class="row-between"><span>Status</span><span class="badge ${cls}"><i class="dot"></i>${label}${conn.latency != null && conn.state !== 'offline' ? ` <small>${conn.latency} ms</small>` : ''}</span></div>
        <div class="row-between"><span>Alamat server</span><b class="wrap-any">${esc(db.kv('server') || '-')}</b></div>
        <div class="row-between"><span>Kode perangkat</span><b>${esc(d.code || '-')}</b></div>
        <div class="row-between"><span>Nama perangkat</span><span>${esc(d.name || '-')}</span></div>
        <div class="row-between"><span>Dicek</span><span>${conn.checkedAt ? ago(conn.checkedAt) : '-'}</span></div>
        ${conn.state !== 'online' && conn.error && !testResult ? `<div class="alert a-red">${esc(conn.error)}</div>` : ''}
        ${result}
        <button type="button" class="btn btn-primary btn-block" data-a="test">Tes koneksi</button>
      </div>`;
  }

  function syncSection() {
    return `
      <div class="card">
        <div class="card-title">Sinkron & data</div>
        <div class="row-between"><span>Belum terkirim</span><b>${syncInfo.pending}</b></div>
        <div class="row-between"><span>Ditolak server</span><b>${syncInfo.rejected}</b></div>
        <div class="row-between"><span>Sinkron terakhir</span><span>${db.kv('lastSyncAt') ? fmtDateTime(db.kv('lastSyncAt')) + ' (' + ago(db.kv('lastSyncAt')) + ')' : 'belum pernah'}</span></div>
        <div class="row-between"><span>Data barang</span><span>${goodsCount()} barang · ${membersCount()} member</span></div>
        <div class="row-between"><span>Data diperbarui</span><span>${db.kv('catalogAt') ? ago(db.kv('catalogAt')) : '-'}</span></div>
        <div class="btn-row">
          <button type="button" class="btn" data-a="sync">${syncInfo.running ? 'Mengirim…' : 'Sinkronkan'}</button>
          <button type="button" class="btn" data-a="redownload">Unduh ulang data</button>
        </div>
      </div>`;
  }

  function printerSection() {
    const p = db.kv('printer');
    const st = printer.state();
    return `
      <div class="card">
        <div class="card-title">Printer struk (Bluetooth)</div>
        <div class="row-between"><span>Printer</span><b>${p ? esc(p.name) : 'belum dipilih'}</b></div>
        <div class="row-between"><span>Status</span>${
          p ? (st.connected ? '<span class="pbadge p-ok">tersambung</span>' : '<span class="pbadge p-off">tidak tersambung</span>') : '<span class="muted">-</span>'
        }</div>
        <div class="row-between"><span>Lebar kertas</span>
          <span class="seg seg-sm">
            <button type="button" data-width="32" class="${Number(db.kv('paperWidth', 32)) === 32 ? 'on' : ''}">58mm</button>
            <button type="button" data-width="48" class="${Number(db.kv('paperWidth', 32)) === 48 ? 'on' : ''}">80mm</button>
          </span></div>
        <div class="hint">Pair printer dulu di Pengaturan Bluetooth Android (PIN biasanya 0000 atau 1234), lalu pilih di sini.</div>
        <div class="btn-row">
          <button type="button" class="btn" data-a="choose-printer">Pilih printer</button>
          ${p ? `<button type="button" class="btn" data-a="${st.connected ? 'disconnect' : 'connect'}">${st.connected ? 'Putuskan' : 'Hubungkan'}</button>` : ''}
        </div>
        ${p ? '<button type="button" class="btn btn-primary btn-block" data-a="test-print">🖨 Cetak tes</button>' : ''}
      </div>`;
  }

  function receiptSection() {
    return `
      <div class="card">
        <div class="card-title">Isi struk</div>
        <label for="storeName">Nama toko</label>
        <input id="storeName" value="${esc(db.kv('storeName', 'NTN MART'))}">
        <label for="storeLines">Alamat / telepon (boleh beberapa baris)</label>
        <textarea id="storeLines" rows="2">${esc(db.kv('storeLines', ''))}</textarea>
        <label for="storeFooter">Pesan di bawah struk</label>
        <textarea id="storeFooter" rows="2">${esc(db.kv('storeFooter', 'Terima kasih atas kunjungan Anda'))}</textarea>
        <button type="button" class="btn btn-block" data-a="save-receipt">Simpan isi struk</button>
      </div>`;
  }

  async function cashierSection() {
    const list = await db.all('cashiers');
    return `
      <div class="card">
        <div class="card-title">Kasir di HP ini</div>
        ${list.length ? list.map((c) => `<div class="row-between"><span>${esc(c.name)} <small class="muted">${esc(c.email)}</small></span>
          <button type="button" class="btn btn-sm" data-del-cashier="${c.id}">Hapus</button></div>`).join('') : '<div class="muted">Belum ada.</div>'}
        <div class="hint">Menghapus kasir dari HP tidak menghapus akunnya di server.</div>
      </div>`;
  }

  function deviceSection() {
    return `
      <div class="card">
        <div class="card-title">Perangkat</div>
        <p class="muted small">Pasang ulang kalau admin membuat kode pasang baru, HP dihapus dari server, atau pindah alamat server.
          Transaksi yang belum terkirim tetap disimpan.</p>
        <button type="button" class="btn btn-block" data-a="repair">Pasang ulang perangkat</button>
        <div class="muted small center">Kasir HP versi ${APP_VERSION}</div>
      </div>`;
  }

  // Bagian yang berubah sendiri (sambungan, sinkron, printer) digambar ulang terpisah
  // supaya isian struk yang sedang diketik tidak hilang.
  $('#body').innerHTML = `<div id="s-conn"></div><div id="s-sync"></div><div id="s-printer"></div>
    <div id="s-receipt">${receiptSection()}</div><div id="s-cashier"></div><div id="s-device">${deviceSection()}</div>`;
  const renderConn = () => ($('#s-conn').innerHTML = connSection());
  const renderSync = () => ($('#s-sync').innerHTML = syncSection());
  const renderPrinter = () => ($('#s-printer').innerHTML = printerSection());
  const renderCashiers = async () => ($('#s-cashier').innerHTML = await cashierSection());
  async function render() {
    renderConn();
    renderSync();
    renderPrinter();
    await renderCashiers();
  }

  async function choosePrinter() {
    const b = busy('Membaca daftar Bluetooth…');
    let list;
    try {
      list = await printer.listPaired();
    } catch (e) {
      b.done();
      toast(e.message || String(e), 'error', 5000);
      return;
    }
    b.done();
    list.sort((a, c) => (c.printer ? 1 : 0) - (a.printer ? 1 : 0));
    const s = openSheet({
      title: 'Pilih printer',
      full: true,
      html: list.length
        ? `<p class="muted small">Daftar perangkat yang sudah di-pair di Android. Printer biasanya bernama RPP02, MTP, BlueTooth Printer, dsb.</p>
           <div class="member-list">${list
             .map((d) => `<button type="button" class="member" data-addr="${esc(d.address)}" data-name="${esc(d.name)}">
               <b>${d.printer ? '🖨 ' : ''}${esc(d.name)}</b><div class="muted small">${esc(d.address)}</div></button>`)
             .join('')}</div>`
        : `<div class="empty">Belum ada perangkat Bluetooth yang di-pair.<br>Buka Pengaturan Android › Bluetooth, nyalakan printer, pair (PIN 0000 / 1234), lalu kembali ke sini.</div>`,
    });
    s.body.addEventListener('click', async (e) => {
      const m = e.target.closest('[data-addr]');
      if (!m) return;
      s.close();
      await db.setKv('printer', { address: m.dataset.addr, name: m.dataset.name });
      const bb = busy('Menyambung ke ' + m.dataset.name + '…');
      try {
        await printer.connect(m.dataset.addr);
        toast('Printer tersambung ✓', 'ok');
      } catch (err) {
        toast((err.message || err) + '', 'error', 5000);
      }
      bb.done();
      render();
    });
  }

  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    const w = e.target.closest('[data-width]');
    const dc = e.target.closest('[data-del-cashier]');
    if (w) {
      await db.setKv('paperWidth', Number(w.dataset.width));
      render();
    }
    if (dc) {
      const c = await db.get('cashiers', Number(dc.dataset.delCashier));
      if (c && (await confirmBox(`Hapus kasir ${c.name} dari HP ini? Untuk memakai lagi harus login online.`, { ok: 'Hapus', danger: true }))) {
        await db.del('cashiers', c.id);
        if (session.cashier && session.cashier.id === c.id) session.cashier = null;
        render();
      }
    }
    if (!a) return;
    const act = a.dataset.a;
    if (act === 'back') go(session.cashier ? 'kasir' : goodsCount() ? 'lock' : 'download');
    if (act === 'test') {
      const b = busy('Menghubungi server…');
      const r = await ping();
      b.done();
      testResult = r.ok ? { ok: true, latency: r.latency, serverTime: r.data.server_time, device: r.data.device } : { ok: false, error: r.error };
      render();
    }
    if (act === 'sync') {
      const r = await syncNow({ catalog: 'force' });
      toast(
        r.error ? r.error.message : `Sinkron selesai: ${r.sent} terkirim${r.rejected ? `, ${r.rejected} ditolak` : ''}${removedNote(r)}`,
        r.error ? 'error' : r.removed ? 'warn' : 'ok',
        r.removed ? 5000 : 4000
      );
      render();
    }
    if (act === 'redownload') {
      if (!(await confirmBox('Unduh ulang semua data barang & member dari server? Butuh internet, bisa beberapa menit kalau lambat.'))) return;
      const b = busy('Mengunduh…');
      try {
        const r = await pullData(true, (t) => b.set(t));
        toast(`Selesai: ${r.goods} barang, ${r.members} member`, 'ok');
      } catch (err) {
        toast(err.message, 'error', 5000);
      }
      b.done();
      render();
    }
    if (act === 'choose-printer') choosePrinter();
    if (act === 'connect') {
      const b = busy('Menyambung ke printer…');
      try {
        await printer.connect(db.kv('printer').address);
        toast('Printer tersambung ✓', 'ok');
      } catch (err) {
        toast((err.message || err) + '', 'error', 5000);
      }
      b.done();
      render();
    }
    if (act === 'disconnect') {
      await printer.disconnect();
      render();
    }
    if (act === 'test-print') {
      const b = busy('Mencetak tes…');
      try {
        await printTest();
        toast('Cetak tes terkirim ke printer ✓', 'ok');
      } catch (err) {
        toast((err.message || err) + '', 'error', 5000);
      }
      b.done();
      render();
    }
    if (act === 'save-receipt') {
      await db.setKv('storeName', $('#storeName').value.trim() || 'TOKO');
      await db.setKv('storeLines', $('#storeLines').value.trim());
      await db.setKv('storeFooter', $('#storeFooter').value.trim());
      toast('Isi struk disimpan ✓', 'ok');
    }
    if (act === 'repair') {
      const msg = syncInfo.pending
        ? `Masih ada ${syncInfo.pending} transaksi belum terkirim. Transaksi tetap disimpan dan akan dikirim setelah HP terpasang lagi. Lanjut pasang ulang?`
        : 'Pasang ulang perangkat dengan kode baru dari admin?';
      if (await confirmBox(msg, { ok: 'Pasang ulang' })) go('pair', { canCancel: true });
    }
  });

  const offStatus = mountStatus(root, (n) => go(n === 'settings' ? 'pengaturan' : n));
  const offs = [
    bus.on('sync', renderSync),
    bus.on('catalog', renderSync),
    printer.onState(renderPrinter),
    bus.on('conn', renderConn),
    bus.on('device', renderConn),
  ];
  render();
  return {
    cleanup: () => {
      offStatus();
      offs.forEach((o) => o());
    },
    back: () => go(backTo),
  };
}
