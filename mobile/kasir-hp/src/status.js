// Penanda status yang tampil di layar utama: sambungan, sinkron, printer, peringatan.

import * as db from './db.js';
import { bus } from './bus.js';
import { conn, syncInfo, syncNow, removedNote, oldestPendingDays, WARN_AGE_DAYS, MAX_AGE_DAYS } from './sync.js';
import { printer } from './native.js';
import { esc, fmtTime, ago } from './util.js';
import { toast } from './ui.js';

export const CONN_LABEL = {
  unknown: ['Cek…', 'b-grey'],
  online: ['Online', 'b-green'],
  slow: ['Lambat', 'b-yellow'],
  offline: ['Offline', 'b-red'],
  denied: ['Ditolak', 'b-red'],
};

export function connBadgeHtml() {
  const [label, cls] = CONN_LABEL[conn.state] || CONN_LABEL.unknown;
  const ms = conn.latency != null && (conn.state === 'online' || conn.state === 'slow') ? `<small>${conn.latency} ms</small>` : '';
  return `<span class="badge ${cls}"><i class="dot"></i>${label}${ms}</span>`;
}

export function printerBadgeHtml() {
  const saved = db.kv('printer');
  const st = printer.state();
  if (!saved) return `<span class="pbadge p-none">🖨 belum dipilih</span>`;
  return st.connected
    ? `<span class="pbadge p-ok">🖨 tersambung</span>`
    : `<span class="pbadge p-off">🖨 tidak tersambung</span>`;
}

export function syncStripHtml() {
  const last = db.kv('lastSyncAt');
  const lastTxt = last ? `${fmtTime(last)} (${ago(last)})` : 'belum pernah';
  const pend = syncInfo.pending;
  const main = pend
    ? `<span class="s-pend">⏳ <b>${pend}</b> belum terkirim</span>`
    : `<span class="s-ok">✓ Semua terkirim</span>`;
  const rej = syncInfo.rejected ? ` · <a href="#" data-go="history-rejected" class="s-rej">✗ ${syncInfo.rejected} ditolak</a>` : '';
  return `
    <div class="strip-text">${main}${rej}<div class="muted small">Sinkron terakhir: ${esc(lastTxt)}</div></div>
    <button type="button" class="btn btn-sm ${syncInfo.running ? 'is-busy' : ''}" data-act="sync" ${syncInfo.running ? 'disabled' : ''}>
      ${syncInfo.running ? 'Mengirim…' : 'Sinkronkan'}
    </button>`;
}

export function alertsHtml() {
  const out = [];
  const days = oldestPendingDays();
  if (syncInfo.pending && days >= WARN_AGE_DAYS) {
    out.push(`<div class="alert a-red">⚠ Ada transaksi belum terkirim sejak <b>${Math.floor(days)} hari</b> lalu.
      Segera sambungkan internet dan tekan Sinkronkan. Server menolak transaksi yang lebih lama dari ${MAX_AGE_DAYS} hari.</div>`);
  }
  if (conn.state === 'denied') {
    out.push(`<div class="alert a-red">⛔ ${esc(conn.error)} <a href="#" data-go="settings">Buka Pengaturan</a></div>`);
  }
  if (conn.skewMs != null && Math.abs(conn.skewMs) > 5 * 60000) {
    const min = Math.round(Math.abs(conn.skewMs) / 60000);
    out.push(`<div class="alert a-yellow">🕒 Jam HP ${conn.skewMs > 0 ? 'lebih cepat' : 'lebih lambat'} ${min} menit dari server.
      Atur tanggal & jam otomatis di Pengaturan Android supaya waktu transaksi benar.</div>`);
  }
  const err = db.kv('lastSyncError');
  if (err && syncInfo.pending && conn.state !== 'offline' && conn.state !== 'denied') {
    out.push(`<div class="alert a-yellow">${esc(err)}</div>`);
  }
  return out.join('');
}

/**
 * Pasang penanda status pada elemen dengan atribut data-status="conn|printer|strip|alerts" di dalam root.
 * navigate(name) dipanggil saat kasir menekan tautan. Mengembalikan fungsi pembersih.
 */
export function mountStatus(root, navigate) {
  const render = () => {
    root.querySelectorAll('[data-status="conn"]').forEach((el) => (el.innerHTML = connBadgeHtml()));
    root.querySelectorAll('[data-status="printer"]').forEach((el) => (el.innerHTML = printerBadgeHtml()));
    root.querySelectorAll('[data-status="strip"]').forEach((el) => (el.innerHTML = syncStripHtml()));
    root.querySelectorAll('[data-status="alerts"]').forEach((el) => (el.innerHTML = alertsHtml()));
  };
  render();
  const offs = [bus.on('conn', render), bus.on('sync', render), bus.on('device', render), printer.onState(render)];
  const timer = setInterval(render, 30000); // perbarui tulisan "x menit lalu"

  const onClick = async (e) => {
    const go = e.target.closest('[data-go]');
    if (go) {
      e.preventDefault();
      navigate(go.dataset.go);
      return;
    }
    const act = e.target.closest('[data-act="sync"]');
    if (act) {
      const r = await syncNow({ catalog: 'force' });
      if (r.error) toast(r.error.message, 'error', 4000);
      else if (r.rejected) toast(`${r.sent} terkirim, ${r.rejected} ditolak server`, 'error', 4000);
      else if (r.errors) toast(`${r.sent} terkirim, ${r.errors} gagal (dicoba lagi nanti)`, 'warn', 4000);
      else if (r.removed) toast((r.sent ? `${r.sent} transaksi terkirim ✓` : 'Sinkron selesai ✓') + removedNote(r), 'warn', 5000);
      else toast(r.sent ? `${r.sent} transaksi terkirim ✓` : 'Sinkron selesai ✓', 'ok');
    }
  };
  root.addEventListener('click', onClick);
  return () => {
    offs.forEach((off) => off());
    clearInterval(timer);
    root.removeEventListener('click', onClick);
  };
}
