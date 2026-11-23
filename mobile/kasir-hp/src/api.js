// Panggilan ke server Laravel: /api/pos/v1/*
// Di HP memakai HTTP native (CapacitorHttp): tidak kena CORS dan bisa diberi batas waktu.

import { Capacitor, CapacitorHttp } from '@capacitor/core';
import { kv } from './db.js';
import { secret } from './native.js';

export const API_PREFIX = '/api/pos/v1';

/** Jenis kesalahan supaya pesan ke kasir jelas. */
export class ApiError extends Error {
  constructor(kind, message, status = 0, data = null, detail = '') {
    super(message);
    this.kind = kind; // network | timeout | device_unknown | device_inactive | invalid | rate | server | notfound | badresponse
    this.status = status;
    this.data = data;
    this.detail = detail;
  }
}

let tokenCache = null;
export async function getToken() {
  if (tokenCache === null) tokenCache = (await secret.get('device_token')) || '';
  return tokenCache;
}
export async function setToken(t) {
  tokenCache = t || '';
  if (t) await secret.set('device_token', t);
  else await secret.remove('device_token');
}

function messageFor(status, data, path) {
  const serverMsg = data && typeof data === 'object' && data.message ? String(data.message) : '';
  const code = data && typeof data === 'object' ? data.code : null;
  if (status === 401) {
    return new ApiError('device_unknown', 'Perangkat belum terdaftar di server (401). Pasang ulang HP dengan kode dari admin.', status, data);
  }
  if (status === 403) {
    if (code === 'device_inactive' || !path.startsWith('/login')) {
      return new ApiError('device_inactive', 'Perangkat ini dinonaktifkan admin (403). Hubungi admin toko.', status, data);
    }
    return new ApiError('invalid', serverMsg || 'Akses ditolak.', status, data);
  }
  if (status === 404) {
    return new ApiError('notfound', 'API Kasir HP tidak ditemukan di alamat ini (404). Cek alamat server, atau server belum diperbarui.', status, data);
  }
  if (status === 419 || status === 405) {
    return new ApiError('notfound', `Alamat server sepertinya salah (${status}).`, status, data);
  }
  if (status === 422) {
    const errs = data && data.errors ? Object.values(data.errors).flat().join(' ') : '';
    return new ApiError('invalid', serverMsg || errs || 'Data ditolak server.', status, data);
  }
  if (status === 429) {
    return new ApiError('rate', 'Terlalu banyak percobaan. Tunggu 1 menit lalu coba lagi.', status, data);
  }
  if (status >= 500) {
    return new ApiError('server', `Server sedang bermasalah (${status}). Coba lagi nanti.`, status, data);
  }
  return new ApiError('server', serverMsg || `Jawaban server tidak dikenal (${status}).`, status, data);
}

function networkError(e, url) {
  const raw = String((e && (e.message || e.errorMessage)) || e || '');
  const host = (() => {
    try {
      return new URL(url).hostname;
    } catch (x) {
      return '';
    }
  })();
  let msg = 'Server tidak terjangkau. Cek internet/Wi-Fi HP dan alamat server.';
  if (/cleartext/i.test(raw)) msg = 'HP menolak koneksi http ke alamat ini. Pakai https:// atau alamat IP lokal.';
  if (host === 'localhost' || host === '127.0.0.1') {
    msg += ' Catatan: "localhost" di HP berarti HP itu sendiri; pakai alamat IP komputer server (mis. http://192.168.1.5:8000).';
  }
  if (/time ?out|timed out/i.test(raw)) {
    return new ApiError('timeout', 'Server tidak menjawab (waktu habis). Internet lambat atau server sibuk.', 0, null, raw);
  }
  return new ApiError('network', msg, 0, null, raw);
}

/**
 * request('GET', '/ping') -> data JSON.
 * opts: { data, query, timeout (ms), server, token, auth (default true) }
 */
export async function request(method, path, opts = {}) {
  const server = opts.server || kv('server');
  if (!server) throw new ApiError('device_unknown', 'HP belum dipasang ke server.');
  let url = server + API_PREFIX + path;
  if (opts.query) {
    const q = Object.entries(opts.query)
      .filter(([, v]) => v !== null && v !== undefined && v !== '')
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
      .join('&');
    if (q) url += (url.includes('?') ? '&' : '?') + q;
  }
  const headers = { Accept: 'application/json' };
  if (opts.data !== undefined) headers['Content-Type'] = 'application/json';
  if (opts.auth !== false) {
    const token = opts.token || (await getToken());
    if (token) headers.Authorization = 'Bearer ' + token;
  }
  const timeout = opts.timeout || 15000;

  let status;
  let data;
  if (Capacitor.isNativePlatform()) {
    let res;
    try {
      res = await CapacitorHttp.request({
        method,
        url,
        headers,
        data: opts.data,
        connectTimeout: Math.min(timeout, 10000),
        readTimeout: timeout,
        responseType: 'json',
        shouldEncodeUrlParams: false,
      });
    } catch (e) {
      throw networkError(e, url);
    }
    status = res.status;
    data = res.data;
    if (typeof data === 'string') {
      try {
        data = JSON.parse(data);
      } catch (e) {
        /* bukan JSON */
      }
    }
  } else {
    // Mode uji di browser komputer.
    const ctl = new AbortController();
    const timer = setTimeout(() => ctl.abort(), timeout);
    let res;
    try {
      res = await fetch(url, {
        method,
        headers,
        body: opts.data !== undefined ? JSON.stringify(opts.data) : undefined,
        signal: ctl.signal,
      });
      status = res.status;
      const text = await res.text();
      try {
        data = JSON.parse(text);
      } catch (e) {
        data = text;
      }
    } catch (e) {
      throw networkError(ctl.signal.aborted ? new Error('timeout') : e, url);
    } finally {
      clearTimeout(timer);
    }
  }

  if (status >= 200 && status < 300) {
    if (!data || typeof data !== 'object') {
      const snippet = typeof data === 'string' ? data.slice(0, 120) : String(data);
      throw new ApiError(
        'badresponse',
        'Jawaban server bukan data Kasir HP. Cek alamat server (atau Wi-Fi minta login).',
        status,
        data,
        `${path} ${status} ${typeof data} [${typeof data === 'string' ? data.length : '-'}] ${snippet}`
      );
    }
    return data;
  }
  throw messageFor(status, data, path);
}

export const api = {
  pair: (server, pairCode) => request('POST', '/pair', { server, data: { pair_code: pairCode }, auth: false, timeout: 20000 }),
  ping: (timeout = 8000) => request('GET', '/ping', { timeout }),
  login: (email, password) => request('POST', '/login', { data: { email, password }, timeout: 20000 }),
  catalog: (since) => request('GET', '/catalog', { query: { since }, timeout: 120000 }),
  members: (since) => request('GET', '/members', { query: { since }, timeout: 60000 }),
  transactions: (list) => request('POST', '/transactions', { data: { transactions: list }, timeout: 60000 }),
  cancelled: (since) => request('GET', '/transactions/cancelled', { query: { since }, timeout: 30000 }),
};
