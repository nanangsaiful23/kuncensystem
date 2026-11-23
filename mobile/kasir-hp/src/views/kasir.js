// Layar utama kasir: cari/scan barang, keranjang, ke pembayaran.

import * as db from '../db.js';
import { go, session } from '../router.js';
import { openSheet, toast, confirmBox, moneyInput, beep, primeAudio } from '../ui.js';
import { esc, rp, qtyText, parseQty, debounce, round2 } from '../util.js';
import { searchGoods, findByCode, goodsCount } from '../catalog.js';
import { cart, addGood, updateLine, removeLine, resetCart, findLine, lineSum, lineGross, grandTotal, itemCount } from '../cart.js';
import { tierPlan, tierText } from '../tier.js';
import { mountStatus } from '../status.js';
import { bus } from '../bus.js';
import { scan, isScanning, pauseScan, setScanText } from '../scanner.js';
import { syncNow, adminChanged, adminNote, canReachServer, lookupOnServer } from '../sync.js';

const BARCODE_RE = /^\d{6,}$/; // angka >= 6 digit = barcode (bukan nama barang)
const PAD_KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', ',', '0', '⌫'];
import { App } from '@capacitor/app';

/** "Harga grosir: 1 PAK5 + 1 PCS (hemat 2.500)" bila jumlahnya cukup untuk satuan besar. */
const tierNote = (l) => {
  const plan = tierPlan(l);
  return plan ? `<div class="tier-note">Harga grosir: <b>${esc(tierText(plan))}</b> (hemat ${rp(plan.saved)})</div>` : '';
};

const unitChips = (g) =>
  g.units
    .map((u) => `<button type="button" class="chip" data-unit="${u.good_unit_id}">${esc(u.unit)} <b>${rp(u.selling_price)}</b></button>`)
    .join('');

