// Status sambungan (ping berkala) dan sinkronisasi:
//  - kirim transaksi yang belum terkirim (maks. 20 per kiriman)
//  - unduh perubahan katalog & member (pakai ?since=server_time terakhir)
//  - transaksi yang dihapus/dibatalkan admin di web ikut dihapus dari riwayat di sini
//  - transaksi yang diubah admin di web ikut diperbarui isinya di sini

import { App } from '@capacitor/app';
import { api } from './api.js';
import * as db from './db.js';
import { bus } from './bus.js';
import { loadCatalog, loadMembers } from './catalog.js';
import { mergeServerSale } from './edits.js';

export const SLOW_MS = 2000; // ping lebih lama dari ini = "Lambat"
export const MAX_AGE_DAYS = 7; // server menolak transaksi lebih lama dari ini
export const WARN_AGE_DAYS = 5; // mulai mengingatkan kasir
const PING_EVERY_MS = 15000;
const CATALOG_EVERY_MS = 10 * 60 * 1000;
const CANCEL_EVERY_MS = 2 * 60 * 1000; // cek transaksi yang dihapus/diubah admin di web
const BATCH = 20;

/** state: unknown | online | slow | offline | denied */
export const conn = {
  state: 'unknown',
  latency: null,
  checkedAt: null,
  error: null,
  errorKind: null,
  detail: '',
  serverTime: null,
  skewMs: null,
};

export const syncInfo = {
  pending: 0,
  rejected: 0,
  oldestPendingAt: null,
  running: false,
};

function setConn(patch) {
  Object.assign(conn, patch);
  bus.emit('conn', conn);
}

export async function ping() {
  const t0 = Date.now();
  try {
    const r = await api.ping(8000);
    const latency = Date.now() - t0;
    const serverTs = Date.parse(r.server_time);
    const skewMs = isNaN(serverTs) ? null : Math.round(Date.now() - latency / 2 - serverTs);
    if (r.device && JSON.stringify(r.device) !== JSON.stringify(db.kv('device'))) {
      await db.setKv('device', r.device);
      bus.emit('device', r.device);
    }
    setConn({
      state: latency > SLOW_MS ? 'slow' : 'online',
      latency,
      checkedAt: Date.now(),
      error: null,
      errorKind: null,
      detail: '',
      serverTime: r.server_time,
      skewMs,
    });
    return { ok: true, latency, data: r };
  } catch (e) {
    const denied = e.kind === 'device_unknown' || e.kind === 'device_inactive';
    setConn({
      state: denied ? 'denied' : 'offline',
      latency: null,
      checkedAt: Date.now(),
      error: e.message,
      errorKind: e.kind,
      detail: e.detail || '',
    });
    return { ok: false, error: e };
  }
}

export async function refreshCounts() {
  const pending = await db.allByIndex('sales', 'status', 'pending');
  syncInfo.pending = pending.length;
  syncInfo.rejected = await db.count('sales', 'status', 'rejected');
  syncInfo.oldestPendingAt = pending.reduce((min, s) => (!min || s.sold_at < min ? s.sold_at : min), null);
  bus.emit('sync', syncInfo);
}

/** Umur transaksi belum terkirim yang paling lama, dalam hari. */
export function oldestPendingDays() {
  if (!syncInfo.oldestPendingAt) return 0;
  return (Date.now() - Date.parse(syncInfo.oldestPendingAt)) / 86400000;
}

const toPayload = (s) => ({
  client_uuid: s.client_uuid,
  receipt_no: s.receipt_no,
  sold_at: s.sold_at,
  cashier_id: s.cashier_id,
  member_id: s.member_id || null,
  payment: s.payment,
  items: s.items.map((i) => ({ good_unit_id: i.good_unit_id, quantity: i.quantity, price: i.price, discount: i.discount || 0 })),
  total_discount: s.total_discount || 0,
  total: s.total,
  money_paid: s.money_paid,
  money_returned: s.money_returned,
  note: s.note || null,
});

let running = null;

/** Ada transaksi yang dihapus/diubah admin di web pada sinkron ini. */
export const adminChanged = (r) => !!(r && (r.removed || r.edited));

/** Tambahan untuk ringkasan "Sinkron selesai" (kalau tidak, pemberitahuan hapus/ubah tertimpa). */
export function adminNote(r) {
  const bits = [];
  if (r && r.removed) bits.push(`${r.removed} transaksi dihapus`);
  if (r && r.edited) bits.push(`${r.edited}${bits.length ? '' : ' transaksi'} diubah`);
  return bits.length ? ` — ${bits.join(' & ')} admin di web` : '';
}

/**
 * Sinkronkan sekarang. catalog: 'auto' (bila sudah waktunya) | 'force' (selalu cek perubahan) | 'full' | 'none'.
 * Hasil: { sent, rejected, errors, removed, edited, error }
 */
export function syncNow(opts = {}) {
  if (running) return running;
  running = doSync(opts).finally(() => {
    running = null;
    syncInfo.running = false;
    bus.emit('sync', syncInfo);
  });
  return running;
}

