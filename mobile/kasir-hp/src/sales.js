// Simpan transaksi di HP (tidak pernah menunggu server) + cetak struk.

import * as db from './db.js';
import { cart, lineGross, subtotal, grandTotal } from './cart.js';
import { uuidv4, isoLocal, ymd, dayKey, round2 } from './util.js';
import { buildReceipt, buildTestPage } from './receipt.js';
import { printer } from './native.js';

export const receiptSettings = () => ({
  storeName: db.kv('storeName', 'NTN MART'),
  storeLines: db.kv('storeLines', ''),
  footer: db.kv('storeFooter', 'Terima kasih atas kunjungan Anda'),
  width: db.kv('paperWidth', 32),
});

/**
 * Simpan keranjang sebagai transaksi baru. Nomor nota: <kode perangkat>-YYYYMMDD-NNNN.
 * Nomor urut & transaksi disimpan dalam satu transaksi database supaya tidak dobel.
 */
export async function saveSale({ cashier, moneyPaid }) {
  const device = db.kv('device');
  if (!device || !device.code) throw new Error('Kode perangkat tidak ada. Pasang ulang perangkat.');
  const now = new Date();
  const date = ymd(now);
  const total = grandTotal();
  const paid = cart.payment === 'transfer' ? total : Number(moneyPaid) || 0;
  if (paid < total) throw new Error('Uang bayar kurang dari total.');

  const sale = {
    client_uuid: uuidv4(),
    receipt_no: null,
    sold_at: isoLocal(now),
    day: dayKey(now),
    created_ms: now.getTime(),
    device_code: device.code,
    cashier_id: cashier.id,
    cashier_name: cashier.name,
    member_id: cart.memberId || null,
    member_name: cart.memberId ? cart.memberName : null,
    payment: cart.payment,
    items: cart.lines.map((l) => ({
      good_unit_id: l.good_unit_id,
      good_id: l.good_id,
      name: l.name,
      code: l.code,
      unit: l.unit,
      quantity: round2(l.quantity),
      price: l.price,
      discount: l.discount || 0,
      gross: lineGross(l),
    })),
    subtotal: subtotal(),
    total_discount: cart.totalDiscount || 0,
    total,
    money_paid: paid,
    money_returned: round2(paid - total),
    note: cart.note ? String(cart.note).slice(0, 255) : null,
    status: 'pending',
    attempts: 0,
    message: null,
    transaction_id: null,
    printed: 0,
  };

  const prefix = `${device.code}-${date}-`;
  await db.atomic(['sales', 'kv'], async (os, req) => {
    const seqKey = 'seq-' + date;
    let n = Number(await req(os.kv.get(seqKey))) || 0;
    // Jaga-jaga (mis. data pengaturan terhapus): jangan pakai ulang nomor yang sudah ada hari ini.
    const todays = await req(os.sales.index('day').getAll(sale.day));
    for (const s of todays) {
      if (s.receipt_no && s.receipt_no.startsWith(prefix)) n = Math.max(n, parseInt(s.receipt_no.slice(prefix.length), 10) || 0);
    }
    n += 1;
    sale.receipt_no = prefix + String(n).padStart(4, '0');
    os.kv.put(n, seqKey);
    os.sales.put(sale);
  });
  return sale;
}

export async function markPrinted(sale) {
  sale.printed = (sale.printed || 0) + 1;
  const fresh = await db.get('sales', sale.client_uuid);
  if (fresh) {
    fresh.printed = sale.printed;
    await db.put('sales', fresh);
  }
}

/** Cetak struk. Melempar Error dengan pesan yang jelas bila gagal. */
export async function printSale(sale, { reprint = false } = {}) {
  const p = db.kv('printer');
  if (!p || !p.address) throw new Error('Printer belum dipilih. Buka Pengaturan > Printer.');
  await printer.write(buildReceipt(sale, receiptSettings(), { reprint }), p.address);
  await markPrinted(sale);
}

export async function printTest() {
  const p = db.kv('printer');
  if (!p || !p.address) throw new Error('Printer belum dipilih.');
  const device = db.kv('device') || {};
  await printer.write(buildTestPage(receiptSettings(), { device: device.code, printer: p.name }), p.address);
}
