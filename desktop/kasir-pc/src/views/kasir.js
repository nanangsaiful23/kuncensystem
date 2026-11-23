// Layar kasir PC. Tata letak & tombol pintas sengaja dibuat SAMA dengan Tambah Transaksi di web
// (resources/views/layout/transaction/form.blade.php) supaya kasir yang bergantian memakai web dan
// Kasir PC tidak perlu belajar ulang:
//   kolom cari di atas · tabel (barang terbaru di atas) · member/pembayaran/catatan di bawah tabel ·
//   panel kanan: total, potongan akhir, bayar + uang cepat, kembali, Proses.
//   F2/F4// cari · F3/↓ ke tabel · F8 bayar · Alt+1..6 uang cepat · Ctrl+Enter proses.
// Tambahan PC: F7 member, F9 riwayat, +/− jumlah baris terakhir, Alt+←/→ ganti satuan di tabel,
// dan barcode yang ter-scan di kolom lain (bayar/jumlah) tetap masuk sebagai barang.
// Perhitungan & penyimpanan memakai modul Kasir HP (cart.js, sales.js) supaya hasilnya sama.

import * as db from '@hp/db.js';
import { go, session } from '@hp/router.js';
import { openSheet, toast, confirmBox, moneyInput, beep } from '@hp/ui.js';
import { esc, rp, qtyText, parseQty, parseMoney, debounce, round2 } from '@hp/util.js';
import { searchGoods, findByCode, goodsCount, searchMembers, goodById } from '@hp/catalog.js';
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
import { tierPlan, tierText } from '@hp/tier.js';
import { mountStatus } from '@hp/status.js';
import { bus } from '@hp/bus.js';
import { kick } from '@hp/sync.js';
import { statusHtml } from '@hp/views/common.js';
import { saveSale, printSale } from '../sales.js';

const kbd = (k) => `<kbd>${k}</kbd>`;
const BARCODE_RE = /^\d{6,}$/; // sama dengan web: angka >= 6 digit = barcode, diproses saat Enter
const SCAN_GAP_MS = 40; // jarak antar tombol dari scanner (manusia jauh lebih lambat)
const SCAN_MIN_LEN = 6;

/** Saran uang bayar (sama dengan web): uang pas, lalu pembulatan ke atas. Maks. 6 (Alt+1..6). */
function payOptions(total) {
  if (!(total > 0)) return [];
  const out = [total];
  for (const d of [1000, 2000, 5000, 10000, 20000, 50000, 100000]) {
    const v = Math.ceil(total / d) * d;
    if (v > total && !out.includes(v)) out.push(v);
  }
  return out.slice(0, 6);
}