async function doSync({ catalog = 'auto' } = {}) {
  syncInfo.running = true;
  bus.emit('sync', syncInfo);
  const result = { sent: 0, rejected: 0, errors: 0, removed: 0, edited: 0, error: null, contacted: false };
  const tried = new Set();
  try {
    for (;;) {
      const pending = (await db.allByIndex('sales', 'status', 'pending'))
        .filter((s) => !tried.has(s.client_uuid))
        .sort((a, b) => (a.sold_at < b.sold_at ? -1 : a.sold_at > b.sold_at ? 1 : 0));
      const batch = pending.slice(0, BATCH);
      if (!batch.length) break;

      const res = await api.transactions(batch.map(toPayload));
      result.contacted = true;
      const results = Array.isArray(res.results) ? res.results : [];
      const byUuid = new Map(results.map((r) => [r.client_uuid, r]));
      const now = Date.now();
      const gone = []; // sudah ada di server tetapi sudah dihapus admin di web
      batch.forEach((s, i) => {
        tried.add(s.client_uuid);
        const r = byUuid.get(s.client_uuid) || results[i];
        s.attempts = (s.attempts || 0) + 1;
        s.last_attempt_at = now;
        if (r && r.status === 'duplicate' && r.cancelled) {
          gone.push(s);
        } else if (r && (r.status === 'saved' || r.status === 'duplicate')) {
          s.status = 'sent';
          s.transaction_id = r.transaction_id;
          s.sent_at = now;
          s.message = null;
          result.sent++;
        } else if (r && r.status === 'rejected') {
          s.status = 'rejected'; // tidak dikirim ulang otomatis
          s.message = r.message || 'Ditolak server';
          s.rejected_at = now;
          result.rejected++;
        } else {
          s.message = (r && r.message) || 'Gangguan server, akan dicoba lagi.';
          result.errors++;
        }
      });
      await db.bulk('sales', batch.filter((s) => !gone.includes(s)), gone.map((s) => s.client_uuid));
      if (gone.length) {
        result.removed += gone.length;
        bus.emit('removed', gone);
      }
      await refreshCounts();
    }

    if (catalog === 'force' || catalog === 'full' || Date.now() - (db.kv('cancelAt') || 0) > CANCEL_EVERY_MS) {
      result.removed += (await pullCancelled()).length;
      result.edited += (await pullEdited()).length;
      result.contacted = true;
    }

    if (catalog !== 'none') {
      const due = Date.now() - (db.kv('catalogAt') || 0) > CATALOG_EVERY_MS;
      if (catalog === 'force' || catalog === 'full' || due) {
        await pullData(catalog === 'full');
        result.contacted = true;
      }
    }

    if (result.contacted || conn.state === 'online' || conn.state === 'slow') {
      await db.setKv('lastSyncAt', Date.now());
    }
    await db.setKv('lastSyncError', result.errors ? `${result.errors} transaksi gagal karena gangguan server, akan dicoba lagi.` : null);
    if (result.contacted && conn.state !== 'online' && conn.state !== 'slow') ping();
  } catch (e) {
    result.error = e;
    await db.setKv('lastSyncError', e.message);
    if (e.kind === 'device_unknown' || e.kind === 'device_inactive') {
      setConn({ state: 'denied', error: e.message, errorKind: e.kind, checkedAt: Date.now() });
    } else if (e.kind === 'network' || e.kind === 'timeout') {
      setConn({ state: 'offline', error: e.message, errorKind: e.kind, detail: e.detail || '', checkedAt: Date.now() });
    }
  }
  await refreshCounts();
  bus.emit('synced', result);
  return result;
}

/**
 * Hapus dari riwayat di sini transaksi yang sudah dihapus/dibatalkan admin di web.
 * Server lama (belum punya fitur ini) atau gangguan server tidak menghentikan sinkron;
 * dicoba lagi pada pengecekan berikutnya. Hasil: daftar transaksi yang dihapus.
 */
export async function pullCancelled() {
  let r;
  try {
    r = await api.cancelled(db.kv('cancelSince'));
  } catch (e) {
    if (['network', 'timeout', 'device_unknown', 'device_inactive'].includes(e.kind)) throw e;
    console.warn('Cek transaksi dihapus admin gagal:', e.message);
    await db.setKv('cancelAt', Date.now());
    return [];
  }
  const removed = [];
  for (const c of r.cancelled || []) {
    const s = c && c.client_uuid ? await db.get('sales', c.client_uuid) : null;
    if (s) removed.push(s);
  }
  if (removed.length) {
    await db.bulk('sales', [], removed.map((s) => s.client_uuid));
    bus.emit('removed', removed);
    await refreshCounts();
  }
  if (r.server_time) await db.setKv('cancelSince', r.server_time);
  await db.setKv('cancelAt', Date.now());
  return removed;
}

