// Layar kasir PC: satu layar untuk cari/scan barang, keranjang (tabel), dan pembayaran.
// Dirancang untuk keyboard + scanner barcode USB (scanner mengetik kode lalu Enter).
// Perhitungan & penyimpanan memakai modul Kasir HP (cart.js, sales.js) supaya hasilnya sama.

import * as db from '@hp/db.js';
import { go, session } from '@hp/router.js';
import { openSheet, toast, confirmBox, moneyInput, beep } from '@hp/ui.js';
import { esc, rp, qtyText, parseQty, parseMoney, debounce, round2 } from '@hp/util.js';
import { searchGoods, findByCode, goodsCount, searchMembers } from '@hp/catalog.js';
import {
  cart,
  addGood,
  updateLine,
  removeLine,
  resetCart,
  findLine,
  lineGross,
  lineSum,
  subtotal,
  grandTotal,
  setCart,
  cartProblem,
} from '@hp/cart.js';
import { mountStatus } from '@hp/status.js';
import { bus } from '@hp/bus.js';
import { kick } from '@hp/sync.js';
import { statusHtml } from '@hp/views/common.js';
import { saveSale, printSale } from '../sales.js';

const kbd = (k) => `<kbd>${k}</kbd>`;

/** Pilihan uang cepat: pembulatan ke atas ke pecahan umum. */
function quickAmounts(total) {
  const out = [];
  for (const step of [1000, 5000, 10000, 20000, 50000, 100000]) {
    const v = Math.ceil(total / step) * step;
    if (v > total && !out.includes(v)) out.push(v);
  }
  return out.slice(0, 4);
}

