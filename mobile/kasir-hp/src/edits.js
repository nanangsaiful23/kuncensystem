// Transaksi yang diubah admin di web (jumlah barang, member, catatan, uang bayar):
// isi transaksi di riwayat HP/PC diganti dengan isi terbaru dari server
// (GET /transactions/changed, lihat sync.js pullEdited()).

import { round2 } from './util.js';

const n = (v) => round2(Number(v) || 0);

/**
 * Yang bisa diubah admin. Total & kembalian tidak ikut dibandingkan: keduanya dihitung dari
 * isi ini, dan server boleh membulatkan sedikit berbeda (selisih itu bukan "diubah admin").
 */
function signature(s) {
  return JSON.stringify([
    s.member_id || null,
    s.note || '',
    s.payment || '',
    n(s.money_paid),
    n(s.total_discount),
    (s.items || []).map((i) => [Number(i.good_unit_id), n(i.quantity), n(i.price), n(i.discount)]),
  ]);
}

/**
 * Gabungkan isi dari server ke transaksi di HP. Hasil: transaksi baru (untuk disimpan)
 * atau null bila isinya sama. Nama barang/satuan/kode tetap yang di HP (seperti saat dijual);
 * baris yang tidak ada di HP memakai nama dari server.
 */
export function mergeServerSale(local, srv) {
  if (!local || !srv || !Array.isArray(srv.items) || !srv.items.length) return null;
  const old = Array.isArray(local.items) ? local.items : [];
  const used = new Set();
  const items = srv.items.map((it, i) => {
    const id = Number(it.good_unit_id);
    // Urutan baris di server sama dengan urutan kiriman HP; cari per satuan bila bergeser.
    const k = old[i] && Number(old[i].good_unit_id) === id && !used.has(i) ? i : old.findIndex((o, j) => !used.has(j) && Number(o.good_unit_id) === id);
    const o = k >= 0 ? old[k] : null;
    if (k >= 0) used.add(k);
    const quantity = n(it.quantity);
    const price = n(it.price);
    return {
      good_id: o && o.good_id != null ? o.good_id : it.good_id,
      name: o ? o.name : it.name || 'Barang #' + id,
      code: o ? o.code : it.code,
      good_unit_id: id,
      unit: o ? o.unit : it.unit || '',
      quantity,
      price,
      discount: n(it.discount),
      gross: round2(quantity * price),
    };
  });

  const memberId = srv.member_id || null;
  const next = {
    ...local,
    member_id: memberId,
    member_name: !memberId ? null : memberId === local.member_id && local.member_name ? local.member_name : srv.member_name || '#' + memberId,
    payment: srv.payment || local.payment,
    items,
    subtotal: round2(items.reduce((a, i) => a + i.gross - i.discount, 0)),
    total_discount: n(srv.total_discount),
    total: n(srv.total),
    money_paid: n(srv.money_paid),
    money_returned: n(srv.money_returned),
    note: srv.note ? String(srv.note) : null,
  };
  if (signature(next) === signature(local)) return null;
  next.edited_at = Date.now(); // ditampilkan di detail riwayat
  if (srv.transaction_id) next.transaction_id = srv.transaction_id;
  return next;
}
