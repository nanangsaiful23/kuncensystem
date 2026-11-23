// Penyimpanan lokal di HP (IndexedDB). Semua transaksi disimpan di sini
// SEBELUM dikirim ke server, jadi kasir tidak pernah menunggu internet.

const DB_NAME = 'kasirhp';
const DB_VERSION = 1;

let dbp = null;

function req(r) {
  return new Promise((resolve, reject) => {
    r.onsuccess = () => resolve(r.result);
    r.onerror = () => reject(r.error);
  });
}

function done(tx) {
  return new Promise((resolve, reject) => {
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
    tx.onabort = () => reject(tx.error || new Error('Transaksi database dibatalkan'));
  });
}

export function openDb() {
  if (dbp) return dbp;
  dbp = new Promise((resolve, reject) => {
    const r = indexedDB.open(DB_NAME, DB_VERSION);
    r.onupgradeneeded = () => {
      const db = r.result;
      if (!db.objectStoreNames.contains('kv')) db.createObjectStore('kv');
      if (!db.objectStoreNames.contains('goods')) db.createObjectStore('goods', { keyPath: 'id' });
      if (!db.objectStoreNames.contains('members')) db.createObjectStore('members', { keyPath: 'id' });
      if (!db.objectStoreNames.contains('cashiers')) db.createObjectStore('cashiers', { keyPath: 'id' });
      if (!db.objectStoreNames.contains('sales')) {
        const s = db.createObjectStore('sales', { keyPath: 'client_uuid' });
        s.createIndex('status', 'status');
        s.createIndex('sold_at', 'sold_at');
        s.createIndex('day', 'day');
      }
    };
    r.onsuccess = () => resolve(r.result);
    r.onerror = () => reject(r.error);
    r.onblocked = () => reject(new Error('Database sedang dipakai'));
  });
  // Minta browser tidak menghapus data saat memori penuh.
  try {
    navigator.storage && navigator.storage.persist && navigator.storage.persist();
  } catch (e) {
    /* abaikan */
  }
  return dbp;
}

export async function get(store, key) {
  const db = await openDb();
  return req(db.transaction(store).objectStore(store).get(key));
}

export async function all(store) {
  const db = await openDb();
  return req(db.transaction(store).objectStore(store).getAll());
}

export async function allByIndex(store, index, value) {
  const db = await openDb();
  return req(db.transaction(store).objectStore(store).index(index).getAll(value));
}

export async function count(store, index, value) {
  const db = await openDb();
  const os = db.transaction(store).objectStore(store);
  return req(index ? os.index(index).count(value) : os.count());
}

export async function put(store, value, key) {
  const db = await openDb();
  const tx = db.transaction(store, 'readwrite');
  tx.objectStore(store).put(value, key);
  return done(tx);
}

export async function del(store, key) {
  const db = await openDb();
  const tx = db.transaction(store, 'readwrite');
  tx.objectStore(store).delete(key);
  return done(tx);
}

export async function clear(store) {
  const db = await openDb();
  const tx = db.transaction(store, 'readwrite');
  tx.objectStore(store).clear();
  return done(tx);
}

/** Simpan banyak & hapus banyak dalam satu transaksi database. */
export async function bulk(store, puts = [], deletes = [], clearFirst = false) {
  const db = await openDb();
  const tx = db.transaction(store, 'readwrite');
  const os = tx.objectStore(store);
  if (clearFirst) os.clear();
  for (const v of puts) os.put(v);
  for (const k of deletes) os.delete(k);
  return done(tx);
}

/** Jalankan fn(objectStores) dalam satu transaksi readwrite (atomik). */
export async function atomic(stores, fn) {
  const db = await openDb();
  const tx = db.transaction(stores, 'readwrite');
  const os = {};
  for (const s of stores) os[s] = tx.objectStore(s);
  const result = await fn(os, req);
  await done(tx);
  return result;
}

// ---- Pengaturan (key-value) ----
const kvCache = new Map();

export async function loadKv() {
  const db = await openDb();
  const tx = db.transaction('kv');
  const os = tx.objectStore('kv');
  const keys = await req(os.getAllKeys());
  const vals = await req(os.getAll());
  keys.forEach((k, i) => kvCache.set(k, vals[i]));
}

export const kv = (key, def = null) => (kvCache.has(key) ? kvCache.get(key) : def);

export async function setKv(key, value) {
  kvCache.set(key, value);
  if (value === null || value === undefined) {
    kvCache.delete(key);
    return del('kv', key);
  }
  return put('kv', value, key);
}
