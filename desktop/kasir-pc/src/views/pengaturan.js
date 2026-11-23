// Pengaturan Kasir PC: sambungan (tes koneksi), data, printer Windows, isi struk, kasir, pasang ulang.
// Turunan dari views/pengaturan.js Kasir HP; bagian printer diganti printer Windows.

import * as db from '@hp/db.js';
import { go, session } from '@hp/router.js';
import { openSheet, busy, toast, confirmBox } from '@hp/ui.js';
import { esc, fmtDateTime, ago } from '@hp/util.js';
import { conn, ping, syncInfo, syncNow, pullData, SLOW_MS } from '@hp/sync.js';
import { goodsCount, membersCount } from '@hp/catalog.js';
import { mountStatus, CONN_LABEL } from '@hp/status.js';
import { bus } from '@hp/bus.js';
import { printer } from '../platform/native.js';
import { printTest } from '../sales.js';

/* global __APP_VERSION__ */
const APP_VERSION = typeof __APP_VERSION__ !== 'undefined' ? __APP_VERSION__ : 'dev';

export function pengaturanView(root) {
  const backTo = () => (session.cashier ? 'kasir' : goodsCount() ? 'lock' : 'download');

  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      <button type="button" class="icon-btn" data-a="back" title="Kembali (Esc)">←</button>
      <div class="tb-left"><div class="tb-title">Pengaturan</div><div class="tb-sub">Kasir PC ${APP_VERSION}</div></div>
      <div class="tb-right"><span data-status="conn"></span></div>
    </header>
    <div class="content pc-settings" id="body"></div>
  </div>`;
  const $ = (s) => root.querySelector(s);
  let testResult = null;
  let info = null;

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
          Jam komputer: ${esc(fmtDateTime(new Date(), true))}${skew != null && Math.abs(skew) > 60 ? ` <b>(selisih ${Math.round(skew / 60)} menit — atur jam otomatis di Windows)</b>` : ''}<br>
          Perangkat di server: ${esc(testResult.device ? testResult.device.name + ' (' + testResult.device.code + ')' : '-')}
        </div>`;
      } else {
        const e = testResult.error;
        let help = '';
        if (e.kind === 'device_unknown') help = 'Perangkat belum terdaftar atau sudah dihapus. Minta kode pasang baru ke admin, lalu tekan "Pasang ulang perangkat".';
        else if (e.kind === 'device_inactive') help = 'Admin menonaktifkan komputer ini di halaman Kasir HP. Minta admin mengaktifkan lagi.';
        else if (e.kind === 'network' || e.kind === 'timeout')
          help = 'Cek: internet/Wi-Fi komputer menyala, bisa membuka situs lain, dan alamat server benar. Transaksi tetap bisa dibuat; nanti dikirim otomatis.';
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
        <div class="hint">Perubahan harga/barang di server ikut diunduh otomatis tiap 10 menit saat online.</div>
        <div class="btn-row">
          <button type="button" class="btn" data-a="sync">${syncInfo.running ? 'Mengirim…' : 'Sinkronkan sekarang'}</button>
          <button type="button" class="btn" data-a="redownload">Unduh ulang semua data</button>
        </div>
      </div>`;
  }

  function printerSection() {
    const p = db.kv('printer');
    const st = printer.state();
    const width = Number(db.kv('paperWidth', 32));
    const dialog = db.kv('printDialog') === true;
    return `
      <div class="card">
        <div class="card-title">Printer struk (printer Windows)</div>
        <div class="row-between"><span>Printer</span><b>${p ? esc(p.name) : 'belum dipilih'}</b></div>
        <div class="row-between"><span>Status</span>${
          p ? (st.connected ? '<span class="pbadge p-ok">ada di Windows</span>' : '<span class="pbadge p-off">tidak ditemukan</span>') : '<span class="muted">-</span>'
        }</div>
        <div class="row-between"><span>Lebar kertas</span>
          <span class="seg seg-sm">
            <button type="button" data-width="32" class="${width === 32 ? 'on' : ''}">58mm</button>
            <button type="button" data-width="48" class="${width === 48 ? 'on' : ''}">80mm</button>
          </span></div>
        <div class="row-between"><span>Cara cetak</span>
          <span class="seg seg-sm">
            <button type="button" data-dialog="0" class="${!dialog ? 'on' : ''}">Langsung</button>
            <button type="button" data-dialog="1" class="${dialog ? 'on' : ''}">Tampilkan dialog</button>
          </span></div>
        <div class="hint">Pasang dulu driver printer thermal di Windows (mis. POS-58 / XP-58) sampai printer muncul di
          Pengaturan Windows › Printer. "Langsung" = struk keluar tanpa jendela cetak. Kalau struk terpotong atau
          kecil sekali, coba "Tampilkan dialog" untuk memilih ukuran kertas di driver.</div>
        <div class="btn-row">
          <button type="button" class="btn" data-a="choose-printer">Pilih printer</button>
          ${p ? '<button type="button" class="btn" data-a="recheck">Periksa lagi</button>' : ''}
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
        <div class="card-title">Kasir di komputer ini</div>
        ${list.length ? list.map((c) => `<div class="row-between"><span>${esc(c.name)} <small class="muted">${esc(c.email)}</small></span>
          <button type="button" class="btn btn-sm" data-del-cashier="${c.id}">Hapus</button></div>`).join('') : '<div class="muted">Belum ada.</div>'}
        <div class="hint">Menghapus kasir dari komputer ini tidak menghapus akunnya di server.</div>
      </div>`;
  }

  function aboutSection() {
    return `
      <div class="card">
        <div class="card-title">Perangkat & data</div>
        <p class="muted small">Pasang ulang kalau admin membuat kode pasang baru, perangkat dihapus dari server, atau pindah alamat server.
          Transaksi yang belum terkirim tetap disimpan.</p>
        <button type="button" class="btn btn-block" data-a="repair">Pasang ulang perangkat</button>
        <hr>
        <div class="row-between"><span>Versi</span><span>Kasir PC ${APP_VERSION}${info ? ` · Electron ${esc(info.electron)}` : ''}</span></div>
        ${
          info
            ? `<div class="row-between"><span>Folder data</span><span class="wrap-any small">${esc(info.dataDir)}</span></div>
               <div class="row-between"><span>Token perangkat</span><span>${info.encrypted ? 'terenkripsi (Windows)' : '<b>tidak terenkripsi</b>'}</span></div>
               <button type="button" class="btn btn-block" data-a="open-data">Buka folder data</button>`
            : ''
        }
        <div class="hint">Semua transaksi & data barang ada di folder data. Jangan hapus folder itu sebelum semua transaksi terkirim.
          Ukuran tampilan: <kbd>Ctrl</kbd> + <kbd>+</kbd> / <kbd>−</kbd> / <kbd>0</kbd>, layar penuh <kbd>F11</kbd>.</div>
      </div>`;
  }

  $('#body').innerHTML = `<div class="pc-cols"><div><div id="s-conn"></div><div id="s-sync"></div><div id="s-cashier"></div></div>
    <div><div id="s-printer"></div><div id="s-receipt">${receiptSection()}</div><div id="s-about"></div></div></div>`;
  const renderConn = () => ($('#s-conn').innerHTML = connSection());
  const renderSync = () => ($('#s-sync').innerHTML = syncSection());
  const renderPrinter = () => ($('#s-printer').innerHTML = printerSection());
  const renderCashiers = async () => ($('#s-cashier').innerHTML = await cashierSection());
  const renderAbout = () => ($('#s-about').innerHTML = aboutSection());
  async function render() {
    renderConn();
    renderSync();
    renderPrinter();
    renderAbout();
    await renderCashiers();
  }

  async function choosePrinter() {
    const b = busy('Membaca daftar printer Windows…');
    let list;
    try {
      list = await printer.listPaired();
    } catch (e) {
      b.done();
      toast(e.message || String(e), 'error', 5000);
      return;
    }
    b.done();
    const cur = (db.kv('printer') || {}).address;
    const s = openSheet({
      title: 'Pilih printer',
      html: list.length
        ? `<p class="muted small">Printer yang terpasang di Windows. Printer thermal biasanya bernama POS-58, XP-58, POS-80, EPSON TM, dsb.</p>
           <div class="member-list">${list
             .map((d) => `<button type="button" class="member ${d.address === cur ? 'on' : ''}" data-addr="${esc(d.address)}" data-name="${esc(d.name)}">
               <b>🖨 ${esc(d.name)}</b>${d.isDefault ? ' <span class="tag">default Windows</span>' : ''}
               ${d.description ? `<div class="muted small">${esc(d.description)}</div>` : ''}</button>`)
             .join('')}</div>`
        : `<div class="empty">Belum ada printer di Windows.<br>Pasang driver printer thermal (dari CD/situs pembuat printer),
           pastikan printer muncul di Pengaturan Windows › Bluetooth & perangkat › Printer, lalu buka lagi di sini.</div>`,
    });
    s.body.addEventListener('click', async (e) => {
      const m = e.target.closest('[data-addr]');
      if (!m) return;
      s.close();
      await db.setKv('printer', { address: m.dataset.addr, name: m.dataset.name });
      try {
        await printer.connect(m.dataset.addr);
        toast('Printer dipilih ✓  Coba "Cetak tes".', 'ok', 3500);
      } catch (err) {
        toast((err.message || err) + '', 'error', 5000);
      }
      render();
    });
  }

  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    const w = e.target.closest('[data-width]');
    const dlg = e.target.closest('[data-dialog]');
    const dc = e.target.closest('[data-del-cashier]');
    if (w) {
      await db.setKv('paperWidth', Number(w.dataset.width));
      renderPrinter();
    }
    if (dlg) {
      await db.setKv('printDialog', dlg.dataset.dialog === '1' ? true : null);
      renderPrinter();
    }
    if (dc) {
      const c = await db.get('cashiers', Number(dc.dataset.delCashier));
      if (c && (await confirmBox(`Hapus kasir ${c.name} dari komputer ini? Untuk memakai lagi harus login online.`, { ok: 'Hapus', danger: true }))) {
        await db.del('cashiers', c.id);
        if (session.cashier && session.cashier.id === c.id) session.cashier = null;
        render();
      }
    }
    if (!a) return;
    const act = a.dataset.a;
    if (act === 'back') go(backTo());
    if (act === 'test') {
      const b = busy('Menghubungi server…');
      const r = await ping();
      b.done();
      testResult = r.ok ? { ok: true, latency: r.latency, serverTime: r.data.server_time, device: r.data.device } : { ok: false, error: r.error };
      render();
    }
    if (act === 'sync') {
      const r = await syncNow({ catalog: 'force' });
      toast(r.error ? r.error.message : `Sinkron selesai: ${r.sent} terkirim${r.rejected ? `, ${r.rejected} ditolak` : ''}`, r.error ? 'error' : 'ok', 4000);
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
    if (act === 'recheck') {
      try {
        await printer.connect(db.kv('printer').address);
        toast('Printer ada ✓', 'ok');
      } catch (err) {
        toast((err.message || err) + '', 'error', 5000);
      }
      renderPrinter();
    }
    if (act === 'test-print') {
      const b = busy('Mencetak tes…');
      try {
        await printTest();
        toast('Cetak tes terkirim ke printer ✓', 'ok');
      } catch (err) {
        if (!err.cancelled) toast((err.message || err) + '', 'error', 5000);
      }
      b.done();
      renderPrinter();
    }
    if (act === 'save-receipt') {
      await db.setKv('storeName', $('#storeName').value.trim() || 'TOKO');
      await db.setKv('storeLines', $('#storeLines').value.trim());
      await db.setKv('storeFooter', $('#storeFooter').value.trim());
      toast('Isi struk disimpan ✓', 'ok');
    }
    if (act === 'open-data' && window.kasirPc) window.kasirPc.openDataFolder();
    if (act === 'repair') {
      const msg = syncInfo.pending
        ? `Masih ada ${syncInfo.pending} transaksi belum terkirim. Transaksi tetap disimpan dan akan dikirim setelah komputer ini terpasang lagi. Lanjut pasang ulang?`
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
  if (window.kasirPc) {
    window.kasirPc.info().then((i) => {
      info = i;
      if (root.isConnected) renderAbout();
    });
  }
  return {
    cleanup: () => {
      offStatus();
      offs.forEach((o) => o());
    },
    back: () => go(backTo()),
  };
}