/** "5*indomie" -> { qty: 5, text: "indomie" } */
function splitQty(text) {
  const m = String(text).match(/^\s*(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/);
  if (!m) return { qty: 1, text: String(text).trim() };
  const qty = parseQty(m[1]);
  return { qty: qty > 0 ? qty : 1, text: m[2].trim() };
}

/** "Harga grosir: 1 PAK5 + 1 PCS (hemat 2.500)" — sama dengan keterangan di kasir web. */
function tierNote(l) {
  const plan = tierPlan(l);
  return `<div class="tier-note">${plan ? `Harga grosir: <b>${esc(tierText(plan))}</b> (hemat ${rp(plan.saved)})` : ''}</div>`;
}

/** Satuan bawaan = isi 1 (PCS/KG), kalau tidak ada yang terkecil (sama dengan web). */
const defaultUnit = (g) => Math.max(0, g.units.findIndex((u) => Number(u.quantity) === 1));
const unitLabel = (u) => u.unit + (u.quantity > 1 && !String(u.unit).includes(String(u.quantity)) ? ` (${qtyText(u.quantity)})` : '');

export function kasirView(root) {
  if (!session.cashier) {
    go('lock');
    return {};
  }
  const device = db.kv('device') || {};
  const hasPrinter = () => !!(db.kv('printer') && db.kv('printer').address);

  root.innerHTML = `
  <div class="screen kasir pc">
    <header class="topbar pc-top">
      <div class="tb-left">
        <div class="tb-title">Kasir PC <span class="dev-code">${esc(device.code || '')}</span></div>
        <div class="tb-sub">👤 ${esc(session.cashier.name)} · <span data-status="printer"></span></div>
      </div>
      <div class="pc-sync" data-status="strip"></div>
      <div class="tb-right">
        <button type="button" class="badge-btn" data-a="pengaturan" data-status="conn" title="Sambungan (Pengaturan)" tabindex="-1"></button>
        <button type="button" class="tb-btn" data-a="riwayat" tabindex="-1">Riwayat ${kbd('F9')}</button>
        <button type="button" class="tb-btn" data-a="pengaturan" tabindex="-1">Pengaturan</button>
        <button type="button" class="tb-btn" data-a="lock" tabindex="-1">Ganti kasir ${kbd('Ctrl+L')}</button>
      </div>
    </header>
    <div data-status="alerts"></div>
    <div class="pc-body">
      <div class="pc-left">
        <div class="pc-search" id="sr-wrap">
          <label for="q">Cari barang ${kbd('F2')}</label>
          <input id="q" type="text" autocomplete="off" spellcheck="false"
            placeholder="Scan barcode lalu Enter, atau ketik nama barang…   (5*kode = jumlah 5)">
          <span class="pc-hint">${kbd('↓')}/${kbd('F3')} ke tabel · ${kbd('F8')} bayar · ${kbd('Ctrl')}+${kbd('Enter')} proses</span>
          <div class="sr-panel" id="sr-panel" hidden>
            <div class="sr-head"><span id="sr-info"></span>
              <span>${kbd('↑')}${kbd('↓')} pilih · ${kbd('←')}${kbd('→')} satuan · ${kbd('Insert')} / ${kbd('Shift')}+${kbd('↑↓')} centang · ${kbd('Enter')} tambahkan · ${kbd('Esc')} tutup</span></div>
            <div class="sr-list" id="sr-list"></div>
            <div class="sr-foot">
              <button type="button" class="sr-btn" data-sr="all" tabindex="-1">Centang semua</button>
              <button type="button" class="sr-btn primary" data-sr="add" tabindex="-1">Tambahkan <span id="sr-count"></span></button>
            </div>
          </div>
        </div>

        <div class="pc-table">
          <div class="pc-table-head">
            <b>Daftar barang</b>
            <span class="tbl-hint">${kbd('Enter')} baris berikut · ${kbd('↑')}${kbd('↓')} baris · ${kbd('←')}${kbd('→')} kolom · ${kbd('Alt')}+${kbd('←→')} satuan · ${kbd('Alt')}+${kbd('Del')} hapus · ${kbd('Esc')} cari</span>
            <span class="badge-count" id="count-badge">0 barang</span>
          </div>
          <div class="pc-table-scroll" id="table-scroll">
            <table class="cart-table">
              <thead><tr>
                <th class="c-no">#</th><th>Nama barang</th><th class="c-num">Harga</th><th class="c-qty">Jml</th>
                <th class="c-disc">Potongan</th><th class="c-num">Total</th><th class="c-x"></th>
              </tr></thead>
              <tbody id="lines"></tbody>
            </table>
            <div id="empty" class="empty"></div>
          </div>
        </div>

        <div class="pc-info">
          <div class="info-field f-member">
            <label for="member-q">Member ${kbd('F7')} <span class="mem-tag" id="mem-tag">Umum</span></label>
            <div class="mem-wrap">
              <input id="member-q" type="text" autocomplete="off" spellcheck="false" placeholder="Ketik nama member / toko / no. HP, Enter">
              <div class="mem-list" id="mem-list" hidden></div>
            </div>
          </div>
          <div class="info-field f-pay">
            <label>Pembayaran</label>
            <div class="seg" role="radiogroup" id="pay-seg" aria-label="Jenis pembayaran">
              <button type="button" role="radio" data-pay="cash">💵 Tunai</button>
              <button type="button" role="radio" data-pay="transfer">🏦 Transfer</button>
            </div>
          </div>
          <div class="info-field f-note">
            <label for="note">Catatan</label>
            <input id="note" type="text" maxlength="255" autocomplete="off" placeholder="Opsional…">
          </div>
        </div>
      </div>

      <aside class="pc-side">
        <div class="pos-total"><div class="lbl">Total akhir</div><div class="val" id="grand">0</div></div>
        <div class="side-card">
          <div class="sum-line"><span>Total harga <small id="s-count"></small></span><span id="s-gross">0</span></div>
          <div class="sum-line"><span>Potongan per barang</span><span id="s-disc">0</span></div>
          <div class="inline-row"><label for="tdisc">Potongan akhir</label>
            <input id="tdisc" inputmode="numeric" placeholder="0" autocomplete="off"></div>
        </div>
        <div class="side-card" id="cash-box">
          <label for="paid">Bayar ${kbd('F8')}</label>
          <input id="paid" inputmode="numeric" class="big-input" placeholder="0" autocomplete="off">
          <div class="pay-sugg" id="quick"></div>
          <div class="inline-row"><label>Kembali</label><div class="pos-change" id="change">0</div></div>
        </div>
        <div class="side-card" id="tf-box" hidden>
          <label class="check"><input type="checkbox" id="tf-ok"> Transfer sudah masuk (sudah saya cek) ${kbd('F8')}</label>
          <div class="hint">Transfer dicek manual oleh kasir. Uang bayar = total.</div>
        </div>
        <div id="err" class="error" hidden></div>
        <div class="pc-process-wrap">
          <label class="check print-opt" id="print-opt"><input type="checkbox" id="print-ok" tabindex="-1"> Cetak struk</label>
          <button type="button" class="btn btn-primary big pc-process" data-a="process">✓ Proses transaksi ${kbd('Ctrl+Enter')}</button>
          <button type="button" class="link-btn" data-a="clear" tabindex="-1">Batalkan transaksi ini</button>
        </div>
      </aside>
    </div>
  </div>`;

  const $ = (s) => root.querySelector(s);
  const q = $('#q');
  const tbody = $('#lines');
  const sr = { items: [], active: -1, open: false, query: '' };
  let selKey = null; // baris yang terakhir ditambah/disentuh (+ / − / Alt+Del)
  let flashKey = null;
  let paid = 0;
  let rejectedText = null;

  // ======================= TABEL =======================
  // Barang terbaru di paling atas (sama dengan web), nomor urut tetap: yang pertama dimasukkan = 1.
  const rowsInView = () => cart.lines.slice().reverse();

  function rowHtml(l, no) {
    const g = goodById(l.good_id);
    const st = g && g.stock_estimate != null ? Number(g.stock_estimate) : null;
    const stock = st === null ? '' : ` · <span class="${st <= 0 ? 'low' : ''}">stok ±${qtyText(st)}</span>`;
    const multi = l.units.length > 1;
    return `
      <tr data-key="${l.key}" class="${no % 2 ? 'odd' : 'even'}${l.key === selKey ? ' sel' : ''}${l.key === flashKey ? ' flash' : ''}">
        <td class="c-no">${no}</td>
        <td class="c-name"><div class="ln">${esc(l.name)}
          <button type="button" class="unit-tag${multi ? ' multi' : ''}" data-a="unit" tabindex="-1"
            title="${multi ? 'Ganti satuan (Alt+← / Alt+→)' : 'Satuan'}">${esc(l.unit)}${multi ? ' ▾' : ''}</button></div>
          <div class="muted small">${esc(l.code || '')}${stock}</div>${tierNote(l)}</td>
        <td class="c-num">${rp(l.price)}</td>
        <td class="c-qty"><input data-f="qty" class="num qty" value="${qtyText(l.quantity)}" autocomplete="off" inputmode="decimal"></td>
        <td class="c-disc"><input data-f="disc" class="num" value="${l.discount ? rp(l.discount) : ''}" placeholder="0" autocomplete="off" inputmode="numeric"></td>
        <td class="c-num b" data-sum>${rp(lineSum(l))}</td>
        <td class="c-x"><button type="button" class="x-btn" data-a="del" tabindex="-1" title="Hapus (Alt+Del)">✕</button></td>
      </tr>`;
  }

  function renderCart() {
    // Fokus di tabel dipertahankan saat tabel digambar ulang.
    const af = document.activeElement;
    const afRow = af && af.closest && af.closest('#lines tr');
    const focus = afRow && af.dataset.f ? { key: afRow.dataset.key, f: af.dataset.f } : null;

    if (selKey !== null && !findLine(selKey)) selKey = cart.lines.length ? cart.lines[cart.lines.length - 1].key : null;
    const rows = rowsInView();
    tbody.innerHTML = rows.map((l, i) => rowHtml(l, rows.length - i)).join('');
    $('#empty').hidden = rows.length > 0;
    $('#empty').innerHTML = goodsCount()
      ? `Belum ada barang. Scan barcode atau ketik nama barang di kolom cari ${kbd('F2')}.`
      : 'Data barang kosong. Buka Pengaturan › Unduh ulang data (perlu internet).';
    $('#count-badge').textContent = `${rows.length} barang`;
    if (focus) {
      const el = root.querySelector(`#lines tr[data-key="${focus.key}"] [data-f="${focus.f}"]`);
      if (el) el.focus();
    }
    const fl = flashKey !== null && root.querySelector(`#lines tr[data-key="${flashKey}"]`);
    if (fl) {
      fl.scrollIntoView({ block: 'nearest' });
      const k = flashKey;
      setTimeout(() => {
        if (flashKey === k) flashKey = null;
        const el = root.querySelector(`#lines tr[data-key="${k}"]`);
        if (el) el.classList.remove('flash');
      }, 1200);
    }
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

  const rowKeyOf = (el) => {
    const tr = el && el.closest && el.closest('#lines tr');
    return tr ? Number(tr.dataset.key) : null;
  };
  const fieldEl = (key, f) => root.querySelector(`#lines tr[data-key="${key}"] [data-f="${f}"]`);
  function focusField(key, f) {
    const el = fieldEl(key, f);
    if (!el) return false;
    el.focus();
    el.select();
    el.scrollIntoView({ block: 'nearest' });
    return true;
  }
  function focusFirstRow() {
    const rows = rowsInView();
    if (!rows.length) {
      toast('Belum ada barang di tabel.', 'info');
      return;
    }
    focusField(rows[0].key, 'qty');
  }

  /** Simpan isi kolom ke keranjang. false = isi tidak valid (fokus tetap di kolom itu). */
  function applyField(el) {
    const key = rowKeyOf(el);
    const l = findLine(key);
    if (!l) return true;
    selKey = key;
    if (el.dataset.f === 'qty') {
      const v = parseQty(el.value);
      if (isNaN(v) || v <= 0) {
        toast('Jumlah harus lebih dari 0 (boleh desimal, mis. 0,5). Hapus baris: Alt+Del.', 'warn');
        el.value = qtyText(l.quantity);
        return false;
      }
      updateLine(key, { quantity: v }); // selalu: angka saat mengetik sudah dipratinjau, ini yang menyimpan draf
    }
    if (el.dataset.f === 'disc') {
      const v = parseMoney(el.value);
      if (v > lineGross(l)) {
        toast('Potongan lebih besar dari harga barang itu.', 'error');
        el.value = l.discount ? rp(l.discount) : '';
        return false;
      }
      updateLine(key, { discount: v });
    }
    return true;
  }

  /** Saat mengetik di tabel: total baris & total akhir langsung ikut (tanpa menggambar ulang tabel). */
  function previewField(el) {
    const l = findLine(rowKeyOf(el));
    if (!l) return;
    if (el.dataset.f === 'qty') {
      const v = parseQty(el.value);
      if (!isNaN(v) && v > 0) l.quantity = v;
    } else {
      const digits = el.value.replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
      el.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
      const v = parseMoney(el.value);
      if (v <= lineGross(l)) l.discount = v;
    }
    const tr = el.closest('tr');
    const sum = tr.querySelector('[data-sum]');
    if (sum) sum.textContent = rp(lineSum(l));
    const note = tr.querySelector('.tier-note');
    if (note) note.outerHTML = tierNote(l);
    renderPay();
  }

  function moveRow(key, f, d) {
    const rows = rowsInView();
    const i = rows.findIndex((l) => l.key === key) + d;
    if (i < 0) return focusSearch();
    if (i >= rows.length) return d > 0 && f === 'qty' ? focusSearch() : undefined;
    focusField(rows[i].key, f);
  }

  function cycleUnit(key, d) {
    const l = findLine(key);
    if (!l || l.units.length < 2) return;
    const i = l.units.findIndex((u) => u.good_unit_id === l.good_unit_id);
    const u = l.units[(i + d + l.units.length) % l.units.length];
    selKey = key;
    updateLine(key, { good_unit_id: u.good_unit_id });
    toast(`${l.name}: ${u.unit} ${rp(u.selling_price)}`, 'info', 1500);
  }

  async function deleteRow(key) {
    const l = findLine(key);
    if (!l) return;
    const rows = rowsInView();
    const i = rows.findIndex((x) => x.key === key);
    if (!(await confirmBox(`Hapus "${l.name}" (${l.unit}) dari daftar?`, { ok: 'Hapus', danger: true }))) {
      focusField(key, 'qty');
      return;
    }
    removeLine(key);
    const left = rowsInView();
    if (left.length) setTimeout(() => focusField(left[Math.min(i, left.length - 1)].key, 'qty'), 0);
    else focusSearch();
  }

  tbody.addEventListener('change', (e) => {
    if (e.target.dataset.f) applyField(e.target);
  });
  tbody.addEventListener('input', (e) => {
    if (e.target.dataset.f) previewField(e.target);
  });
  tbody.addEventListener('focusin', (e) => {
    const key = rowKeyOf(e.target);
    if (key !== null && key !== selKey) {
      selKey = key;
      tbody.querySelectorAll('tr').forEach((tr) => tr.classList.toggle('sel', Number(tr.dataset.key) === key));
    }
    // isi kolom terblok saat fokus: ketikan menimpa (mencegah "1" jadi "12"), juga saat diklik
    if (e.target.tagName === 'INPUT') setTimeout(() => e.target === document.activeElement && e.target.select(), 0);
  });
  tbody.addEventListener('mousedown', (e) => {
    if (e.target.tagName === 'INPUT' && e.target !== document.activeElement) e.target._justFocused = true;
  });
  tbody.addEventListener('mouseup', (e) => {
    if (e.target._justFocused) {
      e.target._justFocused = false;
      e.preventDefault();
    }
  });
  // Kolom di tabel: Enter/↑/↓/←/→/Esc seperti web.
  tbody.addEventListener('keydown', (e) => {
    const t = e.target;
    const f = t.dataset.f;
    if (!f) return;
    const key = rowKeyOf(t);
    const stop = () => {
      e.preventDefault();
      e.stopPropagation();
    };
    if (e.key === 'Escape') {
      stop();
      applyField(t);
      focusSearch();
    } else if (e.altKey && e.key === 'Delete') {
      stop();
      deleteRow(key);
    } else if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
      stop();
      if (applyField(t)) cycleUnit(key, e.key === 'ArrowRight' ? 1 : -1);
    } else if (e.key === 'Enter') {
      if (e.ctrlKey) return; // Ctrl+Enter = proses (tombol pintas umum)
      stop();
      if (!applyField(t)) return;
      moveRow(key, 'qty', e.shiftKey ? -1 : 1); // Enter: baris berikut (baris terakhir -> kembali ke cari)
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      stop();
      if (!applyField(t)) return;
      moveRow(key, f, e.key === 'ArrowDown' ? 1 : -1);
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
      const len = t.value.length;
      const all = t.selectionStart === 0 && t.selectionEnd === len;
      const atEnd = t.selectionStart === len && t.selectionEnd === len;
      const atStart = t.selectionStart === 0 && t.selectionEnd === 0;
      const other = f === 'qty' ? 'disc' : 'qty';
      if ((e.key === 'ArrowRight' && f === 'qty' && (all || atEnd)) || (e.key === 'ArrowLeft' && f === 'disc' && (all || atStart))) {
        stop();
        if (applyField(t)) focusField(key, other);
      }
    }
  });

  // ======================= CARI BARANG =======================
  function focusSearch() {
    q.focus();
    q.select();
  }

  function closeResults() {
    sr.open = false;
    sr.items = [];
    sr.active = -1;
    sr.query = '';
    $('#sr-panel').hidden = true;
  }

  function openResults(text) {
    const list = searchGoods(text);
    const prev = sr.query === text ? sr.items : [];
    sr.items = list.map((g) => {
      const old = prev.find((x) => x.g.id === g.id);
      return old || { g, sel: defaultUnit(g), checked: false };
    });
    sr.query = text;
    sr.open = true;
    sr.active = sr.items.length ? Math.min(Math.max(sr.active, 0), sr.items.length - 1) : -1;
    paintResults();
  }

  function paintResults() {
    const panel = $('#sr-panel');
    panel.hidden = !sr.open;
    if (!sr.open) return;
    const list = $('#sr-list');
    const scroll = list.scrollTop;
    if (!sr.items.length) {
      list.innerHTML = `<div class="sr-state">Tidak ada barang "${esc(sr.query)}".</div>`;
    } else {
      list.innerHTML = sr.items
        .map((it, i) => {
          const g = it.g;
          const st = Number(g.stock_estimate || 0);
          const units = g.units.length
            ? g.units
                .map((u, j) => `<span class="sr-unit${j === it.sel ? ' on' : ''}" data-u="${j}">${esc(unitLabel(u))} <b>${rp(u.selling_price)}</b></span>`)
                .join('')
            : '<span class="muted small">belum ada harga jual</span>';
          return `
          <div class="sr-item${it.checked ? ' checked' : ''}${i === sr.active ? ' active' : ''}" data-i="${i}">
            <input type="checkbox" tabindex="-1"${it.checked ? ' checked' : ''}>
            <div class="sr-main">
              <div class="sr-line"><span class="sr-name">${esc(g.name)}</span>${g.is_discontinued ? '<span class="tag">discontinue</span>' : ''}
                ${g.code ? `<span class="sr-code">${esc(g.code)}</span>` : ''}
                <span class="sr-stock ${st < 0 ? 'minus' : st === 0 ? 'zero' : ''}">${st === 0 ? 'kosong' : `${st < 0 ? 'minus ' : 'stok '}${qtyText(Math.abs(st))}`}</span></div>
              <div class="sr-units">${units}</div>
            </div>
          </div>`;
        })
        .join('');
      list.scrollTop = scroll;
    }
    $('#sr-info').textContent = `${sr.items.length} barang`;
    const n = sr.items.filter((x) => x.checked).length;
    $('#sr-count').textContent = n ? `(${n})` : '';
    const act = list.querySelector('.sr-item.active');
    if (act) act.scrollIntoView({ block: 'nearest' });
  }

  function setActive(i) {
    if (i < 0 || i >= sr.items.length) return;
    sr.active = i;
    paintResults();
  }
  function toggleCheck(i, force) {
    const it = sr.items[i];
    if (!it) return;
    it.checked = force === undefined ? !it.checked : force;
    paintResults();
  }

  /** Masukkan barang ke tabel. Barang + satuan yang sama: jumlah ditambah (seperti web). */
  function add(g, unitIdx, qty = 1, { quiet = false } = {}) {
    const unit = g.units[unitIdx] || g.units[defaultUnit(g)];
    if (!unit) {
      beep(false);
      toast(`${g.name}: belum punya harga jual.`, 'error', 3500);
      return false;
    }
    const existed = cart.lines.some((l) => l.good_unit_id === unit.good_unit_id && !l.discount);
    const line = addGood(g, unit, qty);
    if (!line) return false;
    selKey = flashKey = line.key;
    if (existed && !quiet) toast(`"${g.name} ${unit.unit}" sudah ada, jumlah jadi ${qtyText(line.quantity)}`, 'info', 2200);
    const st = Number(g.stock_estimate);
    if (!existed && g.stock_estimate != null && st <= 0) toast(`Stok ${g.name}: ${qtyText(st)} (habis / minus)`, 'warn', 3000);
    return true;
  }

  function addPicked(items, qty) {
    for (const it of items) add(it.g, it.sel, qty, { quiet: items.length > 1 });
    q.value = '';
    rejectedText = null;
    closeResults();
    focusSearch();
  }

  /** Kode dari scanner (atau Enter pada kode). true bila barang ketemu. */
  function addByCode(code, qty = 1) {
    const g = findByCode(code);
    if (!g) return false;
    if (add(g, defaultUnit(g), qty)) beep(true);
    return true;
  }

  function enterSearch() {
    const raw = q.value.trim();
    if (!raw) return;
    const { qty, text } = splitQty(raw);
    // 1) kode/barcode persis (scanner)
    if (addByCode(text, qty)) {
      q.value = '';
      closeResults();
      focusSearch();
      return;
    }
    // 2) angka panjang yang tidak ada = barcode tidak dikenal: jangan ambil barang yang "mirip"
    if (BARCODE_RE.test(text)) {
      beep(false);
      toast(`Barcode ${text} tidak ditemukan.`, 'error', 4000);
      rejectedText = q.value;
      closeResults();
      q.select(); // scan berikutnya langsung menimpa
      return;
    }
    // 3) daftar hasil yang sedang tampil: yang dicentang, atau yang disorot
    if (sr.open && sr.query === text && sr.items.length) {
      const checked = sr.items.filter((x) => x.checked);
      addPicked(checked.length ? checked : [sr.items[sr.active] || sr.items[0]], qty);
      return;
    }
    openResults(text);
    if (sr.items.length === 1) addPicked([sr.items[0]], qty); // Enter + 1 hasil -> langsung masuk (seperti web)
    else if (!sr.items.length) beep(false);
  }

  const liveSearch = debounce(() => {
    if (rejectedText !== null && q.value === rejectedText) return;
    rejectedText = null;
    const { text } = splitQty(q.value);
    if (!text) return closeResults();
    if (BARCODE_RE.test(text)) return closeResults(); // tunggu Enter dari scanner
    sr.active = 0;
    openResults(text);
  }, 120);
  q.addEventListener('input', liveSearch);

  q.addEventListener('keydown', (e) => {
    const k = e.key;
    const stop = () => {
      e.preventDefault();
      e.stopPropagation();
    };
    if (k === 'Enter') {
      if (e.ctrlKey) return;
      stop();
      enterSearch();
      return;
    }
    if (!sr.open) {
      if (k === 'ArrowDown') {
        stop();
        const { text } = splitQty(q.value);
        if (text && !BARCODE_RE.test(text)) {
          sr.active = 0;
          openResults(text);
        } else focusFirstRow();
      } else if (k === 'Escape' && q.value) {
        stop();
        q.value = '';
      } else if (!q.value && (k === '+' || k === '=' || k === '-')) {
        // kolom cari kosong: + / − mengubah jumlah baris terakhir
        stop();
        bumpSel(k === '-' ? -1 : 1);
      }
      return;
    }
    if (k === 'ArrowDown' || k === 'ArrowUp') {
      stop();
      if (e.shiftKey) toggleCheck(sr.active, true);
      setActive(sr.active + (k === 'ArrowDown' ? 1 : -1));
    } else if (k === 'PageDown' || k === 'PageUp') {
      stop();
      setActive(Math.max(0, Math.min(sr.items.length - 1, sr.active + (k === 'PageDown' ? 8 : -8))));
    } else if ((k === 'ArrowLeft' || k === 'ArrowRight') && sr.items[sr.active] && sr.items[sr.active].g.units.length > 1) {
      stop(); // ← → ganti satuan (barang 1 satuan: kursor teks biasa)
      const it = sr.items[sr.active];
      it.sel = Math.max(0, Math.min(it.g.units.length - 1, it.sel + (k === 'ArrowRight' ? 1 : -1)));
      paintResults();
    } else if (k === 'Insert') {
      stop();
      toggleCheck(sr.active);
    } else if (k === 'Escape') {
      stop();
      closeResults();
    }
  });

  const panel = $('#sr-panel');
  panel.addEventListener('mousedown', (e) => e.preventDefault()); // klik di panel: fokus tetap di kolom cari
  panel.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-sr]');
    if (btn && btn.dataset.sr === 'all') {
      sr.items.forEach((x) => (x.checked = true));
      paintResults();
      return;
    }
    if (btn && btn.dataset.sr === 'add') {
      const checked = sr.items.filter((x) => x.checked);
      const pick = checked.length ? checked : sr.items[sr.active] ? [sr.items[sr.active]] : [];
      if (pick.length) addPicked(pick, splitQty(q.value).qty);
      return;
    }
    const row = e.target.closest('.sr-item');
    if (!row) return;
    const i = Number(row.dataset.i);
    const unit = e.target.closest('.sr-unit');
    if (unit) {
      // klik satuan = pilih satuan itu (seperti web); klik lagi pada satuan terpilih = langsung tambahkan
      const it = sr.items[i];
      const j = Number(unit.dataset.u);
      if (it.sel === j && sr.active === i) return addPicked([it], splitQty(q.value).qty);
      sr.active = i;
      it.sel = j;
      paintResults();
      return;
    }
    sr.active = i;
    toggleCheck(i);
  });
  panel.addEventListener('dblclick', (e) => {
    const row = e.target.closest('.sr-item');
    if (row && sr.items[Number(row.dataset.i)]) addPicked([sr.items[Number(row.dataset.i)]], splitQty(q.value).qty);
  });
  const onDocDown = (e) => {
    if (sr.open && !e.target.closest('#sr-wrap')) closeResults();
    if (!e.target.closest('.mem-wrap')) closeMembers();
  };
  document.addEventListener('mousedown', onDocDown);

  // ---- baris terakhir: + / − / hapus ----
  function bumpSel(d) {
    const l = findLine(selKey) || cart.lines[cart.lines.length - 1];
    if (!l) return;
    const v = round2(l.quantity + d);
    if (v <= 0) return deleteRow(l.key);
    selKey = flashKey = l.key;
    updateLine(l.key, { quantity: v });
  }

  // ======================= MEMBER (di bawah tabel, seperti web) =======================
  const mq = $('#member-q');
  const mem = { items: [], active: -1, open: false };
  function setMember(m, keepText = false) {
    setCart({ memberId: m ? m.id : null, memberName: m ? m.name : null });
    if (!keepText) mq.value = m ? m.name : '';
    closeMembers();
    renderMemberTag();
  }
  function renderMemberTag() {
    const tag = $('#mem-tag');
    tag.textContent = cart.memberId ? cart.memberName : 'Umum';
    tag.classList.toggle('picked', !!cart.memberId);
  }
  function closeMembers() {
    mem.open = false;
    mem.items = [];
    mem.active = -1;
    $('#mem-list').hidden = true;
  }
  function paintMembers() {
    const el = $('#mem-list');
    el.hidden = false;
    mem.open = true;
    el.innerHTML = mem.items.length
      ? mem.items
          .map(
            (m, i) => `<div class="mem-item${i === mem.active ? ' active' : ''}" data-i="${i}"><b>${esc(m.name)}</b>${m.store_name ? ` · ${esc(m.store_name)}` : ''}
              <small>${esc([m.phone_number, m.address].filter(Boolean).join(' · '))}</small></div>`
          )
          .join('')
      : '<div class="mem-item none">Member tidak ditemukan. Kasir PC tidak bisa menambah member baru.</div>';
    const a = el.querySelector('.mem-item.active');
    if (a) a.scrollIntoView({ block: 'nearest' });
  }
  const memberSearch = debounce(() => {
    const t = mq.value.trim();
    if (!t) return closeMembers();
    mem.items = searchMembers(t, 30);
    mem.active = mem.items.length ? 0 : -1;
    paintMembers();
  }, 120);
  mq.addEventListener('input', () => {
    if (cart.memberId) setMember(null, true); // mengetik = member belum dipilih (seperti web)
    memberSearch();
  });
  mq.addEventListener('focus', () => setTimeout(() => mq === document.activeElement && mq.select(), 0));
  mq.addEventListener('keydown', (e) => {
    const k = e.key;
    if (k === 'Enter') {
      if (e.ctrlKey) return;
      e.preventDefault();
      e.stopPropagation();
      if (mem.open && mem.items[mem.active]) {
        setMember(mem.items[mem.active]);
        focusPay();
        return;
      }
      if (!mq.value.trim()) {
        setMember(null);
        focusPay();
        return;
      }
      if (cart.memberId) return focusPay();
      mem.items = searchMembers(mq.value.trim(), 30);
      mem.active = mem.items.length ? 0 : -1;
      paintMembers();
    } else if (mem.open && (k === 'ArrowDown' || k === 'ArrowUp')) {
      e.preventDefault();
      const i = mem.active + (k === 'ArrowDown' ? 1 : -1);
      if (i >= 0 && i < mem.items.length) {
        mem.active = i;
        paintMembers();
      }
    } else if (k === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      if (mem.open) closeMembers();
      else focusSearch();
    }
  });
  $('#mem-list').addEventListener('mousedown', (e) => {
    e.preventDefault();
    const it = e.target.closest('.mem-item[data-i]');
    if (it) {
      setMember(mem.items[Number(it.dataset.i)]);
      focusPay();
    }
  });

  // ======================= PEMBAYARAN =======================
  const paidEl = $('#paid');
  const tdisc = $('#tdisc');
  const note = $('#note');
  let payOpts = [];

  function focusPay() {
    if (cart.payment === 'transfer') $('#tf-ok').focus();
    else {
      paidEl.focus();
      paidEl.select();
    }
  }

  function renderPay() {
    const total = grandTotal();
    const gross = round2(cart.lines.reduce((a, l) => a + lineGross(l), 0));
    const disc = round2(cart.lines.reduce((a, l) => a + (l.discount || 0), 0));
    const qty = cart.lines.reduce((a, l) => a + Number(l.quantity || 0), 0);
    $('#grand').textContent = rp(total);
    $('#s-gross').textContent = rp(gross);
    $('#s-disc').textContent = disc ? '-' + rp(disc) : '0';
    $('#s-count').textContent = cart.lines.length ? `(${cart.lines.length} brg · ${qtyText(qty)} qty)` : '';
    root.querySelectorAll('[data-pay]').forEach((b) => {
      const on = b.dataset.pay === cart.payment;
      b.classList.toggle('on', on);
      b.setAttribute('aria-checked', on ? 'true' : 'false');
      b.tabIndex = on ? 0 : -1;
    });
    $('#cash-box').hidden = cart.payment !== 'cash';
    $('#tf-box').hidden = cart.payment !== 'transfer';
    payOpts = payOptions(total);
    $('#quick').innerHTML = payOpts
      .map(
        (v, i) =>
          `<button type="button" class="pay-chip${i === 0 ? ' exact' : ''}" data-amt="${i}" tabindex="-1">${i === 0 ? 'Uang pas ' : ''}${rp(v)}${kbd('Alt+' + (i + 1))}</button>`
      )
      .join('');
    const change = round2(paid - total);
    const ch = $('#change');
    ch.classList.toggle('short', paid > 0 && change < 0);
    ch.textContent = !paidEl.value ? '0' : change < 0 ? 'Kurang ' + rp(-change) : rp(change);
    const printer = hasPrinter();
    $('#print-opt').hidden = !printer;
    $('#print-ok').checked = printer && db.kv('autoPrint') !== false;
  }

  function resetPayInputs() {
    paid = 0;
    paidEl.value = '';
    tdisc.value = '';
    note.value = '';
    mq.value = '';
    $('#tf-ok').checked = false;
    $('#err').hidden = true;
    renderMemberTag();
  }

  function pickPay(i) {
    if (payOpts[i] === undefined || cart.payment !== 'cash') return;
    paid = payOpts[i];
    paidEl.value = rp(paid);
    renderPay();
    paidEl.focus();
    paidEl.select();
  }

  function setPayment(p) {
    setCart({ payment: p });
    renderPay();
  }

  moneyInput(tdisc, (v) => setCart({ totalDiscount: v }));
  moneyInput(paidEl, (v) => {
    paid = v;
    renderPay();
  });
  note.addEventListener('input', debounce(() => setCart({ note: note.value }), 200));
  tdisc.value = cart.totalDiscount ? rp(cart.totalDiscount) : '';
  note.value = cart.note || '';
  if (cart.memberId) mq.value = cart.memberName || '';
  renderMemberTag();

  for (const el of [paidEl, tdisc, note]) {
    el.addEventListener('focus', () => setTimeout(() => el === document.activeElement && el.select(), 0));
  }
  paidEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.ctrlKey) {
      e.preventDefault();
      process();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      focusSearch();
    }
  });
  tdisc.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.ctrlKey) {
      e.preventDefault();
      focusPay();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      focusSearch();
    }
  });
  note.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.ctrlKey) {
      e.preventDefault();
      focusPay();
    }
  });
  $('#tf-ok').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.ctrlKey) {
      e.preventDefault();
      if (!$('#tf-ok').checked) $('#tf-ok').checked = true;
      process();
    }
  });
  // Pembayaran: ← → (atau T / C) mengganti Tunai / Transfer saat tombolnya difokuskan.
  $('#pay-seg').addEventListener('keydown', (e) => {
    if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
      e.preventDefault();
      setPayment(cart.payment === 'cash' ? 'transfer' : 'cash');
      root.querySelector(`[data-pay="${cart.payment}"]`).focus();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      focusPay();
    }
  });
  $('#print-ok').addEventListener('change', (e) => db.setKv('autoPrint', e.target.checked ? null : false));

  // ======================= PROSES =======================
  let saving = false;
  async function process() {
    if (saving) return;
    const err = $('#err');
    err.hidden = true;
    const fail = (msg, focusEl) => {
      err.hidden = false;
      err.textContent = msg;
      beep(false);
      toast(msg, 'error', 3500);
      if (focusEl) {
        focusEl.focus();
        if (focusEl.select) focusEl.select();
      }
    };
    // isian tabel yang sedang diketik ikut disimpan dulu
    const af = document.activeElement;
    if (af && af.dataset && af.dataset.f && !applyField(af)) return;
    setCart({ note: note.value.trim() });

    if (!cart.lines.length) return fail('Pilih minimal satu barang.', q);
    const problem = cartProblem();
    if (problem) return fail(problem);
    const total = grandTotal();
    if (cart.payment === 'cash') {
      if (!paidEl.value.trim()) return fail('Isi jumlah uang yang dibayar (F8), atau pilih Uang pas (Alt+1).', paidEl);
      if (paid >= 100000000) {
        paid = 0;
        paidEl.value = '';
        renderPay();
        return fail('Uang bayar terlalu besar — mungkin barcode ter-scan di kolom uang. Ketik ulang uangnya.', paidEl);
      }
      if (paid < total) return fail(`Uang bayar (${rp(paid)}) kurang dari total (${rp(total)}). Kasir PC tidak bisa membuat hutang.`, paidEl);
      if (paid - total >= 500000) {
        saving = true;
        const ok = await confirmBox(`Uang bayar ${rp(paid)}, kembalian ${rp(paid - total)}. Sudah benar?`, { ok: 'Benar, proses', cancel: 'Ubah' });
        saving = false;
        if (!ok) return focusPay();
      }
    } else if (!$('#tf-ok').checked) {
      return fail('Centang dulu "Transfer sudah masuk" setelah dicek (F8 lalu Spasi).', $('#tf-ok'));
    }

    saving = true;
    let sale;
    try {
      sale = await saveSale({ cashier: session.cashier, moneyPaid: paid });
    } catch (e) {
      saving = false;
      return fail('Gagal menyimpan: ' + e.message);
    }
    const doPrint = hasPrinter() && $('#print-ok').checked;
    resetCart();
    resetPayInputs();
    saving = false;
    kick(); // kirim ke server di latar belakang bila online
    showDone(sale, doPrint);
  }

  /** Jendela "tersimpan": kembalian besar, status cetak & kirim. Enter / Esc / F2 = transaksi baru. */
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
          ${hasPrinter() ? `<button type="button" class="btn" data-d="print" ${printState === 'printing' ? 'disabled' : ''}>🖨 ${printState === 'none' ? 'Cetak struk' : 'Cetak ulang'} ${kbd('P')}</button>` : ''}
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
      } else if ((e.key === 'p' || e.key === 'P') && hasPrinter() && printState !== 'printing') {
        e.preventDefault();
        e.stopPropagation();
        doPrintNow(printState !== 'none' || sale.printed > 0);
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

  // ======================= KLIK =======================
  root.addEventListener('click', async (e) => {
    const pay = e.target.closest('[data-pay]');
    if (pay) {
      setPayment(pay.dataset.pay);
      focusPay();
      return;
    }
    const amt = e.target.closest('[data-amt]');
    if (amt) return pickPay(Number(amt.dataset.amt));
    const a = e.target.closest('[data-a]');
    if (!a) return;
    const act = a.dataset.a;
    if (act === 'del') deleteRow(rowKeyOf(a));
    if (act === 'unit') cycleUnit(rowKeyOf(a), 1);
    if (act === 'process') process();
    if (act === 'clear') {
      if (!cart.lines.length && !paidEl.value) return focusSearch();
      if (await confirmBox('Batalkan transaksi ini (kosongkan daftar barang)?', { ok: 'Batalkan transaksi', cancel: 'Tidak', danger: true })) {
        resetCart();
        resetPayInputs();
      }
      focusSearch();
    }
    if (act === 'riwayat') go('riwayat');
    if (act === 'pengaturan') go('pengaturan');
    if (act === 'lock') go('lock');
  });
  root.querySelectorAll('.pay-sugg, #sr-panel').forEach((el) => el.addEventListener('mousedown', (e) => e.target.closest('button') && e.preventDefault()));

  // ======================= TOMBOL PINTAS (sama dengan web + tambahan PC) =======================
  const typing = (t) => t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
  function onKeys(e) {
    if (document.querySelector('.overlay, .busy')) return; // ada jendela lain di atas
    const k = e.key;
    const handled = () => {
      e.preventDefault();
      e.stopPropagation();
    };
    if (k === 'F2' || k === 'F4' || (k === '/' && !typing(e.target))) {
      handled();
      focusSearch();
    } else if (k === 'F3') {
      handled();
      focusFirstRow();
    } else if (k === 'F6') {
      handled();
      toast('Retur hanya bisa di kasir web (perlu online).', 'info', 3000);
    } else if (k === 'F7') {
      handled();
      mq.focus();
      mq.select();
    } else if (k === 'F8') {
      handled();
      focusPay();
    } else if (k === 'F9') {
      handled();
      go('riwayat');
    } else if (k === 'F12' || (e.ctrlKey && k === 'Enter')) {
      handled();
      process();
    } else if (e.altKey && !e.ctrlKey && /^[1-6]$/.test(k)) {
      handled();
      pickPay(Number(k) - 1);
    } else if (e.ctrlKey && (k === 'l' || k === 'L')) {
      handled();
      go('lock');
    } else if (k === 'Escape' && document.activeElement !== q) {
      handled();
      closeMembers();
      focusSearch();
    }
  }
  document.addEventListener('keydown', onKeys);

  // Ketikan saat fokus tidak di kolom isian langsung masuk ke kolom cari.
  function onStrayKey(e) {
    if (document.querySelector('.overlay, .busy')) return;
    if (typing(e.target)) return;
    if (e.ctrlKey || e.altKey || e.metaKey || e.key.length !== 1 || e.key === '/') return;
    q.focus();
  }
  document.addEventListener('keydown', onStrayKey, true);

  // Scanner barcode mengetik sangat cepat lalu Enter. Kalau itu terjadi di kolom lain (bayar, jumlah,
  // potongan, member), isi kolom dikembalikan dan barcode diproses sebagai barang.
  const burst = { target: null, before: '', chars: '', last: 0, slow: false };
  function onBurst(e) {
    const t = e.target;
    if (document.querySelector('.overlay, .busy') || t === q || !t || t.tagName !== 'INPUT' || t.type === 'checkbox') {
      burst.target = null;
      return;
    }
    const now = performance.now();
    if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
      if (burst.target !== t || now - burst.last > SCAN_GAP_MS * 3) {
        burst.target = t;
        burst.before = t.value;
        burst.chars = '';
        burst.slow = false;
      } else if (now - burst.last > SCAN_GAP_MS) burst.slow = true;
      burst.chars += e.key;
      burst.last = now;
      return;
    }
    if (e.key === 'Enter' && burst.target === t && !burst.slow && burst.chars.length >= SCAN_MIN_LEN && now - burst.last < 150) {
      e.preventDefault();
      e.stopPropagation();
      const code = burst.chars;
      t.value = burst.before;
      t.dispatchEvent(new Event('input', { bubbles: true }));
      burst.target = null;
      if (!addByCode(code)) {
        beep(false);
        toast(`Barcode ${code} tidak ditemukan.`, 'error', 4000);
      }
      focusSearch();
      return;
    }
    if (e.key !== 'Shift') burst.target = null;
  }
  document.addEventListener('keydown', onBurst, true);

  const offStatus = mountStatus(root, (name) =>
    go(name === 'history-rejected' ? 'riwayat' : name === 'settings' ? 'pengaturan' : name, name === 'history-rejected' ? { tab: 'rejected' } : {})
  );
  const offCart = bus.on('cart', queueRender);
  const offCatalog = bus.on('catalog', () => {
    renderCart();
    if (sr.open) openResults(sr.query);
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
      document.removeEventListener('keydown', onBurst, true);
      document.removeEventListener('mousedown', onDocDown);
    },
    back: () => {
      if (sr.open) closeResults();
      else if (q.value) q.value = '';
      focusSearch();
    },
  };
}
