// Layar: pasang perangkat (scan QR dari halaman admin atau ketik alamat + kode).

import * as db from '../db.js';
import { api, setToken } from '../api.js';
import { go } from '../router.js';
import { busy, toast, confirmBox } from '../ui.js';
import { esc, normalizeServer } from '../util.js';
import { scan } from '../scanner.js';
import { startMonitor, syncInfo, ping } from '../sync.js';
import { loadCatalog, loadMembers } from '../catalog.js';

export function pairView(root, { canCancel = false } = {}) {
  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      ${canCancel ? '<button type="button" class="icon-btn" data-a="back">←</button>' : ''}
      <div class="tb-left"><div class="tb-title">Pasang perangkat</div><div class="tb-sub">Kasir HP</div></div>
    </header>
    <div class="content">
      <div class="card">
        <p>Minta admin membuka <b>Pengaturan › Kasir HP</b> di komputer toko, pilih
        <b>Tambah perangkat</b> (atau <b>Kode pasang baru</b>), lalu scan QR-nya di sini.</p>
        <button type="button" class="btn btn-primary btn-block big" data-a="scan">📷 Scan QR dari halaman admin</button>
      </div>
      <div class="card">
        <div class="card-title">Atau ketik manual</div>
        <label for="server">Alamat server</label>
        <input id="server" type="url" inputmode="url" autocapitalize="off" autocomplete="off" spellcheck="false"
          placeholder="https://toko-anda.com" value="${esc(db.kv('server') || '')}">
        <div class="hint">Jaringan lokal boleh http, mis. <code>192.168.1.5:8000</code>. Alamat internet wajib https.</div>
        <label for="code">Kode pasang (6 angka)</label>
        <input id="code" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="123456" class="code-input">
        <div class="hint">Kode berlaku 15 menit dan hanya bisa dipakai sekali.</div>
        <div id="err" class="error" hidden></div>
        <button type="button" class="btn btn-primary btn-block" data-a="pair">Pasang</button>
      </div>
    </div>
  </div>`;

  const $ = (s) => root.querySelector(s);
  const showErr = (m) => {
    $('#err').hidden = !m;
    $('#err').textContent = m || '';
  };

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
        for (const k of ['catalogSince', 'membersSince', 'catalogAt', 'cancelSince', 'cancelAt', 'lastSyncAt', 'lastSyncError', 'cartDraft']) await db.setKv(k, null);
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
      ping(); // status sambungan langsung diperbarui (mis. dari "Ditolak" ke "Online")
      if (oldServer === server && oldDevice && db.kv('catalogSince')) go('lock');
      else go('download');
    } catch (e) {
      b.done();
      showErr(e.message);
    }
  }

  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    if (!a) return;
    if (a.dataset.a === 'back') go('pengaturan');
    if (a.dataset.a === 'pair') doPair($('#server').value, $('#code').value);
    if (a.dataset.a === 'scan') {
      try {
        await scan({
          title: 'Scan QR di halaman admin Kasir HP',
          onCode: (text) => {
            let data = null;
            try {
              data = JSON.parse(text);
            } catch (x) {
              /* bukan JSON */
            }
            if (data && data.server && data.pair_code) {
              $('#server').value = data.server;
              $('#code').value = data.pair_code;
              setTimeout(() => doPair(data.server, data.pair_code), 50);
              return { ok: true };
            }
            if (/^\d{6}$/.test(text.trim())) {
              $('#code').value = text.trim();
              return { ok: true };
            }
            return { ok: false, text: 'Bukan QR Kasir HP. Scan QR di halaman admin Kasir HP.' };
          },
        });
      } catch (err) {
        showErr(err.message);
      }
    }
  });

  return { back: canCancel ? () => go('pengaturan') : undefined };
}
