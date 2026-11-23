// Keranjang belanja yang sedang dilayani + perhitungan total
// (rumus sama dengan server: baris = round(harga x jumlah, 2) - diskon baris).

import { round2 } from './util.js';
import * as db from './db.js';
import { bus } from './bus.js';

export const cart = {
  lines: [], // [{ key, good_id, name, code, units, good_unit_id, unit, price, quantity, discount }]
  memberId: null,
  memberName: null,
  totalDiscount: 0,
  payment: 'cash',
  note: '',
};

let seq = 0;

export const lineGross = (l) => round2(l.price * l.quantity);
export const lineSum = (l) => round2(lineGross(l) - (l.discount || 0));
export const subtotal = () => round2(cart.lines.reduce((a, l) => a + lineSum(l), 0));
export const grandTotal = () => round2(subtotal() - (cart.totalDiscount || 0));
export const itemCount = () => cart.lines.length;

function changed() {
  bus.emit('cart', cart);
  saveDraft();
}

let draftTimer = null;
function saveDraft() {
  clearTimeout(draftTimer);
  draftTimer = setTimeout(() => {
    db.setKv('cartDraft', JSON.parse(JSON.stringify(cart))).catch(() => {});
  }, 300);
}

/** Pulihkan keranjang yang belum selesai (mis. aplikasi tertutup tiba-tiba). */
export function restoreDraft() {
  const d = db.kv('cartDraft');
  if (d && Array.isArray(d.lines)) {
    Object.assign(cart, d);
    seq = cart.lines.reduce((m, l) => Math.max(m, l.key || 0), 0);
  }
}

export function resetCart() {
  cart.lines = [];
  cart.memberId = null;
  cart.memberName = null;
  cart.totalDiscount = 0;
  cart.payment = 'cash';
  cart.note = '';
  changed();
}

/** Tambah barang. Barang + satuan yang sama digabung (jumlah ditambah). */
export function addGood(good, unit, qty = 1) {
  unit = unit || good.units[0];
  if (!unit) return null;
  let line = cart.lines.find((l) => l.good_unit_id === unit.good_unit_id && !l.discount);
  if (line) {
    line.quantity = round2(line.quantity + qty);
    // pindahkan ke paling akhir supaya tampil di atas daftar
    cart.lines.splice(cart.lines.indexOf(line), 1);
    cart.lines.push(line);
  } else {
    line = {
      key: ++seq,
      good_id: good.id,
      name: good.name,
      code: good.code,
      units: good.units.map((u) => ({ good_unit_id: u.good_unit_id, unit: u.unit, quantity: u.quantity, selling_price: u.selling_price })),
      good_unit_id: unit.good_unit_id,
      unit: unit.unit,
      price: Number(unit.selling_price) || 0,
      quantity: qty,
      discount: 0,
    };
    cart.lines.push(line);
  }
  changed();
  return line;
}

export const findLine = (key) => cart.lines.find((l) => l.key === key);

export function updateLine(key, patch) {
  const l = findLine(key);
  if (!l) return;
  if (patch.good_unit_id && patch.good_unit_id !== l.good_unit_id) {
    const u = l.units.find((x) => x.good_unit_id === patch.good_unit_id);
    if (u) {
      l.good_unit_id = u.good_unit_id;
      l.unit = u.unit;
      l.price = Number(u.selling_price) || 0;
    }
  }
  if (patch.quantity !== undefined) l.quantity = round2(patch.quantity);
  if (patch.discount !== undefined) l.discount = Math.max(0, Number(patch.discount) || 0);
  changed();
}

export function removeLine(key) {
  cart.lines = cart.lines.filter((l) => l.key !== key);
  changed();
}

export function setCart(patch) {
  Object.assign(cart, patch);
  changed();
}

/** Pesan kesalahan keranjang, atau null bila boleh dibayar. */
export function cartProblem() {
  if (!cart.lines.length) return 'Keranjang masih kosong.';
  for (const l of cart.lines) {
    if (!(l.quantity > 0)) return `Jumlah "${l.name}" harus lebih dari 0.`;
    if (l.discount > lineGross(l)) return `Diskon "${l.name}" lebih besar dari harganya.`;
  }
  if (cart.totalDiscount > subtotal()) return 'Potongan akhir lebih besar dari subtotal.';
  return null;
}
