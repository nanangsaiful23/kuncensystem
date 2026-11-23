// Harga satuan besar otomatis ("harga grosir"), sama dengan kasir web
// (resources/views/layout/transaction/unit-tier.blade.php).
//
// Barang dengan beberapa satuan, mis. PCS 3.500 dan PAK5 15.000 (3.000 per pcs).
// Kasir memilih PCS dan mengisi jumlah 6: baris tetap "PCS x 6", tetapi totalnya
// 1 PAK5 + 1 PCS = 18.500 (bukan 6 x 3.500 = 21.000). Saat disimpan, baris itu dipecah
// menjadi satu item per satuan (PAK5 x 1 dan PCS x 1) supaya stok, HPP, dan struk tetap per satuan.
//
// Aturan (sama dengan web):
//  - satuan besar dipakai hanya bila harga per isinya lebih murah;
//  - tidak pernah memecah ke satuan yang lebih kecil dari pilihan kasir;
//  - potongan baris tetap berlaku di atas harga ini.
// Beda kecil: sisa jumlah dibulatkan 2 desimal (server menyimpan jumlah 2 desimal) supaya total
// di aplikasi sama persis dengan hitungan server.

import { round2 } from './util.js';

/**
 * Rencana harga satuan besar untuk satu baris keranjang, atau null bila tidak ada yang lebih murah.
 * line: { good_unit_id, price, quantity, units: [{ good_unit_id, unit, quantity (isi), selling_price }] }
 * Hasil: { parts: [{ unit, qty }], total, saved } — parts dari satuan terbesar ke satuan pilihan kasir.
 */
export function tierPlan(line) {
  const units = line && Array.isArray(line.units) ? line.units : [];
  const q = Number(line && line.quantity);
  const price = Number(line && line.price);
  const own = units.find((u) => u.good_unit_id === line.good_unit_id);
  if (!own || units.length < 2) return null;
  const mine = { ...own, quantity: Number(own.quantity) || 0, selling_price: price };
  if (!(mine.quantity > 0) || !(price > 0) || !(q > 0)) return null;

  // Satuan lebih besar yang per isinya makin murah (satu satuan per isi: yang termurah).
  const cand = units
    .filter((u) => u.good_unit_id !== own.good_unit_id && Number(u.quantity) > mine.quantity && Number(u.selling_price) > 0)
    .map((u) => ({ ...u, quantity: Number(u.quantity), selling_price: Number(u.selling_price) }))
    .sort((a, b) => a.quantity - b.quantity || a.selling_price - b.selling_price);
  const bigger = [];
  let perIsi = price / mine.quantity;
  for (const u of cand) {
    if (bigger.length && bigger[bigger.length - 1].quantity === u.quantity) continue;
    if (u.selling_price / u.quantity < perIsi - 1e-9) {
      bigger.push(u);
      perIsi = u.selling_price / u.quantity;
    }
  }
  if (!bigger.length) return null;

  let rest = q * mine.quantity; // dalam isi terkecil
  const parts = [];
  for (let i = bigger.length - 1; i >= 0; i--) {
    const c = Math.floor(rest / bigger[i].quantity + 1e-9);
    if (c > 0) {
      parts.push({ unit: bigger[i], qty: c });
      rest -= c * bigger[i].quantity;
    }
  }
  if (!parts.length) return null;
  const left = round2(rest / mine.quantity); // sisa tetap di satuan pilihan kasir
  if (left > 0) parts.push({ unit: mine, qty: left });

  const total = round2(parts.reduce((a, p) => a + round2(p.qty * p.unit.selling_price), 0));
  const saved = round2(round2(q * price) - total);
  if (saved <= 0) return null;
  return { parts, total, saved };
}

/** "1 PAK5 + 1 PCS"; satuan yang diawali angka pakai x: "1x 1 Kg + 2x 100 Gram". */
export function tierText(plan) {
  return plan.parts.map((p) => String(round2(p.qty)).replace('.', ',') + (/^\d/.test(p.unit.unit) ? 'x ' : ' ') + p.unit.unit).join(' + ');
}

/**
 * Item yang dikirim ke server untuk satu baris keranjang: satu item per satuan bila berlaku harga grosir.
 * Potongan baris dibagi mulai dari item bernilai terbesar (tidak melebihi nilai item itu).
 */
export function lineItems(line) {
  const plan = tierPlan(line);
  const base = { good_id: line.good_id, name: line.name, code: line.code };
  const discount = Number(line.discount) || 0;
  if (!plan) {
    const gross = round2(line.price * line.quantity);
    return [{ ...base, good_unit_id: line.good_unit_id, unit: line.unit, quantity: round2(line.quantity), price: line.price, discount, gross }];
  }
  const items = plan.parts.map((p) => ({
    ...base,
    good_unit_id: p.unit.good_unit_id,
    unit: p.unit.unit,
    quantity: round2(p.qty),
    price: p.unit.selling_price,
    discount: 0,
    gross: round2(p.qty * p.unit.selling_price),
  }));
  let left = discount;
  for (const it of items.slice().sort((a, b) => b.gross - a.gross)) {
    if (left <= 0) break;
    it.discount = round2(Math.min(left, it.gross));
    left = round2(left - it.discount);
  }
  return items;
}
