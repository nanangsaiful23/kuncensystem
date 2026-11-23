// Tampilan transaksi yang dipakai di layar Selesai dan Riwayat.

import { esc, rp, qtyText, fmtDateTime, fmtTime } from '../util.js';

export function statusHtml(sale) {
  if (sale.status === 'sent') return `<span class="st st-ok">✓ terkirim</span>`;
  if (sale.status === 'rejected') return `<span class="st st-bad">✗ ditolak</span>`;
  return `<span class="st st-wait">⏳ belum terkirim</span>`;
}

export function saleRowHtml(s) {
  return `
    <button type="button" class="sale-row" data-uuid="${esc(s.client_uuid)}">
      <span class="sr-time">${fmtTime(s.sold_at)}</span>
      <span class="sr-main"><b>${esc(s.receipt_no)}</b><br><small class="muted">${esc(s.cashier_name || '')}${s.member_name ? ' · ' + esc(s.member_name) : ''} · ${s.payment === 'transfer' ? 'Transfer' : 'Tunai'}${s.edited_at ? ' · ✎ diubah admin' : ''}</small></span>
      <span class="sr-right"><b>${rp(s.total)}</b><br>${statusHtml(s)}</span>
    </button>`;
}

export function saleDetailHtml(s) {
  const items = s.items
    .map(
      (it) => `
      <div class="d-item">
        <div>${esc(it.name)}</div>
        <div class="row-between muted small"><span>${qtyText(it.quantity)} ${esc(it.unit)} × ${rp(it.price)}${it.discount ? ` − ${rp(it.discount)}` : ''}</span>
          <span>${rp((it.gross != null ? it.gross : it.price * it.quantity) - (it.discount || 0))}</span></div>
      </div>`
    )
    .join('');
  let statusInfo = '';
  if (s.status === 'sent')
    statusInfo = `<div class="alert a-green">✓ Sudah tersimpan di server (ID transaksi ${esc(s.transaction_id)}).${
      s.edited_at ? `<br><small>✎ Diubah admin di web ${fmtDateTime(s.edited_at)}; yang tampil di sini isi terbaru.</small>` : ''
    }</div>`;
  else if (s.status === 'rejected')
    statusInfo = `<div class="alert a-red"><b>✗ Ditolak server:</b> ${esc(s.message || '')}<br>
      <small>Transaksi ini tercatat di halaman admin Kasir HP (Transaksi ditolak). Beri tahu admin; tidak dikirim ulang otomatis.</small></div>`;
  else
    statusInfo = `<div class="alert a-yellow">⏳ Belum terkirim ke server. Akan dikirim otomatis saat online.${s.message ? `<br><small>Terakhir: ${esc(s.message)}</small>` : ''}</div>`;
  return `
    ${statusInfo}
    <div class="row-between"><span>No. nota</span><b>${esc(s.receipt_no)}</b></div>
    <div class="row-between"><span>Waktu</span><span>${fmtDateTime(s.sold_at, true)}</span></div>
    <div class="row-between"><span>Kasir</span><span>${esc(s.cashier_name || '-')}</span></div>
    <div class="row-between"><span>Member</span><span>${esc(s.member_name || 'Tanpa member')}</span></div>
    <hr>
    ${items}
    <hr>
    ${s.total_discount ? `<div class="row-between"><span>Potongan</span><span>−${rp(s.total_discount)}</span></div>` : ''}
    <div class="row-between big-row"><b>TOTAL</b><b>${rp(s.total)}</b></div>
    <div class="row-between"><span>${s.payment === 'transfer' ? 'Transfer' : 'Tunai'}</span><span>${rp(s.money_paid)}</span></div>
    ${s.payment === 'transfer' ? '' : `<div class="row-between"><span>Kembali</span><span>${rp(s.money_returned)}</span></div>`}
    ${s.note ? `<div class="muted small">Catatan: ${esc(s.note)}</div>` : ''}
    <div class="muted small">Dicetak: ${s.printed || 0}x</div>`;
}
