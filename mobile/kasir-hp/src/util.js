// Fungsi kecil yang dipakai di banyak tempat.

export const esc = (s) =>
  String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/** Pembulatan 2 desimal, setara round($x, 2) di PHP untuk angka positif. */
export const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

/** 25500 -> "25.500", 1750.5 -> "1.750,5" */
export function rp(n) {
  n = Number(n) || 0;
  const neg = n < 0;
  const r = round2(Math.abs(n));
  const int = Math.floor(r);
  const dec = Math.round((r - int) * 100);
  let s = String(int).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  if (dec) s += ',' + String(dec).padStart(2, '0').replace(/0$/, '');
  return (neg ? '-' : '') + s;
}

/** Jumlah barang: 2 -> "2", 1.5 -> "1,5" */
export const qtyText = (n) => String(round2(n)).replace('.', ',');

/** Baca input angka uang (bulat): "25.000" / "25000" -> 25000 */
export function parseMoney(s) {
  const digits = String(s ?? '').replace(/[^\d]/g, '');
  return digits ? parseInt(digits, 10) : 0;
}

/** Baca input jumlah (boleh desimal, koma atau titik): "1,5" -> 1.5 */
export function parseQty(s) {
  const t = String(s ?? '').trim().replace(',', '.');
  if (!/^\d*\.?\d*$/.test(t) || t === '' || t === '.') return NaN;
  return round2(parseFloat(t));
}

export function uuidv4() {
  if (globalThis.crypto && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
  const b = new Uint8Array(16);
  crypto.getRandomValues(b);
  b[6] = (b[6] & 0x0f) | 0x40;
  b[8] = (b[8] & 0x3f) | 0x80;
  const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

const p2 = (n) => String(n).padStart(2, '0');

/** Waktu lokal HP dengan zona waktu: "2026-11-23T09:41:12+07:00" */
export function isoLocal(d = new Date()) {
  const off = -d.getTimezoneOffset();
  const sign = off >= 0 ? '+' : '-';
  const a = Math.abs(off);
  return (
    `${d.getFullYear()}-${p2(d.getMonth() + 1)}-${p2(d.getDate())}` +
    `T${p2(d.getHours())}:${p2(d.getMinutes())}:${p2(d.getSeconds())}` +
    `${sign}${p2(Math.floor(a / 60))}:${p2(a % 60)}`
  );
}

/** "YYYYMMDD" tanggal lokal */
export const ymd = (d = new Date()) => `${d.getFullYear()}${p2(d.getMonth() + 1)}${p2(d.getDate())}`;

/** "YYYY-MM-DD" tanggal lokal */
export const dayKey = (d = new Date()) => `${d.getFullYear()}-${p2(d.getMonth() + 1)}-${p2(d.getDate())}`;

export const fmtTime = (d) => (d ? `${p2(new Date(d).getHours())}:${p2(new Date(d).getMinutes())}` : '-');

export function fmtDateTime(d, withSeconds = false) {
  if (!d) return '-';
  const x = new Date(d);
  if (isNaN(x)) return String(d);
  return (
    `${p2(x.getDate())}-${p2(x.getMonth() + 1)}-${x.getFullYear()} ${p2(x.getHours())}:${p2(x.getMinutes())}` +
    (withSeconds ? `:${p2(x.getSeconds())}` : '')
  );
}

/** "baru saja", "5 menit lalu", "2 jam lalu", "3 hari lalu" */
export function ago(t) {
  if (!t) return 'belum pernah';
  const s = Math.max(0, (Date.now() - new Date(t).getTime()) / 1000);
  if (s < 60) return 'baru saja';
  if (s < 3600) return `${Math.floor(s / 60)} menit lalu`;
  if (s < 86400) return `${Math.floor(s / 3600)} jam lalu`;
  return `${Math.floor(s / 86400)} hari lalu`;
}

export function debounce(fn, ms) {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), ms);
  };
}

const enc = new TextEncoder();
const b64 = (buf) => btoa(String.fromCharCode(...new Uint8Array(buf)));

/** Hash PIN dengan PBKDF2-SHA256 (PIN tidak pernah disimpan apa adanya). */
export async function hashPin(pin, saltB64, iterations = 60000) {
  const salt = saltB64 ? Uint8Array.from(atob(saltB64), (c) => c.charCodeAt(0)) : crypto.getRandomValues(new Uint8Array(16));
  const key = await crypto.subtle.importKey('raw', enc.encode(pin), 'PBKDF2', false, ['deriveBits']);
  const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', hash: 'SHA-256', salt, iterations }, key, 256);
  return { salt: b64(salt), hash: b64(bits), iterations };
}

export function bytesToBase64(bytes) {
  let s = '';
  for (let i = 0; i < bytes.length; i += 0x8000) s += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
  return btoa(s);
}

export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Alamat lokal (LAN/emulator) boleh http; alamat internet wajib https. */
export function isLocalHost(host) {
  host = String(host || '').toLowerCase().replace(/^\[|\]$/g, '');
  if (host === 'localhost' || host === '::1' || host.endsWith('.local') || host.endsWith('.test') || host.endsWith('.localhost')) return true;
  const m = host.match(/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/);
  if (!m) return false;
  const [a, b] = [Number(m[1]), Number(m[2])];
  return a === 10 || a === 127 || (a === 192 && b === 168) || (a === 172 && b >= 16 && b <= 31) || (a === 169 && b === 254);
}

/**
 * Rapikan alamat server dari QR / ketikan:
 * "192.168.1.5:8000" -> "http://192.168.1.5:8000", "toko.com/" -> "https://toko.com"
 */
export function normalizeServer(input) {
  let s = String(input || '').trim();
  if (!s) throw new Error('Alamat server kosong.');
  if (!/^https?:\/\//i.test(s)) {
    const host = s.split(/[/:]/)[0];
    s = (isLocalHost(host) ? 'http://' : 'https://') + s;
  }
  let u;
  try {
    u = new URL(s);
  } catch (e) {
    throw new Error('Alamat server tidak valid.');
  }
  if (u.protocol === 'http:' && !isLocalHost(u.hostname)) {
    throw new Error('Alamat internet harus memakai https:// (http hanya untuk jaringan lokal).');
  }
  let path = u.pathname.replace(/\/+$/, '').replace(/\/api\/pos\/v1$/i, '').replace(/\/+$/, '');
  return `${u.protocol}//${u.host}${path}`;
}