/**
 * Perbarui isi transaksi di riwayat yang diubah admin di web (jumlah barang, member,
 * catatan, uang bayar). Server mengirim isi terbaru transaksi HP/PC yang berubah
 * (hanya yang dijual hari ini & kemarin); yang isinya sama dengan di sini dilewati.
 * Seperti pullCancelled(): server lama/gangguan server tidak menghentikan sinkron.
 * Hasil: daftar transaksi yang diperbarui.
 */
export async function pullEdited() {
  let r;
  try {
    r = await api.changed(db.kv('editSince'));
  } catch (e) {
    if (['network', 'timeout', 'device_unknown', 'device_inactive'].includes(e.kind)) throw e;
    console.warn('Cek transaksi diubah admin gagal:', e.message);
    return [];
  }
  const list = (r.changed || []).filter((c) => c && c.client_uuid);
  // Baca & tulis dalam satu transaksi database: cetak ulang struk (jumlah cetak) tidak tertimpa.
  const edited = list.length
    ? await db.atomic(['sales'], async (os, req) => {
        const out = [];
        for (const c of list) {
          const s = await req(os.sales.get(c.client_uuid));
          const next = s && s.status !== 'rejected' ? mergeServerSale(s, c) : null;
          if (next) {
            os.sales.put(next);
            out.push(next);
          }
        }
        return out;
      })
    : [];
  if (edited.length) {
    bus.emit('edited', edited);
    await refreshCounts();
  }
  if (r.server_time) await db.setKv('editSince', r.server_time);
  return edited;
}

/** Unduh katalog & member. full=true: ambil semua dari awal. */
export async function pullData(full = false, onProgress = () => {}) {
  onProgress('Mengunduh data barang…');
  const c = await api.catalog(full ? null : db.kv('catalogSince'));
  const puts = [];
  const dels = [];
  for (const g of c.goods || []) {
    if (g.is_active === false) dels.push(g.id);
    else
      puts.push({
        id: g.id,
        code: g.code,
        name: g.name,
        is_active: true,
        is_discontinued: !!g.is_discontinued,
        stock_estimate: g.stock_estimate,
        // Satuan terkecil dulu (dipakai sebagai satuan bawaan saat scan/tap).
        units: (g.units || [])
          .map((u) => ({
            good_unit_id: u.good_unit_id,
            unit: u.unit || u.unit_name || 'PCS',
            unit_name: u.unit_name,
            quantity: Number(u.quantity) || 1,
            selling_price: Number(u.selling_price) || 0,
          }))
          .sort((a, b) => a.quantity - b.quantity),
      });
  }
  onProgress(`Menyimpan ${puts.length} barang…`);
  await db.bulk('goods', puts, dels, !!c.full);
  await db.setKv('catalogSince', c.server_time);

  onProgress('Mengunduh data member…');
  const m = await api.members(full ? null : db.kv('membersSince'));
  const mPuts = [];
  const mDels = [];
  for (const x of m.members || []) {
    if (x.is_active === false) mDels.push(x.id);
    else mPuts.push(x);
  }
  await db.bulk('members', mPuts, mDels, !!m.full);
  await db.setKv('membersSince', m.server_time);
  await db.setKv('catalogAt', Date.now());
  await db.setKv('lastSyncAt', Date.now());

  await loadCatalog();
  await loadMembers();
  bus.emit('catalog');
  return { goods: puts.length, removed: dels.length, members: mPuts.length, full: !!c.full };
}

let started = false;
let timer = null;

async function tick() {
  const r = await ping();
  if (!r.ok) return;
  await refreshCounts();
  const catalogDue = Date.now() - (db.kv('catalogAt') || 0) > CATALOG_EVERY_MS;
  const cancelDue = Date.now() - (db.kv('cancelAt') || 0) > CANCEL_EVERY_MS;
  if (syncInfo.pending > 0 || catalogDue || cancelDue) syncNow({ catalog: 'auto' });
}

/** Mulai cek sambungan berkala + sinkron otomatis selama aplikasi terbuka. */
export function startMonitor() {
  if (started) return;
  started = true;
  tick();
  timer = setInterval(() => {
    if (document.visibilityState !== 'hidden') tick();
  }, PING_EVERY_MS);
  window.addEventListener('online', tick);
  try {
    App.addListener('appStateChange', ({ isActive }) => {
      if (isActive) tick();
    });
  } catch (e) {
    /* browser */
  }
}

export function stopMonitor() {
  started = false;
  clearInterval(timer);
  window.removeEventListener('online', tick);
}

/** Dipanggil setelah transaksi baru disimpan: kirim segera bila online. */
export async function kick() {
  await refreshCounts();
  if (conn.state === 'online' || conn.state === 'slow') syncNow({ catalog: 'none' });
  else tick();
}

/** Hapus transaksi terkirim yang sudah lama supaya HP tidak penuh. */
export async function pruneOldSales(days = 60) {
  const cutoff = Date.now() - days * 86400000;
  const sent = await db.allByIndex('sales', 'status', 'sent');
  const old = sent.filter((s) => Date.parse(s.sold_at) < cutoff).map((s) => s.client_uuid);
  if (old.length) await db.bulk('sales', [], old);
}
