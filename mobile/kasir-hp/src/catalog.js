// Data barang & member di memori HP untuk pencarian cepat (tanpa internet).

import * as db from './db.js';

let goods = []; // [{id, code, name, units, _s}]
let byId = new Map();
let byCode = new Map();
let members = [];
let membersById = new Map();

function indexGood(g) {
  g.name = String(g.name == null ? '' : g.name).trim() || '(tanpa nama)';
  g.code = g.code == null ? '' : String(g.code).trim();
  g._s = (g.name + ' ' + g.code).toLowerCase();
  return g;
}

export async function loadCatalog() {
  const list = await db.all('goods');
  goods = list.filter((g) => g.is_active !== false).map(indexGood);
  goods.sort((a, b) => a.name.localeCompare(b.name));
  byId = new Map(goods.map((g) => [g.id, g]));
  byCode = new Map();
  for (const g of goods) if (g.code) byCode.set(String(g.code).trim().toLowerCase(), g);
}

export async function loadMembers() {
  const list = await db.all('members');
  // Member "Non Member" (ID 1 di server) sama dengan tanpa member; tidak perlu dipilih.
  for (const m of list) m.name = String(m.name == null ? '' : m.name).trim() || 'Member #' + m.id;
  members = list.filter((m) => m.is_active !== false && !/^non[\s-]?member$/i.test(m.name));
  members.sort((a, b) => a.name.localeCompare(b.name));
  membersById = new Map(members.map((m) => [m.id, m]));
}

export const goodsCount = () => goods.length;
export const membersCount = () => members.length;
export const goodById = (id) => byId.get(id);
export const memberById = (id) => membersById.get(id);

/** Cari barang dengan kode/barcode persis (hasil scan). */
export function findByCode(code) {
  return byCode.get(String(code || '').trim().toLowerCase()) || null;
}

/** Cari barang: semua kata harus ada di nama/kode. Kode persis tampil paling atas. */
export function searchGoods(q, limit = 40) {
  q = String(q || '').trim().toLowerCase();
  if (!q) return [];
  const exact = byCode.get(q);
  const words = q.split(/\s+/).filter(Boolean);
  const out = [];
  for (const g of goods) {
    if (g === exact) continue;
    let ok = true;
    for (const w of words) {
      if (!g._s.includes(w)) {
        ok = false;
        break;
      }
    }
    if (ok) out.push(g);
  }
  const first = words[0];
  out.sort((a, b) => {
    const as = a._s.startsWith(first) ? 0 : 1;
    const bs = b._s.startsWith(first) ? 0 : 1;
    return as - bs || a.name.length - b.name.length;
  });
  const res = out.slice(0, limit);
  if (exact) res.unshift(exact);
  return res;
}

export function searchMembers(q, limit = 50) {
  q = String(q || '').trim().toLowerCase();
  if (!q) return members.slice(0, limit);
  const words = q.split(/\s+/).filter(Boolean);
  return members
    .filter((m) => {
      const s = `${m.name} ${m.store_name || ''} ${m.phone_number || ''} ${m.address || ''}`.toLowerCase();
      return words.every((w) => s.includes(w));
    })
    .slice(0, limit);
}