export function kasirView(root) {
  if (!session.cashier) {
    go('lock');
    return {};
  }
  const device = db.kv('device') || {};
  root.innerHTML = `
  <div class="screen kasir">
    <header class="topbar">
      <div class="tb-left">
        <div class="tb-title">Kasir HP <span class="dev-code">${esc(device.code || '')}</span></div>
        <div class="tb-sub">👤 ${esc(session.cashier.name)} · <span data-status="printer"></span></div>
      </div>
      <div class="tb-right">
        <button type="button" class="badge-btn" data-a="conn" data-status="conn"></button>
        <button type="button" class="icon-btn" data-a="menu" aria-label="Menu">☰</button>
      </div>
    </header>
    <div class="strip" data-status="strip"></div>
    <div data-status="alerts"></div>
    <div class="searchbar">
      <input id="q" type="search" placeholder="Cari nama / kode / barcode" autocomplete="off" autocapitalize="off"
        spellcheck="false" enterkeyhint="search">
      <button type="button" class="icon-btn big scan-btn" data-a="scan" aria-label="Scan barcode">📷</button>
    </div>
    <div id="results" class="results" hidden></div>
    <div id="cart" class="cart"></div>
    <footer class="paybar">
      <div class="paybar-total">
        <div class="muted small"><span id="count">0</span> barang</div>
        <div class="total" id="total">0</div>
      </div>
      <button type="button" class="btn" data-a="clear">Batal</button>
      <button type="button" class="btn btn-primary big" data-a="pay">Bayar</button>
    </footer>
  </div>`;

  const $ = (s) => root.querySelector(s);
  const q = $('#q');
  const results = $('#results');
  let lastResults = [];

  // ---- Keranjang ----
  function renderCart() {
    const el = $('#cart');
    if (!cart.lines.length) {
      el.innerHTML = `<div class="empty">${goodsCount() ? 'Belum ada barang.<br>Cari nama barang atau tekan 📷 untuk scan barcode.' : 'Data barang kosong. Buka Pengaturan › Unduh ulang data.'}</div>`;
    } else {
      el.innerHTML = cart.lines
        .slice()
        .reverse()
        .map(
          (l) => `
        <div class="line" data-key="${l.key}">
          <div class="line-main" data-a="edit">
            <div class="line-name">${esc(l.name)}</div>
            <div class="line-sub">${esc(l.unit)} × ${rp(l.price)}${l.discount ? ` <span class="disc">diskon -${rp(l.discount)}</span>` : ''}</div>
            ${tierNote(l)}
          </div>
          <div class="qty">
            <button type="button" data-a="dec">−</button>
            <span class="qv" data-a="edit">${qtyText(l.quantity)}</span>
            <button type="button" data-a="inc">+</button>
          </div>
          <div class="line-sum">${rp(lineSum(l))}</div>
        </div>`
        )
        .join('');
    }
    $('#count').textContent = itemCount();
    $('#total').textContent = rp(grandTotal());
  }

  // ---- Pencarian ----
  function renderResults(list) {
    lastResults = list;
    if (!q.value.trim()) {
      results.hidden = true;
      return;
    }
    results.hidden = false;
    if (!list.length) {
      results.innerHTML = `<div class="empty small">Tidak ada barang "${esc(q.value)}".</div>`;
      return;
    }
    results.innerHTML = list
      .map(
        (g) => `
      <div class="result" data-id="${g.id}">
        <div class="result-main" data-a="add">
          <div class="result-name">${esc(g.name)}${g.is_discontinued ? ' <span class="tag">discontinue</span>' : ''}</div>
          <div class="muted small">${esc(g.code || '')} · stok ±${qtyText(g.stock_estimate || 0)}</div>
        </div>
        <div class="chips">${g.units.length ? unitChips(g) : '<span class="muted small">tidak ada harga</span>'}</div>
      </div>`
      )
      .join('');
  }
  const doSearch = debounce(() => renderResults(searchGoods(q.value)), 120);
  q.addEventListener('input', doSearch);
  q.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const text = q.value.trim();
    if (!text) return;
    // Scanner barcode USB/Bluetooth mengetik kode lalu Enter.
    const g = findByCode(text) || (lastResults.length === 1 ? lastResults[0] : null);
    if (g) {
      beep(add(g, null, true));
    } else if (BARCODE_RE.test(text)) {
      // barcode tidak dikenal: jangan tampilkan barang yang "mirip"; cek ke server bila online
      beep(false);
      toast(`Barcode ${text} tidak ada di data barang${canReachServer() ? ' — mengecek server…' : ''}`, 'error', 4000);
      results.hidden = true;
      q.select(); // scan berikutnya langsung menimpa
      if (canReachServer()) {
        lookupOnServer(text).then(({ good, checked }) => {
          if (good && good.units.length) {
            addGood(good, good.units[0], 1);
            if (q.value.trim() === text) q.value = '';
            beep(true);
            toast(`✓ ${good.name} masuk (data barang baru diperbarui)`, 'ok');
          } else if (checked) toast(`Barcode ${text} tidak terdaftar, juga di server.`, 'error', 4000);
        });
      }
    } else {
      renderResults(searchGoods(text));
    }
  });

  function add(g, unitId = null, keepFocus = false) {
    if (!g.units.length) {
      toast('Barang ini belum punya harga jual.', 'error');
      return false;
    }
    const unit = unitId ? g.units.find((u) => u.good_unit_id === unitId) : g.units[0];
    addGood(g, unit, 1);
    q.value = '';
    results.hidden = true;
    if (keepFocus) q.focus();
    else q.blur();
    return true;
  }

  results.addEventListener('click', (e) => {
    const r = e.target.closest('.result');
    if (!r) return;
    const g = searchGoods(q.value).find((x) => x.id === Number(r.dataset.id)) || lastResults.find((x) => x.id === Number(r.dataset.id));
    if (!g) return;
    const chip = e.target.closest('[data-unit]');
    add(g, chip ? Number(chip.dataset.unit) : null);
  });

  // ---- Aksi ----
  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    if (!a) return;
    const act = a.dataset.a;
    const lineEl = e.target.closest('.line');
    const key = lineEl ? Number(lineEl.dataset.key) : null;

    if (act === 'inc') updateLine(key, { quantity: findLine(key).quantity + 1 });
    if (act === 'dec') {
      const l = findLine(key);
      if (l.quantity > 1) updateLine(key, { quantity: round2(l.quantity - 1) });
      else if (await confirmBox(`Hapus "${l.name}" dari keranjang?`, { ok: 'Hapus', danger: true })) removeLine(key);
    }
    if (act === 'edit') editLine(key);
    if (act === 'scan') openScanner();
    if (act === 'pay') {
      if (!cart.lines.length) return toast('Keranjang masih kosong.', 'warn');
      go('bayar');
    }
    if (act === 'clear') {
      if (!cart.lines.length) return;
      if (await confirmBox('Kosongkan keranjang?', { ok: 'Kosongkan', danger: true })) resetCart();
    }
    if (act === 'menu') openMenu();
    if (act === 'conn') go('pengaturan');
  });

  function editLine(key) {
    const l = findLine(key);
    if (!l) return;
    const s = openSheet({
      title: l.name,
      html: `
        <label>Satuan</label>
        <div class="chips wrap" id="units">${l.units
          .map(
            (u) => `<button type="button" class="chip ${u.good_unit_id === l.good_unit_id ? 'on' : ''}" data-unit="${u.good_unit_id}">
              ${esc(u.unit)} <b>${rp(u.selling_price)}</b>${u.quantity > 1 ? ` <small>(${qtyText(u.quantity)})</small>` : ''}</button>`
          )
          .join('')}</div>
        <label>Jumlah</label>
        <div class="qty-edit">
          <button type="button" class="btn" data-q="-1">−</button>
          <input id="eq" inputmode="decimal" value="${qtyText(l.quantity)}">
          <button type="button" class="btn" data-q="1">+</button>
        </div>
        <label>Diskon baris ini (Rp)</label>
        <input id="ed" inputmode="numeric" value="${l.discount ? rp(l.discount) : ''}" placeholder="0">
        <div class="row-between big-row"><span>Jumlah harga</span><b id="esum"></b></div>
        <div id="eerr" class="error" hidden></div>
        <div class="btn-row">
          <button type="button" class="btn btn-danger" data-e="del">Hapus</button>
          <button type="button" class="btn btn-primary" data-e="ok">Simpan</button>
        </div>`,
    });
    const b = s.body;
    let unitId = l.good_unit_id;
    let disc = l.discount || 0;
    const price = () => Number((l.units.find((u) => u.good_unit_id === unitId) || {}).selling_price) || 0;
    const calc = () => {
      const qv = parseQty(b.querySelector('#eq').value);
      // termasuk harga grosir otomatis (satuan besar) bila jumlahnya cukup
      const gross = isNaN(qv) ? 0 : lineGross({ ...l, good_unit_id: unitId, price: price(), quantity: qv });
      b.querySelector('#esum').textContent = rp(gross - disc);
      return { qv, gross };
    };
    moneyInput(b.querySelector('#ed'), (v) => {
      disc = v;
      calc();
    });
    b.querySelector('#eq').addEventListener('input', calc);
    calc();
    b.addEventListener('click', async (e) => {
      const u = e.target.closest('[data-unit]');
      if (u) {
        unitId = Number(u.dataset.unit);
        b.querySelectorAll('[data-unit]').forEach((x) => x.classList.toggle('on', x === u));
        calc();
      }
      const dq = e.target.closest('[data-q]');
      if (dq) {
        const cur = parseQty(b.querySelector('#eq').value) || 0;
        b.querySelector('#eq').value = qtyText(Math.max(0, round2(cur + Number(dq.dataset.q))));
        calc();
      }
      const act = e.target.closest('[data-e]');
      if (act && act.dataset.e === 'del') {
        removeLine(key);
        s.close();
      }
      if (act && act.dataset.e === 'ok') {
        const { qv, gross } = calc();
        const err = b.querySelector('#eerr');
        err.hidden = false;
        if (isNaN(qv) || qv <= 0) return (err.textContent = 'Jumlah harus lebih dari 0 (boleh desimal, mis. 0,5).');
        if (disc > gross) return (err.textContent = 'Diskon lebih besar dari jumlah harga.');
        updateLine(key, { good_unit_id: unitId, quantity: qv, discount: disc });
        s.close();
      }
    });
  }

  // ---- Scan kamera: kamera tetap terbuka; barang terakhir tampil di bawah dengan − jumlah + ----
  async function openScanner() {
    primeAudio(); // masih di dalam sentuhan tombol 📷: izinkan bunyi bip selama scan
    const overlay = document.getElementById('scan-overlay');
    const panel = document.getElementById('scan-item');
    let key = null; // baris keranjang dari scan terakhir
    let lastGoodId = null; // barang scan terakhir: tidak dimasukkan lagi bila langsung di-scan ulang
    let typed = null; // isi papan angka; null = papan tertutup
    let n = 0;
    let seq = 0; // nomor scan, supaya hasil cek server tidak menimpa pesan scan yang lebih baru

    const closePad = () => {
      typed = null;
      overlay.classList.remove('typing');
      pauseScan(false);
    };

    function paint() {
      const l = key ? findLine(key) : null;
      panel.hidden = !l;
      if (!l) {
        panel.innerHTML = '';
        return;
      }
      const head = `<div class="si-name">${esc(l.name)}</div>`;
      if (typed !== null) {
        panel.innerHTML = `${head}
          <div class="si-show">Jumlah: ${typed ? `<b>${esc(typed)}</b>` : `<b class="ph">${qtyText(l.quantity)}</b>`} ${esc(l.unit)}</div>
          <div class="si-keys">${PAD_KEYS.map((k) => `<button type="button" data-k="${k}">${k}</button>`).join('')}</div>
          <div class="si-pad-btns">
            <button type="button" class="btn" data-s="cancel">Batal</button>
            <button type="button" class="btn btn-primary" data-s="ok">OK</button>
          </div>`;
        return;
      }
      const plan = tierPlan(l);
      panel.innerHTML = `${head}
        <div class="si-row">
          <button type="button" class="si-btn ${l.quantity > 1 ? '' : 'del'}" data-s="dec">${l.quantity > 1 ? '−' : 'Hapus'}</button>
          <button type="button" class="si-qty" data-s="pad"><b>${qtyText(l.quantity)}</b> ${esc(l.unit)}<small>ketuk untuk ketik jumlah</small></button>
          <button type="button" class="si-btn" data-s="inc">+</button>
        </div>
        <div class="si-sum">${plan ? `harga grosir ${esc(tierText(plan))} · ` : ''}<b>${rp(lineSum(l))}</b></div>`;
    }

    panel.onclick = (e) => {
      const b = e.target.closest('[data-s],[data-k]');
      const l = key ? findLine(key) : null;
      if (!b || !l) return;
      const k = b.dataset.k;
      if (k) {
        if (k === '⌫') typed = typed.slice(0, -1);
        else if (k === ',') typed = typed.includes(',') ? typed : (typed || '0') + ',';
        else if (typed.length < 6) typed = typed === '0' ? k : typed + k;
        paint();
        return;
      }
      const s = b.dataset.s;
      if (s === 'inc') updateLine(key, { quantity: l.quantity + 1 });
      if (s === 'dec') {
        if (l.quantity > 1) updateLine(key, { quantity: round2(l.quantity - 1) });
        else {
          removeLine(key);
          key = null;
          setScanText(`Dihapus: ${l.name}`, null);
        }
      }
      if (s === 'pad') {
        typed = '';
        overlay.classList.add('typing');
        pauseScan(true);
      }
      if (s === 'cancel') closePad();
      if (s === 'ok') {
        if (typed) {
          const qv = parseQty(typed);
          if (!(qv > 0)) {
            beep(false);
            return;
          }
          updateLine(key, { quantity: qv });
        }
        closePad();
      }
      paint();
    };

    function addScanned(g) {
      if (!g.units.length) return { ok: false, text: `✗ ${g.name}: belum ada harga jual` };
      // Barang yang sama berturut-turut tidak dimasukkan lagi: barcode yang lama di depan kamera
      // (atau terbaca ulang) tidak boleh menambah jumlah. Tambah jumlah lewat + atau ketik jumlah.
      if (g.id === lastGoodId) {
        const l = key ? findLine(key) : null;
        if (!l) return { ok: null, silent: true, text: `${g.name} baru dihapus — tidak dimasukkan lagi` };
        const plus = panel.querySelector('[data-s="inc"]');
        if (plus) {
          plus.classList.remove('flash');
          void plus.offsetWidth; // ulang animasi
          plus.classList.add('flash');
        }
        return { ok: null, silent: true, text: `Sudah di-scan (${qtyText(l.quantity)} ${l.unit}). Tambah jumlah: tekan +` };
      }
      if (typed !== null) closePad();
      key = addGood(g, g.units[0], 1).key;
      lastGoodId = g.id;
      n++;
      paint();
      return { ok: true, text: `✓ ${g.name}  (${n} scan)` };
    }

    function unknownCode(code) {
      const mine = seq;
      const online = canReachServer();
      if (online) {
        lookupOnServer(code).then(({ good, checked }) => {
          if (good && !isScanning()) return toast(`Barcode ${code} sekarang ada di data barang. Scan ulang.`, 'warn', 4000);
          if (good) {
            const r = addScanned(good);
            if (!r.silent) beep(r.ok !== false);
            setScanText(r.text, r.ok);
          } else if (seq === mine && isScanning()) {
            setScanText(checked ? `✗ Barcode ${code} tidak terdaftar (sudah dicek ke server)` : `✗ Barcode ${code} tidak ada di data barang`, false);
          }
        });
      }
      return { ok: false, text: `✗ Barcode ${code} tidak ada di data barang${online ? ' — mengecek server…' : ''}` };
    }

    try {
      await scan({
        title: 'Arahkan barcode ke dalam kotak hijau',
        continuous: true,
        aim: true,
        isKnown: (code) => !!findByCode(code),
        onCode: (code) => {
          seq++;
          const g = findByCode(code);
          return g ? addScanned(g) : unknownCode(code);
        },
      });
    } catch (e) {
      toast(e.message, 'error', 4000);
    } finally {
      closePad();
      key = null;
      paint();
      panel.onclick = null;
    }
  }

  function openMenu() {
    const s = openSheet({
      title: 'Menu',
      html: `
        <div class="menu">
          <button type="button" data-m="riwayat">🧾 Riwayat transaksi</button>
          <button type="button" data-m="sync">🔄 Sinkronkan sekarang</button>
          <button type="button" data-m="pengaturan">⚙ Pengaturan (sambungan, printer)</button>
          <button type="button" data-m="lock">🔒 Ganti kasir / kunci</button>
        </div>`,
    });
    s.body.addEventListener('click', async (e) => {
      const m = e.target.closest('[data-m]');
      if (!m) return;
      s.close();
      if (m.dataset.m === 'riwayat') go('riwayat');
      if (m.dataset.m === 'pengaturan') go('pengaturan');
      if (m.dataset.m === 'lock') go('lock');
      if (m.dataset.m === 'sync') {
        const r = await syncNow({ catalog: 'force' });
        toast(r.error ? r.error.message : 'Sinkron selesai ✓' + adminNote(r), r.error ? 'error' : adminChanged(r) ? 'warn' : 'ok', adminChanged(r) ? 5000 : 3500);
      }
    });
  }

  const offStatus = mountStatus(root, (name) => go(name === 'history-rejected' ? 'riwayat' : name === 'settings' ? 'pengaturan' : name, name === 'history-rejected' ? { tab: 'rejected' } : {}));
  const offCart = bus.on('cart', renderCart);
  const offCatalog = bus.on('catalog', () => {
    renderCart();
    if (q.value) doSearch();
  });
  renderCart();

  return {
    cleanup: () => {
      offStatus();
      offCart();
      offCatalog();
    },
    back: () => {
      if (!results.hidden) {
        q.value = '';
        results.hidden = true;
        return;
      }
      App.minimizeApp();
    },
  };
}
