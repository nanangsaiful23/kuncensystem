// Layar pasang perangkat untuk PC: ketik alamat server + kode pasang dari halaman admin Kasir HP.
// (Kasir HP men-scan QR dengan kamera; di PC diketik, atau isi QR ditempel ke kolom alamat.)

import * as db from '@hp/db.js';
import { api, setToken } from '@hp/api.js';
import { go } from '@hp/router.js';
import { busy, toast, confirmBox } from '@hp/ui.js';
import { esc, normalizeServer } from '@hp/util.js';
import { startMonitor, syncInfo, ping } from '@hp/sync.js';
import { loadCatalog, loadMembers } from '@hp/catalog.js';

export function pairView(root, { canCancel = false } = {}) {
  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      ${canCancel ? '<button type="button" class="icon-btn" data-a="back" title="Kembali (Esc)">←</button>' : ''}
      <div class="tb-left"><div class="tb-title">Pasang perangkat</div><div class="tb-sub">Kasir PC</div></div>
    </header>
    <div class="content pc-narrow">
      <form class="card" id="f" autocomplete="off">
        <p>Minta admin membuka <b>Pengaturan › Kasir HP</b> di sistem toko, pilih <b>Tambah perangkat</b>
          (jenis <b>Komputer</b>) atau <b>Kode pasang</b>. Lalu ketik alamat server dan kode yang tampil di sana.</p>
        <label for="server">Alamat server</label>
        <input id="server" type="text" autocapitalize="off" autocomplete="off" spellcheck="false"
          placeholder="https://toko-anda.com" value="${esc(db.kv('server') || '')}">
        <div class="hint">Sama dengan alamat yang tampil di halaman admin ("Alamat server"). Jaringan lokal boleh http,
          mis. <code>192.168.1.5:8000</code>; alamat internet wajib https.</div>
        <label for="code">Kode pasang (6 angka)</label>
        <input id="code" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="123456" class="code-input">
        <div class="hint">Kode berlaku 15 menit dan hanya bisa dipakai sekali.</div>
        <div id="err" class="error" hidden></div>
        <button type="submit" class="btn btn-primary btn-block big">Pasang</button>
      </form>
    </div>
  </div>`;

  const $ = (s) => root.querySelector(s);
  const showErr = (m) => {
    $('#err').hidden = !m;
    $('#err').textContent = m || '';
  };

  // Isi QR dari halaman admin berbentuk {"server": "...", "pair_code": "..."}: boleh ditempel ke kolom alamat
  // (mis. dari scanner 2D USB yang bisa membaca QR).
  $('#server').addEventListener('input', () => {
    const v = $('#server').value.trim();
    if (!v.startsWith('{')) return;
    try {
      const d = JSON.parse(v);
      if (d && d.server) {
        $('#server').value = d.server;
        if (d.pair_code) $('#code').value = d.pair_code;
        $('#code').focus();
      }
    } catch (e) {
      /* belum lengkap */
    }
  });

  async function doPair(serverInput, code) {
    showErr('');
    let server;
    try {
      server = normalizeServer(serverInput);
    } catch (e) {
      return showErr(e.message);
    }
    code = String(code || '').replace(/\s/g, '');
    if (!/^\d{4,10}$/.test(code)) return showErr('Kode pasang harus berupa angka (6 digit).');

    const oldServer = db.kv('server');
    if (oldServer && oldServer !== server && syncInfo.pending > 0) {
      const ok = await confirmBox(
        `Masih ada ${syncInfo.pending} transaksi belum terkirim ke server lama (${oldServer}).\n` +
          'Kalau pindah server, transaksi itu akan dikirim ke server baru. Lanjutkan?',
        { ok: 'Tetap pasang', danger: true }
      );
      if (!ok) return;
    }

    const b = busy('Menghubungi server…');
    try {
      const r = await api.pair(server, code);
      if (!r.token || !r.device) throw new Error('Jawaban server tidak lengkap.');
      const oldDevice = db.kv('device');
      if (oldServer !== server) {
        // Server lain: data barang, member, dan akun kasir lama tidak berlaku.
        await Promise.all([db.clear('goods'), db.clear('members'), db.clear('cashiers')]);
        for (const k of ['catalogSince', 'membersSince', 'catalogAt', 'lastSyncAt', 'lastSyncError', 'cartDraft']) await db.setKv(k, null);
        await loadCatalog();
        await loadMembers();
      }
      await setToken(r.token);
      await db.setKv('server', server);
      await db.setKv('device', r.device);
      await db.setKv('pairedAt', Date.now());
      b.done();
      toast(`Terpasang sebagai ${r.device.name} (${r.device.code})`, 'ok', 3500);
      startMonitor();
      ping();
      if (oldServer === server && oldDevice && db.kv('catalogSince')) go('lock');
      else go('download');
    } catch (e) {
      b.done();
      showErr(e.message);
    }
  }

  $('#f').addEventListener('submit', (e) => {
    e.preventDefault();
    doPair($('#server').value, $('#code').value);
  });
  root.addEventListener('click', (e) => {
    const a = e.target.closest('[data-a]');
    if (a && a.dataset.a === 'back') go('pengaturan');
  });
  setTimeout(() => ($('#server').value ? $('#code') : $('#server')).focus(), 50);

  return { back: canCancel ? () => go('pengaturan') : undefined };
}
