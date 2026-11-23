// Simpan transaksi: memakai saveSale milik Kasir HP apa adanya (nomor nota, antrian sinkron).
// Yang diganti hanya cara mencetak: HTML lewat driver printer Windows.

export * from '@hp/sales.js';
import { markPrinted, receiptSettings } from '@hp/sales.js';
import * as db from '@hp/db.js';
import { printer } from './platform/native.js';
import { receiptHtml, testPageHtml, paperMm } from './receipt-html.js';

const printOpts = () => {
  const p = db.kv('printer');
  if (!p || !p.address) throw new Error('Printer belum dipilih. Buka Pengaturan › Printer.');
  return { deviceName: p.address, widthMm: paperMm(db.kv('paperWidth', 32)), silent: db.kv('printDialog') !== true };
};

/** Cetak struk. Melempar Error dengan pesan yang jelas bila gagal. */
export async function printSale(sale, { reprint = false } = {}) {
  const opts = printOpts();
  await printer.printHtml(receiptHtml(sale, receiptSettings(), { reprint }), opts);
  await markPrinted(sale);
}

export async function printTest() {
  const opts = printOpts();
  const device = db.kv('device') || {};
  const p = db.kv('printer') || {};
  await printer.printHtml(testPageHtml(receiptSettings(), { device: device.code, printer: p.name }), opts);
}
