// Layar pembayaran: member, potongan akhir, tunai/transfer, simpan (+ cetak).

import * as db from '../db.js';
import { go, session } from '../router.js';
import { openSheet, toast, moneyInput, busy } from '../ui.js';
import { esc, rp, debounce } from '../util.js';
import { cart, setCart, subtotal, grandTotal, itemCount, cartProblem, resetCart } from '../cart.js';
import { searchMembers } from '../catalog.js';
import { saveSale, printSale } from '../sales.js';
import { kick } from '../sync.js';

/** Pilihan uang cepat: pembulatan ke atas ke pecahan umum. */
function quickAmounts(total) {
  const out = [];
  for (const step of [1000, 5000, 10000, 20000, 50000, 100000]) {
    const v = Math.ceil(total / step) * step;
    if (v > total && !out.includes(v)) out.push(v);
  }
  return out.slice(0, 4);
}

export function bayarView(root) {
  if (!session.cashier) {
    go('lock');
    return {};
  }
  if (!cart.lines.length) {
    go('kasir');
    return {};
  }

  let paid = 0;

  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      <button type="button" class="icon-btn" data-a="back">←</button>
      <div class="tb-left"><div class="tb-title">Pembayaran</div><div class="tb-sub">👤 ${esc(session.cashier.name)}</div></div>
    </header>
    <div class="content pay">
      <div class="card">
        <div class="row-between"><span>Subtotal (${itemCount()} barang)</span><b>${rp(subtotal())}</b></div>
        <label>Member</label>
        <button type="button" class="select" data-a="member"><span id="member-name"></span><span>▾</span></button>
        <label for="tdisc">Potongan akhir (Rp)</label>
        <input id="tdisc" inputmode="numeric" placeholder="0" value="${cart.totalDiscount ? rp(cart.totalDiscount) : ''}">
        <div class="grand"><span>TOTAL</span><b id="grand"></b></div>
      </div>
      <div class="card">
        <div class="seg">
          <button type="button" data-pay="cash">💵 Tunai</button>
          <button type="button" data-pay="transfer">🏦 Transfer</button>
        </div>
        <div id="cash-box">
          <label for="paid">Uang diterima</label>
          <input id="paid" inputmode="numeric" class="big-input" placeholder="0" autocomplete="off">
          <div class="chips wrap" id="quick"></div>
          <div class="change"><span>Kembalian</span><b id="change">0</b></div>
        </div>
        <div id="tf-box" hidden>
          <label class="check"><input type="checkbox" id="tf-ok"> Transfer sudah masuk (sudah saya cek di HP/rekening)</label>
          <div class="hint">Transfer dicek manual oleh kasir. Uang bayar = total.</div>
        </div>
        <label for="note">Catatan (opsional)</label>
        <input id="note" maxlength="255" value="${esc(cart.note || '')}">
      </div>
      <div id="err" class="error" hidden></div>
    </div>
    <footer class="paybar">
      <button type="button" class="btn" data-a="save">Simpan saja</button>
      <button type="button" class="btn btn-primary big" data-a="save-print">Simpan & Cetak</button>
    </footer>
  </div>`;

  const $ = (s) => root.querySelector(s);

  function render() {
    $('#member-name').textContent = cart.memberId ? cart.memberName : 'Tanpa member';
    const total = grandTotal();
    $('#grand').textContent = rp(total);
    root.querySelectorAll('[data-pay]').forEach((b) => b.classList.toggle('on', b.dataset.pay === cart.payment));
    $('#cash-box').hidden = cart.payment !== 'cash';
    $('#tf-box').hidden = cart.payment !== 'transfer';
    $('#quick').innerHTML =
      `<button type="button" class="chip" data-amt="${Math.ceil(total)}">Uang pas</button>` +
      quickAmounts(total)
        .map((v) => `<button type="button" class="chip" data-amt="${v}">${rp(v)}</button>`)
        .join('');
    const change = paid - total;
    $('#change').textContent = paid ? rp(Math.max(0, change)) : '0';
    $('#change').parentElement.classList.toggle('neg', paid > 0 && change < 0);
    if (paid > 0 && change < 0) $('#change').textContent = 'kurang ' + rp(-change);
  }

  moneyInput($('#tdisc'), (v) => {
    setCart({ totalDiscount: v });
    render();
  });
  moneyInput($('#paid'), (v) => {
    paid = v;
    render();
  });
  $('#note').addEventListener('input', debounce(() => setCart({ note: $('#note').value }), 200));

  function chooseMember() {
    const s = openSheet({
      title: 'Pilih member',
      full: true,
      html: `<input id="mq" type="search" placeholder="Cari nama / toko / no. HP" autocomplete="off">
        <div class="member-list" id="ml"></div>`,
    });
    const mq = s.body.querySelector('#mq');
    const draw = () => {
      const list = searchMembers(mq.value);
      s.body.querySelector('#ml').innerHTML =
        `<button type="button" class="member" data-id=""><b>Tanpa member</b></button>` +
        list
          .map(
            (m) => `<button type="button" class="member ${m.id === cart.memberId ? 'on' : ''}" data-id="${m.id}">
              <b>${esc(m.name)}</b>${m.store_name ? ` · ${esc(m.store_name)}` : ''}
              <div class="muted small">${esc(m.phone_number || '')} ${esc(m.address || '')}</div></button>`
          )
          .join('') +
        (list.length ? '' : '<div class="empty small">Member tidak ditemukan. HP tidak bisa menambah member baru.</div>');
    };
    mq.addEventListener('input', debounce(draw, 120));
    draw();
    s.body.addEventListener('click', (e) => {
      const m = e.target.closest('.member');
      if (!m) return;
      const id = m.dataset.id ? Number(m.dataset.id) : null;
      const name = id ? m.querySelector('b').textContent : null;
      setCart({ memberId: id, memberName: name });
      s.close();
      render();
    });
  }

  async function save(withPrint) {
    const err = $('#err');
    err.hidden = true;
    const problem = cartProblem();
    const total = grandTotal();
    let msg = problem;
    if (!msg && cart.payment === 'cash' && paid < total) msg = `Uang diterima (${rp(paid)}) kurang dari total (${rp(total)}).`;
    if (!msg && cart.payment === 'transfer' && !$('#tf-ok').checked) msg = 'Centang dulu "Transfer sudah masuk" setelah dicek.';
    if (msg) {
      err.hidden = false;
      err.textContent = msg;
      err.scrollIntoView({ block: 'center' });
      return;
    }
    const b = busy('Menyimpan…');
    let sale;
    try {
      setCart({ note: $('#note').value.trim() });
      sale = await saveSale({ cashier: session.cashier, moneyPaid: paid });
    } catch (e) {
      b.done();
      err.hidden = false;
      err.textContent = 'Gagal menyimpan: ' + e.message;
      return;
    }
    resetCart();
    kick(); // kirim ke server di latar belakang bila online
    let printError = null;
    if (withPrint) {
      b.set('Mencetak struk…');
      try {
        await printSale(sale);
      } catch (e) {
        printError = e.message;
      }
    }
    b.done();
    go('selesai', { uuid: sale.client_uuid, printError, printed: withPrint && !printError });
  }

  root.addEventListener('click', (e) => {
    const a = e.target.closest('[data-a]');
    const pay = e.target.closest('[data-pay]');
    const amt = e.target.closest('[data-amt]');
    if (pay) {
      setCart({ payment: pay.dataset.pay });
      render();
    }
    if (amt) {
      paid = Number(amt.dataset.amt);
      $('#paid').value = rp(paid);
      render();
    }
    if (!a) return;
    if (a.dataset.a === 'back') go('kasir');
    if (a.dataset.a === 'member') chooseMember();
    if (a.dataset.a === 'save') save(false);
    if (a.dataset.a === 'save-print') save(true);
  });

  render();
  if (!db.kv('printer')) {
    // Belum ada printer: cukup simpan (struk bisa dicetak ulang nanti dari Riwayat).
    $('[data-a="save-print"]').hidden = true;
    $('[data-a="save"]').className = 'btn btn-primary big';
    $('[data-a="save"]').style.flex = '1';
    $('[data-a="save"]').textContent = 'Simpan';
  }
  setTimeout(() => cart.payment === 'cash' && $('#paid').focus(), 250);
  return { back: () => go('kasir') };
}
