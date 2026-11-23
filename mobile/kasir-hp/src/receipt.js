// Struk ESC/POS untuk printer thermal 58mm (32 karakter/baris) atau 80mm (48).

import { rp, qtyText, fmtDateTime } from './util.js';

const ESC = 0x1b;
const GS = 0x1d;
const LF = 0x0a;

/** Printer murah hanya paham ASCII: buang tanda baca khusus/aksen. */
function ascii(s) {
  return String(s ?? '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[‘’]/g, "'")
    .replace(/[“”]/g, '"')
    .replace(/[–—]/g, '-')
    .replace(/×/g, 'x')
    .replace(/[^\x20-\x7e]/g, '?');
}

/** Potong teks panjang jadi beberapa baris (di batas kata bila bisa). */
export function wrap(text, width) {
  const words = ascii(text).split(/\s+/).filter(Boolean);
  const lines = [];
  let cur = '';
  for (let w of words) {
    while (w.length > width) {
      if (cur) {
        lines.push(cur);
        cur = '';
      }
      lines.push(w.slice(0, width));
      w = w.slice(width);
    }
    if (!cur) cur = w;
    else if (cur.length + 1 + w.length <= width) cur += ' ' + w;
    else {
      lines.push(cur);
      cur = w;
    }
  }
  if (cur) lines.push(cur);
  return lines.length ? lines : [''];
}

class EscPos {
  constructor(width = 32) {
    this.w = width;
    this.b = [];
  }
  raw(...bytes) {
    this.b.push(...bytes);
    return this;
  }
  init() {
    return this.raw(ESC, 0x40, ESC, 0x74, 0x00); // reset + code page PC437
  }
  align(n) {
    return this.raw(ESC, 0x61, n); // 0 kiri, 1 tengah, 2 kanan
  }
  bold(on) {
    return this.raw(ESC, 0x45, on ? 1 : 0);
  }
  size(n) {
    return this.raw(GS, 0x21, n); // 0x00 normal, 0x01 tinggi 2x, 0x11 besar
  }
  text(s) {
    for (const ch of ascii(s)) this.b.push(ch.charCodeAt(0));
    return this;
  }
  line(s = '') {
    return this.text(s).raw(LF);
  }
  /** Teks dibungkus sesuai lebar kertas. */
  para(s, width = this.w) {
    for (const l of wrap(s, width)) this.line(l);
    return this;
  }
  /** Kiri ... kanan dalam satu baris. */
  lr(left, right) {
    left = ascii(left);
    right = ascii(right);
    if (left.length + right.length + 1 > this.w) {
      this.line(left.slice(0, this.w));
      return this.line(right.padStart(this.w));
    }
    return this.line(left + ' '.repeat(this.w - left.length - right.length) + right);
  }
  hr(ch = '-') {
    return this.line(ch.repeat(this.w));
  }
  feed(n = 3) {
    return this.raw(ESC, 0x64, n);
  }
  bytes() {
    return Uint8Array.from(this.b);
  }
}

function header(p, s) {
  p.init().align(1).bold(true).size(0x01);
  p.para(s.storeName || 'TOKO', p.w);
  p.size(0x00).bold(false);
  for (const l of String(s.storeLines || '').split('\n').map((x) => x.trim()).filter(Boolean)) p.para(l);
  p.align(0);
}

/**
 * sale: transaksi dari database HP. s: pengaturan struk {storeName, storeLines, footer, width}.
 */
export function buildReceipt(sale, s = {}, { reprint = false } = {}) {
  const p = new EscPos(Number(s.width) || 32);
  header(p, s);
  p.hr();
  p.lr('No', sale.receipt_no);
  p.lr('Waktu', fmtDateTime(sale.sold_at));
  p.lr('Kasir', sale.cashier_name || '-');
  if (sale.member_id) p.lr('Member', sale.member_name || '#' + sale.member_id);
  p.hr();

  let subtotal = 0;
  for (const it of sale.items) {
    const gross = Math.round(it.price * it.quantity * 100) / 100;
    p.para(it.name);
    p.lr(` ${qtyText(it.quantity)} ${it.unit} x ${rp(it.price)}`, rp(gross));
    if (it.discount > 0) p.lr('   Diskon', '-' + rp(it.discount));
    subtotal += gross - (it.discount || 0);
  }
  p.hr();
  const count = sale.items.length;
  p.lr(`Subtotal (${count} brg)`, rp(subtotal));
  if (sale.total_discount > 0) p.lr('Potongan', '-' + rp(sale.total_discount));
  p.bold(true).lr('TOTAL', rp(sale.total)).bold(false);
  p.lr(sale.payment === 'transfer' ? 'Transfer' : 'Tunai', rp(sale.money_paid));
  if (sale.payment !== 'transfer') p.lr('Kembali', rp(sale.money_returned));
  if (sale.note) {
    p.hr();
    p.para('Catatan: ' + sale.note);
  }
  p.hr();
  p.align(1);
  for (const l of String(s.footer || 'Terima kasih').split('\n').map((x) => x.trim()).filter(Boolean)) p.para(l);
  if (reprint) p.line('** CETAK ULANG **');
  p.align(0).feed(4);
  return p.bytes();
}

export function buildTestPage(s = {}, info = {}) {
  const p = new EscPos(Number(s.width) || 32);
  header(p, s);
  p.hr();
  p.align(1).bold(true).line('CETAK TES').bold(false).align(0);
  p.lr('Waktu', fmtDateTime(new Date(), true));
  if (info.device) p.lr('Perangkat', info.device);
  if (info.printer) p.lr('Printer', info.printer);
  p.hr();
  p.line('Penggaris ' + p.w + ' karakter:');
  let ruler = '';
  for (let i = 1; i <= p.w; i++) ruler += String(i % 10);
  p.line(ruler);
  p.lr('Kiri', 'Kanan');
  p.bold(true).line('Huruf tebal').bold(false);
  p.size(0x01).line('Tinggi 2x').size(0x00);
  p.hr();
  p.align(1).line('Kalau semua baris rapi,').line('printer siap dipakai.').align(0);
  p.feed(4);
  return p.bytes();
}