/** "5*indomie" -> { qty: 5, text: "indomie" } */
function splitQty(text) {
  const m = String(text).match(/^\s*(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/);
  if (!m) return { qty: 1, text: String(text).trim() };
  const qty = parseQty(m[1]);
  return { qty: qty > 0 ? qty : 1, text: m[2].trim() };
}

const looksLikeBarcode = (t) => /^\d{5,}$/.test(t);

export function kasirView(root) {
  if (!session.cashier) {
    go('lock');
    return {};
  }
  const device = db.kv('device') || {};
  const hasPrinter = () => !!(db.kv('printer') && db.kv('printer').address);

  root.innerHTML = `
  <div class="screen kasir pc">
    <header class="topbar">
      <div class="tb-left">
        <div class="tb-title">Kasir PC <span class="dev-code">${esc(device.code || '')}</span></div>
        <div class="tb-sub">👤 ${esc(session.cashier.name)} · <span data-status="printer"></span></div>
      </div>
      <div class="tb-right">
        <button type="button" class="badge-btn" data-a="pengaturan" data-status="conn" title="Pengaturan sambungan"></button>
        <button type="button" class="tb-btn" data-a="riwayat">Riwayat ${kbd('F9')}</button>
        <button type="button" class="tb-btn" data-a="pengaturan">Pengaturan</button>
        <button type="button" class="tb-btn" data-a="lock">Ganti kasir ${kbd('Ctrl+L')}</button>
      </div>
    </header>
    <div class="strip" data-status="strip"></div>
    <div data-status="alerts"></div>
    <div class="pc-main">
      <section class="pc-left">
        <div class="pc-search">
          <span class="pc-search-key">${kbd('F2')}</span>
          <input id="q" type="text" autocomplete="off" spellcheck="false"
            placeholder="Scan barcode, atau ketik nama / kode barang lalu Enter   (5*kode = jumlah 5)">
          <div id="results" class="pc-results" hidden></div>
        </div>
        <div class="pc-cart" id="cart-wrap">
          <table class="cart-table">
            <thead><tr>
              <th class="c-no">#</th><th>Barang</th><th class="c-unit">Satuan</th><th class="c-qty">Jumlah</th>
              <th class="c-num">Harga</th><th class="c-disc">Diskon</th><th class="c-num">Subtotal</th><th class="c-x"></th>
            </tr></thead>
            <tbody id="lines"></tbody>
          </table>
          <div id="empty" class="empty"></div>
        </div>
      </section>
      <aside class="pc-pay">
        <div class="pc-total">
          <div class="pc-total-label">TOTAL</div>
          <div class="pc-total-value" id="grand">0</div>
          <div class="muted small" id="count"></div>
        </div>
        <div class="pc-grid2">
          <div>
            <label>Member ${kbd('F4')}</label>
            <button type="button" class="select" data-a="member"><span id="member-name">Tanpa member</span><span>▾</span></button>
          </div>
          <div>
            <label for="tdisc">Potongan (Rp) ${kbd('F6')}</label>
            <input id="tdisc" inputmode="numeric" placeholder="0" autocomplete="off">
          </div>
        </div>
        <label>Pembayaran ${kbd('F7')}</label>
        <div class="seg">
          <button type="button" data-pay="cash">💵 Tunai</button>
          <button type="button" data-pay="transfer">🏦 Transfer</button>
        </div>
        <div id="cash-box">
          <label for="paid">Uang diterima ${kbd('F8')}</label>
          <input id="paid" inputmode="numeric" class="big-input" placeholder="0" autocomplete="off">
          <div class="chips wrap" id="quick"></div>
          <div class="change"><span>Kembalian</span><b id="change">0</b></div>
        </div>
        <div id="tf-box" hidden>
          <label class="check"><input type="checkbox" id="tf-ok"> Transfer sudah masuk (sudah saya cek)</label>
          <div class="hint">Transfer dicek manual oleh kasir. Uang bayar = total.</div>
        </div>
        <input id="note" class="pc-note" maxlength="255" autocomplete="off" placeholder="Catatan (opsional)" aria-label="Catatan">
        <div id="err" class="error" hidden></div>
        <div class="pc-actions">
          <button type="button" class="btn" data-a="clear" title="Kosongkan keranjang">Batal</button>
          <button type="button" class="btn" data-a="save" id="btn-save">Simpan</button>
          <button type="button" class="btn btn-primary big" data-a="save-print" id="btn-save-print">Simpan &amp; Cetak ${kbd('F12')}</button>
        </div>
      </aside>
    </div>
    <footer class="pc-keys">
      ${kbd('F2')} Cari ${kbd('↑↓')} Pilih baris ${kbd('+')}${kbd('−')} Jumlah ${kbd('F3')} Ubah jumlah ${kbd('Del')} Hapus baris
      ${kbd('F4')} Member ${kbd('F6')} Potongan ${kbd('F7')} Tunai/Transfer ${kbd('F8')} Uang ${kbd('F12')} Simpan
      ${kbd('F9')} Riwayat ${kbd('Esc')} Tutup
    </footer>
  </div>`;

  const $ = (s) => root.querySelector(s);
  const q = $('#q');
  const results = $('#results');
  let lastResults = [];
  let lastQuery = '';
  let hi = 0; // baris hasil pencarian yang disorot
  let selKey = null; // baris keranjang yang dipilih
  let paid = 0;

  // ---------------- Keranjang ----------------
  const unitOptions = (l) =>
    l.units
      .map((u) => `<option value="${u.good_unit_id}" ${u.good_unit_id === l.good_unit_id ? 'selected' : ''}>${esc(u.unit)} — ${rp(u.selling_price)}</option>`)
      .join('');

  function renderCart() {
    // Simpan posisi fokus supaya kasir yang sedang mengetik di tabel tidak kehilangan kursor.
    const af = document.activeElement;
    const afRow = af && af.closest && af.closest('#lines tr');
    const focus = afRow && af.dataset.f ? { key: afRow.dataset.key, f: af.dataset.f } : null;

    if (selKey !== null && !findLine(selKey)) selKey = cart.lines.length ? cart.lines[cart.lines.length - 1].key : null;
    $('#lines').innerHTML = cart.lines
      .map(
        (l, i) => `
      <tr data-key="${l.key}" class="${l.key === selKey ? 'sel' : ''}">
        <td class="c-no">${i + 1}</td>
        <td><div class="ln">${esc(l.name)}</div><div class="muted small">${esc(l.code || '')}</div></td>
        <td class="c-unit">${l.units.length > 1 ? `<select data-f="unit">${unitOptions(l)}</select>` : esc(l.unit)}</td>
        <td class="c-qty"><input data-f="qty" class="num" value="${qtyText(l.quantity)}" autocomplete="off"></td>
        <td class="c-num">${rp(l.price)}</td>
        <td class="c-disc"><input data-f="disc" class="num" value="${l.discount ? rp(l.discount) : ''}" placeholder="0" autocomplete="off"></td>
        <td class="c-num b">${rp(lineSum(l))}</td>
        <td class="c-x"><button type="button" class="x-btn" data-a="del" title="Hapus baris">✕</button></td>
      </tr>`
      )
      .join('');
    $('#empty').hidden = cart.lines.length > 0;
    $('#empty').innerHTML = goodsCount()
      ? `Belum ada barang. Scan barcode atau ketik nama barang di kolom cari ${kbd('F2')}.`
      : 'Data barang kosong. Buka Pengaturan › Unduh ulang data (perlu internet).';
    if (focus) {
      const el = root.querySelector(`#lines tr[data-key="${focus.key}"] [data-f="${focus.f}"]`);
      if (el) el.focus();
    }
    const sel = root.querySelector('#lines tr.sel');
    if (sel) sel.scrollIntoView({ block: 'nearest' });
    renderPay();
  }

  let renderQueued = false;
  function queueRender() {
    if (renderQueued) return;
    renderQueued = true;
    setTimeout(() => {
      renderQueued = false;
      renderCart();
    }, 0);
  }

  function rowKey(el) {
    const tr = el.closest('#lines tr');
    return tr ? Number(tr.dataset.key) : null;
  }

  function applyField(el) {
    const key = rowKey(el);
    const l = findLine(key);
    if (!l) return;
    selKey = key;
    const f = el.dataset.f;
    if (f === 'unit') updateLine(key, { good_unit_id: Number(el.value) });
    if (f === 'qty') {
      const v = parseQty(el.value);
      if (isNaN(v)) {
        toast('Jumlah tidak valid (boleh desimal, mis. 0,5).', 'error');
        el.value = qtyText(l.quantity);
        return;
      }
      if (v <= 0) removeLine(key);
      else if (v !== l.quantity) updateLine(key, { quantity: v });
    }
    if (f === 'disc') {
      const v = parseMoney(el.value);
      if (v > lineGross(l)) {
        toast('Diskon lebih besar dari jumlah harga barang itu.', 'error');
        el.value = l.discount ? rp(l.discount) : '';
        return;
      }
      if (v !== (l.discount || 0)) updateLine(key, { discount: v });
    }
  }

  const tbody = $('#lines');
  tbody.addEventListener('change', (e) => {
    if (e.target.dataset.f) applyField(e.target);
  });
  tbody.addEventListener('input', (e) => {
    if (e.target.dataset.f !== 'disc') return;
    const digits = e.target.value.replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
    e.target.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
  });
  tbody.addEventListener('focusin', (e) => {
    const key = rowKey(e.target);
    if (key !== null && key !== selKey) {
      selKey = key;
      root.querySelectorAll('#lines tr').forEach((tr) => tr.classList.toggle('sel', Number(tr.dataset.key) === key));
    }
    if (e.target.tagName === 'INPUT') setTimeout(() => e.target.select(), 0);
  });
  tbody.addEventListener('keydown', (e) => {
    const f = e.target.dataset.f;
    if (!f) return;
    if (e.key === 'Enter') {
      e.preventDefault();
      applyField(e.target);
      focusSearch();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      renderCart();
      focusSearch();
    }
  });

  // ---------------- Pencarian ----------------
  function renderResults(list, text) {
    lastResults = list;
    lastQuery = text;
    if (!text) {
      results.hidden = true;
      return;
    }
    results.hidden = false;
    if (hi >= list.length) hi = 0;
    if (!list.length) {
      results.innerHTML = `<div class="empty small">Tidak ada barang "${esc(text)}".</div>`;
      return;
    }
    results.innerHTML = list
      .map(
        (g, i) => `
      <div class="pc-result ${i === hi ? 'hi' : ''}" data-i="${i}">
        <div class="pr-main" data-add>
          <div class="pr-name">${esc(g.name)}${g.is_discontinued ? ' <span class="tag">discontinue</span>' : ''}</div>
          <div class="muted small">${esc(g.code || '')} · stok ±${qtyText(g.stock_estimate || 0)}</div>
        </div>
        <div class="chips">${
          g.units.length
            ? g.units
                .map(
                  (u, ui) =>
                    `<button type="button" class="chip" data-unit="${u.good_unit_id}" tabindex="-1" title="Alt+${ui + 1}">${esc(u.unit)} <b>${rp(u.selling_price)}</b></button>`
                )
                .join('')
            : '<span class="muted small">tidak ada harga</span>'
        }</div>
      </div>`
      )
      .join('');
    const h = results.querySelector('.pc-result.hi');
    if (h) h.scrollIntoView({ block: 'nearest' });
  }

  let rejectedText = null; // barcode tak dikenal yang baru ditolak: jangan munculkan daftar "tidak ada" sesudahnya
  const doSearch = debounce(() => {
    if (rejectedText !== null && q.value === rejectedText) return;
    rejectedText = null;
    const { text } = splitQty(q.value);
    hi = 0;
    renderResults(text ? searchGoods(text) : [], text);
  }, 120);
  q.addEventListener('input', doSearch);

  function hideResults() {
    results.hidden = true;
    lastResults = [];
    lastQuery = '';
  }

  function focusSearch() {
    q.focus();
  }

  function add(g, unitId = null, qty = 1) {
    if (!g.units.length) {
      beep(false);
      toast(`${g.name}: belum punya harga jual.`, 'error', 3500);
      return false;
    }
    const unit = unitId ? g.units.find((u) => u.good_unit_id === unitId) || g.units[0] : g.units[0];
    const line = addGood(g, unit, qty);
    selKey = line ? line.key : selKey;
    q.value = '';
    hideResults();
    focusSearch();
    return true;
  }

  function enterSearch() {
    const raw = q.value.trim();
    if (!raw) return;
    const { qty, text } = splitQty(raw);
    // 1) kode/barcode persis (scanner)
    const exact = findByCode(text);
    if (exact) {
      if (add(exact, null, qty)) beep(true);
      return;
    }
    // 2) angka panjang yang tidak ada = barcode tidak dikenal (jangan ambil barang yang "mirip")
    if (looksLikeBarcode(text)) {
      beep(false);
      toast(`Barcode ${text} tidak ada di data barang.`, 'error', 4000);
      rejectedText = q.value;
      q.select(); // scan berikutnya langsung menimpa
      hideResults();
      return;
    }
    // 3) hasil pencarian yang sedang tampil -> ambil yang disorot
    if (!results.hidden && lastQuery === text && lastResults.length) {
      add(lastResults[hi] || lastResults[0], null, qty);
      return;
    }
    const list = searchGoods(text);
    if (list.length === 1) add(list[0], null, qty);
    else {
      hi = 0;
      renderResults(list, text);
      if (!list.length) beep(false);
    }
  }

  q.addEventListener('keydown', (e) => {
    const open = !results.hidden && lastResults.length > 0;
    if (e.key === 'Enter') {
      e.preventDefault();
      enterSearch();
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      const d = e.key === 'ArrowDown' ? 1 : -1;
      if (open) {
        hi = (hi + d + lastResults.length) % lastResults.length;
        renderResults(lastResults, lastQuery);
      } else moveSel(d);
    } else if (e.key === 'PageDown' || e.key === 'PageUp') {
      if (!open) return;
      e.preventDefault();
      hi = Math.max(0, Math.min(lastResults.length - 1, hi + (e.key === 'PageDown' ? 8 : -8)));
      renderResults(lastResults, lastQuery);
    } else if (e.altKey && /^[1-9]$/.test(e.key) && open) {
      e.preventDefault();
      const g = lastResults[hi];
      const u = g && g.units[Number(e.key) - 1];
      if (u) add(g, u.good_unit_id, splitQty(q.value).qty);
    } else if (e.key === 'Escape') {
      if (q.value || !results.hidden) {
        e.preventDefault();
        e.stopPropagation();
        q.value = '';
        hideResults();
      }
    } else if (!q.value && (e.key === '+' || e.key === '=' || e.key === '-')) {
      // Kolom cari kosong: + / - (numpad, atau tombol =) mengubah jumlah baris yang dipilih
      e.preventDefault();
      bumpSel(e.key === '-' ? -1 : 1);
    } else if (!q.value && e.key === 'Delete') {
      e.preventDefault();
      deleteSel();
    }
  });

  results.addEventListener('mousedown', (e) => e.preventDefault()); // fokus tetap di kolom cari
  results.addEventListener('click', (e) => {
    const r = e.target.closest('.pc-result');
    if (!r) return;
    const g = lastResults[Number(r.dataset.i)];
    if (!g) return;
    const chip = e.target.closest('[data-unit]');
    add(g, chip ? Number(chip.dataset.unit) : null, splitQty(q.value).qty);
  });

  // ---------------- Baris terpilih (keyboard) ----------------
  function moveSel(d) {
    if (!cart.lines.length) return;
    let i = cart.lines.findIndex((l) => l.key === selKey);
    i = i < 0 ? cart.lines.length - 1 : Math.max(0, Math.min(cart.lines.length - 1, i + d));
    selKey = cart.lines[i].key;
    renderCart();
  }
  function bumpSel(d) {
    const l = findLine(selKey);
    if (!l) return;
    const v = round2(l.quantity + d);
    if (v <= 0) return deleteSel();
    updateLine(l.key, { quantity: v });
  }
  async function deleteSel() {
    const l = findLine(selKey);
    if (!l) return;
    if (await confirmBox(`Hapus "${l.name}" dari keranjang?`, { ok: 'Hapus', danger: true })) removeLine(l.key);
    focusSearch();
  }
  function editSelQty() {
    if (selKey === null && cart.lines.length) selKey = cart.lines[cart.lines.length - 1].key;
    const el = root.querySelector(`#lines tr[data-key="${selKey}"] [data-f="qty"]`);
    if (el) {
      el.focus();
      el.select();
    }
  }

  // ---------------- Pembayaran ----------------
  function renderPay() {
    const total = grandTotal();
    const qty = cart.lines.reduce((a, l) => a + Number(l.quantity || 0), 0);
    $('#grand').textContent = rp(total);
    $('#count').textContent = cart.lines.length
      ? `${cart.lines.length} barang · ${qtyText(qty)} qty · subtotal ${rp(subtotal())}`
      : 'Keranjang kosong';
    $('#member-name').textContent = cart.memberId ? cart.memberName : 'Tanpa member';
    root.querySelectorAll('[data-pay]').forEach((b) => b.classList.toggle('on', b.dataset.pay === cart.payment));
    $('#cash-box').hidden = cart.payment !== 'cash';
    $('#tf-box').hidden = cart.payment !== 'transfer';
    $('#quick').innerHTML = total
      ? `<button type="button" class="chip" data-amt="${Math.ceil(total)}" tabindex="-1">Uang pas</button>` +
        quickAmounts(total)
          .map((v) => `<button type="button" class="chip" data-amt="${v}" tabindex="-1">${rp(v)}</button>`)
          .join('')
      : '';
    const change = paid - total;
    const ch = $('#change');
    ch.parentElement.classList.toggle('neg', paid > 0 && change < 0);
    ch.textContent = paid > 0 && change < 0 ? 'kurang ' + rp(-change) : paid ? rp(Math.max(0, change)) : '0';
    const printer = hasPrinter();
    $('#btn-save-print').hidden = !printer;
    $('#btn-save').className = printer ? 'btn' : 'btn btn-primary big';
    $('#btn-save').innerHTML = printer ? 'Simpan saja' : `Simpan ${kbd('F12')}`;
  }

  function resetPayInputs() {
    paid = 0;
    $('#paid').value = '';
    $('#tdisc').value = '';
    $('#note').value = '';
    $('#tf-ok').checked = false;
    $('#err').hidden = true;
  }

  moneyInput($('#tdisc'), (v) => setCart({ totalDiscount: v }));
  moneyInput($('#paid'), (v) => {
    paid = v;
    renderPay();
  });
  $('#note').addEventListener('input', debounce(() => setCart({ note: $('#note').value }), 200));
  $('#tdisc').value = cart.totalDiscount ? rp(cart.totalDiscount) : '';
  $('#note').value = cart.note || '';
  for (const id of ['#paid', '#tdisc', '#note']) {
    $(id).addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (id === '#paid') save(hasPrinter());
        else $('#paid').focus();
      }
    });
    $(id).addEventListener('focus', (e) => setTimeout(() => e.target.select(), 0));
  }

  function togglePayment() {
    setCart({ payment: cart.payment === 'cash' ? 'transfer' : 'cash' });
    if (cart.payment === 'cash') $('#paid').focus();
    else $('#tf-ok').focus();
  }

  function chooseMember() {
    const s = openSheet({
      title: 'Pilih member',
      html: `<input id="mq" type="search" placeholder="Cari nama / toko / no. HP lalu Enter" autocomplete="off">
        <div class="member-list pc-member-list" id="ml"></div>`,
      onClose: () => setTimeout(focusSearch, 0),
    });
    const mq = s.body.querySelector('#mq');
    let list = [];
    let mi = 0;
    const draw = () => {
      list = [{ id: null, name: 'Tanpa member' }, ...searchMembers(mq.value)];
      if (mi >= list.length) mi = 0;
      s.body.querySelector('#ml').innerHTML =
        list
          .map(
            (m, i) => `<button type="button" class="member ${i === mi ? 'hi' : ''} ${m.id === cart.memberId ? 'on' : ''}" data-i="${i}" tabindex="-1">
              <b>${esc(m.name)}</b>${m.store_name ? ` · ${esc(m.store_name)}` : ''}
              ${m.id ? `<div class="muted small">${esc(m.phone_number || '')} ${esc(m.address || '')}</div>` : ''}</button>`
          )
          .join('') +
        (list.length > 1 || !mq.value ? '' : '<div class="empty small">Member tidak ditemukan. Kasir PC tidak bisa menambah member baru.</div>');
      const h = s.body.querySelector('.member.hi');
      if (h) h.scrollIntoView({ block: 'nearest' });
    };
    const pick = (i) => {
      const m = list[i];
      if (!m) return;
      setCart({ memberId: m.id, memberName: m.id ? m.name : null });
      s.close();
    };
    mq.addEventListener(
      'input',
      debounce(() => {
        mi = mq.value ? 1 : 0;
        draw();
      }, 120)
    );
    mq.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        mi = (mi + (e.key === 'ArrowDown' ? 1 : -1) + list.length) % list.length;
        draw();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        pick(mi);
      }
    });
    s.body.addEventListener('click', (e) => {
      const m = e.target.closest('.member');
      if (m) pick(Number(m.dataset.i));
    });
    draw();
    mq.focus();
  }

  let saving = false;
  async function save(withPrint) {
    if (saving) return;
    const err = $('#err');
    err.hidden = true;
    setCart({ note: $('#note').value.trim() });
    const total = grandTotal();
    let msg = cartProblem();
    if (!msg && cart.payment === 'cash' && paid < total) {
      msg = paid ? `Uang diterima (${rp(paid)}) kurang dari total (${rp(total)}).` : `Isi uang diterima dulu (${'F8'}), atau pilih "Uang pas".`;
    }
    if (!msg && cart.payment === 'cash' && paid >= 100000000) {
      // Barcode yang ter-scan di kolom uang terbaca sebagai angka sangat besar.
      msg = 'Uang diterima terlalu besar — mungkin barcode ter-scan di kolom uang. Ketik ulang uangnya.';
      paid = 0;
      $('#paid').value = '';
      renderPay();
    }
    if (!msg && cart.payment === 'transfer' && !$('#tf-ok').checked) msg = 'Centang dulu "Transfer sudah masuk" setelah dicek.';
    if (msg) {
      err.hidden = false;
      err.textContent = msg;
      beep(false);
      if (cart.payment === 'cash' && /uang/i.test(msg)) $('#paid').focus();
      return;
    }
    if (cart.payment === 'cash' && paid - total >= 500000) {
      saving = true;
      const ok = await confirmBox(`Uang diterima ${rp(paid)}, kembalian ${rp(paid - total)}. Sudah benar?`, { ok: 'Benar, simpan', cancel: 'Ubah' });
      saving = false;
      if (!ok) return $('#paid').focus();
    }
    saving = true;
    let sale;
    try {
      sale = await saveSale({ cashier: session.cashier, moneyPaid: paid });
    } catch (e) {
      saving = false;
      err.hidden = false;
      err.textContent = 'Gagal menyimpan: ' + e.message;
      return;
    }
    resetCart();
    resetPayInputs();
    saving = false;
    kick(); // kirim ke server di latar belakang bila online
    showDone(sale, withPrint && hasPrinter());
  }

  /** Jendela "tersimpan": kembalian besar, status cetak & kirim. Enter/Esc = transaksi baru. */
  function showDone(sale, doPrint) {
    let printState = doPrint ? 'printing' : 'none';
    let printError = null;
    const s = openSheet({
      title: 'Transaksi tersimpan ✓  ' + sale.receipt_no,
      html: `<div id="done"></div>`,
      onClose: () => {
        document.removeEventListener('keydown', onKey, true);
        offSync();
        setTimeout(focusSearch, 0);
      },
    });
    s.el.classList.add('pc-done');
    const draw = () => {
      let pr = '';
      if (printState === 'printing') pr = '<div class="alert a-yellow">🖨 Mencetak struk…</div>';
      if (printState === 'ok') pr = '<div class="alert a-green">🖨 Struk dicetak ✓</div>';
      if (printState === 'error') pr = `<div class="alert a-red">Struk gagal dicetak: ${esc(printError)}</div>`;
      s.body.querySelector('#done').innerHTML = `
        <div class="center done">
          <div class="muted">Total ${rp(sale.total)} · ${sale.payment === 'transfer' ? 'Transfer' : 'Tunai ' + rp(sale.money_paid)}</div>
          ${sale.payment === 'transfer' ? '<div class="done-big">LUNAS</div>' : `<div class="muted">Kembalian</div><div class="done-big">${rp(sale.money_returned)}</div>`}
          <div>${statusHtml(sale)}</div>
          <div class="muted small">Tersimpan di komputer ini. ${sale.status === 'sent' ? '' : 'Dikirim ke server otomatis saat online.'}</div>
        </div>
        ${pr}
        <div class="btn-row">
          ${hasPrinter() ? `<button type="button" class="btn" data-d="print" ${printState === 'printing' ? 'disabled' : ''}>🖨 ${printState === 'none' ? 'Cetak struk' : 'Cetak ulang'}</button>` : ''}
          <button type="button" class="btn btn-primary big" data-d="new">Transaksi baru ${kbd('Enter')}</button>
        </div>`;
    };
    const doPrintNow = async (reprint) => {
      printState = 'printing';
      draw();
      try {
        await printSale(sale, { reprint });
        printState = 'ok';
      } catch (e) {
        printState = e.cancelled ? 'none' : 'error';
        printError = e.message;
      }
      if (s.el.isConnected) draw();
    };
    const onKey = (e) => {
      if (!s.el.isConnected) return;
      if (e.key === 'Enter' || e.key === 'Escape' || e.key === 'F2') {
        e.preventDefault();
        e.stopPropagation();
        s.close();
      }
    };
    document.addEventListener('keydown', onKey, true);
    s.body.addEventListener('click', (e) => {
      const d = e.target.closest('[data-d]');
      if (!d) return;
      if (d.dataset.d === 'new') s.close();
      if (d.dataset.d === 'print') doPrintNow(printState !== 'none' || sale.printed > 0);
    });
    const offSync = bus.on('sync', async () => {
      const fresh = await db.get('sales', sale.client_uuid);
      if (fresh && fresh.status !== sale.status && s.el.isConnected) {
        sale = { ...fresh };
        draw();
      }
    });
    draw();
    if (doPrint) doPrintNow(false);
  }

  // ---------------- Klik & tombol pintas ----------------
  root.addEventListener('click', async (e) => {
    const pay = e.target.closest('[data-pay]');
    if (pay) {
      setCart({ payment: pay.dataset.pay });
      if (cart.payment === 'cash') $('#paid').focus();
      return;
    }
    const amt = e.target.closest('[data-amt]');
    if (amt) {
      paid = Number(amt.dataset.amt);
      $('#paid').value = rp(paid);
      renderPay();
      $('#paid').focus();
      return;
    }
    const row = e.target.closest('#lines tr');
    const a = e.target.closest('[data-a]');
    if (row && !a && !e.target.closest('input,select')) {
      selKey = Number(row.dataset.key);
      renderCart();
      return;
    }
    if (!a) return;
    const act = a.dataset.a;
    if (act === 'del') {
      const key = rowKey(a);
      const l = findLine(key);
      if (l && (await confirmBox(`Hapus "${l.name}" dari keranjang?`, { ok: 'Hapus', danger: true }))) removeLine(key);
      focusSearch();
    }
    if (act === 'member') chooseMember();
    if (act === 'save') save(false);
    if (act === 'save-print') save(true);
    if (act === 'clear') {
      if (!cart.lines.length) return focusSearch();
      if (await confirmBox('Kosongkan keranjang (batalkan transaksi ini)?', { ok: 'Kosongkan', danger: true })) {
        resetCart();
        resetPayInputs();
      }
      focusSearch();
    }
    if (act === 'riwayat') go('riwayat');
    if (act === 'pengaturan') go('pengaturan');
    if (act === 'lock') go('lock');
  });

  function onKeys(e) {
    if (document.querySelector('.overlay, .busy')) return; // ada jendela lain di atas
    const k = e.key;
    const handled = () => {
      e.preventDefault();
      e.stopPropagation();
    };
    if (k === 'F2') {
      handled();
      focusSearch();
      q.select();
    } else if (k === 'F3') {
      handled();
      editSelQty();
    } else if (k === 'F4') {
      handled();
      chooseMember();
    } else if (k === 'F6') {
      handled();
      $('#tdisc').focus();
    } else if (k === 'F7') {
      handled();
      togglePayment();
    } else if (k === 'F8') {
      handled();
      if (cart.payment !== 'cash') setCart({ payment: 'cash' });
      $('#paid').focus();
    } else if (k === 'F9') {
      handled();
      go('riwayat');
    } else if (k === 'F12' || (e.ctrlKey && k === 'Enter')) {
      handled();
      save(hasPrinter());
    } else if (e.ctrlKey && (k === 'l' || k === 'L')) {
      handled();
      go('lock');
    } else if (k === 'Escape' && document.activeElement !== q) {
      handled();
      focusSearch();
    }
  }
  document.addEventListener('keydown', onKeys);

  // Ketikan/scan saat fokus tidak di kolom isian langsung masuk ke kolom cari.
  function onStrayKey(e) {
    if (document.querySelector('.overlay, .busy')) return;
    const t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
    if (e.ctrlKey || e.altKey || e.metaKey || e.key.length !== 1) return;
    q.focus();
  }
  document.addEventListener('keydown', onStrayKey, true);

  const offStatus = mountStatus(root, (name) =>
    go(name === 'history-rejected' ? 'riwayat' : name === 'settings' ? 'pengaturan' : name, name === 'history-rejected' ? { tab: 'rejected' } : {})
  );
  const offCart = bus.on('cart', queueRender);
  const offCatalog = bus.on('catalog', () => {
    renderCart();
    if (q.value) doSearch();
  });
  if (cart.lines.length) selKey = cart.lines[cart.lines.length - 1].key;
  renderCart();
  setTimeout(focusSearch, 50);

  return {
    cleanup: () => {
      offStatus();
      offCart();
      offCatalog();
      document.removeEventListener('keydown', onKeys);
      document.removeEventListener('keydown', onStrayKey, true);
    },
    back: () => {
      if (q.value || !results.hidden) {
        q.value = '';
        hideResults();
      }
      focusSearch();
    },
  };
}
