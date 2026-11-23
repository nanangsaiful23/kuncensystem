// Struk dalam bentuk HTML untuk dicetak lewat driver printer Windows (58mm / 80mm).
// Isi sama dengan struk ESC/POS Kasir HP (receipt.js), tata letak memakai tabel.

import { esc, rp, qtyText, fmtDateTime } from '@hp/util.js';

/** paperWidth tersimpan sebagai jumlah karakter (32 = 58mm, 48 = 80mm), sama dengan Kasir HP. */
export const paperMm = (chars) => (Number(chars) === 48 ? 80 : 58);

function page(s, body) {
  const mm = paperMm(s.width);
  // Area cetak printer 58mm ≈ 48mm, 80mm ≈ 72mm.
  const printable = mm === 80 ? 72 : 48;
  const fontPx = mm === 80 ? 12.5 : 11.5;
  const lines = (t) =>
    String(t || '')
      .split('\n')
      .map((x) => x.trim())
      .filter(Boolean)
      .map((x) => `<div>${esc(x)}</div>`)
      .join('');
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    @page { size: ${mm}mm auto; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body { width: ${mm}mm; font: ${fontPx}px/1.3 Arial, "Segoe UI", Tahoma, sans-serif; -webkit-print-color-adjust: exact; }
    .r { width: ${printable}mm; margin: 0 auto; padding: 2mm 0 6mm; }
    .c { text-align: center; }
    .store { font-size: ${fontPx + 4}px; font-weight: 700; }
    hr { border: 0; border-top: 1px dashed #000; margin: 4px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 0; vertical-align: top; }
    td.n { text-align: right; white-space: nowrap; padding-left: 4px; }
    .item { padding-top: 3px; font-weight: 600; }
    .total td { font-size: ${fontPx + 3}px; font-weight: 700; padding: 2px 0; }
    .big { font-weight: 700; }
    .note { margin-top: 2px; }
    .reprint { margin-top: 4px; font-weight: 700; }
  </style></head><body><div class="r">
    <div class="c"><div class="store">${esc(s.storeName || 'TOKO')}</div>${lines(s.storeLines)}</div>
    ${body}
    <hr><div class="c">${lines(s.footer || 'Terima kasih')}</div>
  </div></body></html>`;
}

const row = (left, right, cls = '') => `<tr class="${cls}"><td>${left}</td><td class="n">${right}</td></tr>`;

/** sale: transaksi dari database lokal. s: pengaturan struk {storeName, storeLines, footer, width}. */
export function receiptHtml(sale, s = {}, { reprint = false } = {}) {
  let subtotal = 0;
  const items = sale.items
    .map((it) => {
      const gross = Math.round(it.price * it.quantity * 100) / 100;
      subtotal += gross - (it.discount || 0);
      return (
        `<tr><td colspan="2" class="item">${esc(it.name)}</td></tr>` +
        row(`&nbsp;${esc(qtyText(it.quantity))} ${esc(it.unit)} x ${rp(it.price)}`, rp(gross)) +
        (it.discount > 0 ? row('&nbsp;&nbsp;Diskon', '-' + rp(it.discount)) : '')
      );
    })
    .join('');
  const qty = sale.items.reduce((a, it) => a + Number(it.quantity || 0), 0);
  const body = `
    <hr>
    <table>
      ${row('No', esc(sale.receipt_no))}
      ${row('Waktu', esc(fmtDateTime(sale.sold_at)))}
      ${row('Kasir', esc(sale.cashier_name || '-'))}
      ${sale.member_id ? row('Member', esc(sale.member_name || '#' + sale.member_id)) : ''}
    </table>
    <hr>
    <table>${items}</table>
    <hr>
    <table>
      ${row(`Subtotal (${sale.items.length} brg, ${esc(qtyText(qty))} qty)`, rp(subtotal))}
      ${sale.total_discount > 0 ? row('Potongan', '-' + rp(sale.total_discount)) : ''}
      ${row('TOTAL', rp(sale.total), 'total')}
      ${row(sale.payment === 'transfer' ? 'Transfer' : 'Tunai', rp(sale.money_paid))}
      ${sale.payment !== 'transfer' ? row('Kembali', rp(sale.money_returned), 'big') : ''}
    </table>
    ${sale.note ? `<hr><div class="note">Catatan: ${esc(sale.note)}</div>` : ''}
    ${reprint ? '<div class="c reprint">** CETAK ULANG **</div>' : ''}`;
  return page(s, body);
}

export function testPageHtml(s = {}, info = {}) {
  const mm = paperMm(s.width);
  let ruler = '';
  for (let i = 1; i <= Number(s.width || 32); i++) ruler += String(i % 10);
  const body = `
    <hr>
    <div class="c big">CETAK TES</div>
    <table>
      ${row('Waktu', esc(fmtDateTime(new Date(), true)))}
      ${info.device ? row('Perangkat', esc(info.device)) : ''}
      ${info.printer ? row('Printer', esc(info.printer)) : ''}
      ${row('Kertas', mm + 'mm')}
      ${row('Kiri', 'Kanan')}
    </table>
    <hr>
    <div style="font-family: Consolas, 'Courier New', monospace; font-size: 9px; overflow: hidden; white-space: nowrap">${ruler}</div>
    <div class="big">Huruf tebal</div>
    <div class="total"><table>${row('TOTAL', '123.456', 'total')}</table></div>
    <hr>
    <div class="c">Kalau tulisan "Kiri" dan "Kanan"<br>tidak terpotong, printer siap dipakai.</div>`;
  return page(s, body);
}
