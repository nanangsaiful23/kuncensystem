// Layar: pilih kasir + PIN (bisa offline), dan tambah kasir (login online email + password, lalu buat PIN).

import * as db from '../db.js';
import { api } from '../api.js';
import { go, session } from '../router.js';
import { openSheet, busy, toast, alertBox } from '../ui.js';
import { esc, hashPin } from '../util.js';
import { restoreDraft } from '../cart.js';
import { conn } from '../sync.js';
import { connBadgeHtml } from '../status.js';
import { bus } from '../bus.js';

const MAX_FAIL = 5;
const LOCK_SECONDS = 60;

/**
 * Papan angka PIN di dalam el.
 * opts: { title, subtitle, fixedLength (auto-kirim), min, max, onSubmit(pin) -> Promise<string|null> (pesan salah) }
 */
function pinPad(el, { title, subtitle = '', fixedLength = null, min = 4, max = 6, onSubmit }) {
  let pin = '';
  el.innerHTML = `
    <div class="pin">
      <div class="pin-title">${esc(title)}</div>
      <div class="pin-sub">${esc(subtitle)}</div>
      <div class="pin-dots"></div>
      <div class="pin-err error"></div>
      <div class="pin-keys">
        ${[1, 2, 3, 4, 5, 6, 7, 8, 9].map((n) => `<button type="button" data-k="${n}">${n}</button>`).join('')}
        <button type="button" data-k="del" class="k-fn">⌫</button>
        <button type="button" data-k="0">0</button>
        <button type="button" data-k="ok" class="k-ok">OK</button>
      </div>
    </div>`;
  const dots = el.querySelector('.pin-dots');
  const err = el.querySelector('.pin-err');
  let busyNow = false;
  const draw = () => {
    const n = fixedLength || Math.max(min, pin.length);
    dots.innerHTML = Array.from({ length: n }, (_, i) => `<i class="${i < pin.length ? 'on' : ''}"></i>`).join('');
  };
  const submit = async () => {
    if (busyNow) return;
    if (pin.length < min) {
      err.textContent = `PIN minimal ${min} angka.`;
      return;
    }
    busyNow = true;
    const msg = await onSubmit(pin);
    busyNow = false;
    if (msg) {
      err.textContent = msg;
      pin = '';
      draw();
    }
  };
  el.querySelector('.pin-keys').addEventListener('click', (e) => {
    const k = e.target.closest('[data-k]');
    if (!k || busyNow) return;
    err.textContent = '';
    const v = k.dataset.k;
    if (v === 'del') pin = pin.slice(0, -1);
    else if (v === 'ok') return submit();
    else if (pin.length < (fixedLength || max)) pin += v;
    draw();
    if (fixedLength && pin.length === fixedLength) submit();
  });
  draw();
}

export function lockView(root) {
  session.cashier = null;
  const device = db.kv('device') || {};
  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      <div class="tb-left"><div class="tb-title">Kasir HP <span class="dev-code">${esc(device.code || '')}</span></div>
        <div class="tb-sub">${esc(device.name || '')}</div></div>
      <div class="tb-right"><span data-conn>${connBadgeHtml()}</span>
        <button type="button" class="icon-btn" data-a="settings" aria-label="Pengaturan">⚙</button></div>
    </header>
    <div class="content">
      <div class="card">
        <div class="card-title">Pilih kasir</div>
        <div id="list" class="cashier-list"></div>
        <button type="button" class="btn btn-block" data-a="add">+ Tambah kasir / lupa PIN (perlu internet)</button>
      </div>
    </div>
  </div>`;

  const off = bus.on('conn', () => (root.querySelector('[data-conn]').innerHTML = connBadgeHtml()));

  db.all('cashiers').then((list) => {
    if (!list.length) return go('addCashier');
    list.sort((a, b) => a.name.localeCompare(b.name));
    root.querySelector('#list').innerHTML = list
      .map((c) => `<button type="button" class="cashier" data-id="${c.id}"><span class="avatar">${esc(c.name.slice(0, 1).toUpperCase())}</span>
        <span><b>${esc(c.name)}</b><br><small class="muted">${esc(c.email)}</small></span></button>`)
      .join('');
  });

  root.addEventListener('click', async (e) => {
    const a = e.target.closest('[data-a]');
    if (a && a.dataset.a === 'add') return go('addCashier', { canCancel: true });
    if (a && a.dataset.a === 'settings') return go('pengaturan');
    const c = e.target.closest('.cashier');
    if (c) openPin(Number(c.dataset.id));
  });

  async function openPin(id) {
    const c = await db.get('cashiers', id);
    if (!c) return;
    const s = openSheet({ title: c.name, full: true });
    pinPad(s.body, {
      title: 'Masukkan PIN',
      subtitle: c.name,
      fixedLength: c.pin.len || null,
      onSubmit: async (pin) => {
        const failKey = 'pinFail-' + id;
        const f = db.kv(failKey) || { n: 0, until: 0 };
        if (f.until > Date.now()) return `Terlalu banyak salah. Tunggu ${Math.ceil((f.until - Date.now()) / 1000)} detik.`;
        const h = await hashPin(pin, c.pin.salt, c.pin.iterations);
        if (h.hash !== c.pin.hash) {
          f.n += 1;
          if (f.n >= MAX_FAIL) {
            f.n = 0;
            f.until = Date.now() + LOCK_SECONDS * 1000;
          }
          await db.setKv(failKey, f);
          return f.until > Date.now() ? `PIN salah ${MAX_FAIL}x. Tunggu ${LOCK_SECONDS} detik.` : 'PIN salah.';
        }
        await db.setKv(failKey, null);
        session.cashier = { id: c.id, name: c.name, email: c.email };
        await db.setKv('lastCashierId', c.id);
        s.close();
        restoreDraft();
        go('kasir');
        return null;
      },
    });
  }

  return { cleanup: off };
}

export function addCashierView(root, { canCancel = false } = {}) {
  root.innerHTML = `
  <div class="screen">
    <header class="topbar">
      ${canCancel ? '<button type="button" class="icon-btn" data-a="back">←</button>' : ''}
      <div class="tb-left"><div class="tb-title">Tambah kasir</div><div class="tb-sub">Login akun kasir (perlu internet)</div></div>
      <div class="tb-right"><span data-conn>${connBadgeHtml()}</span></div>
    </header>
    <div class="content">
      <form class="card" id="f" autocomplete="off">
        <p class="muted">Pakai akun kasir yang sama dengan kasir komputer. Setelah berhasil, buat PIN untuk membuka aplikasi
          (PIN hanya disimpan di HP ini dan tetap bisa dipakai saat offline).</p>
        <label for="email">Email / username kasir</label>
        <input id="email" autocapitalize="off" spellcheck="false" required>
        <label for="pw">Password</label>
        <input id="pw" type="password" required>
        <div id="err" class="error" hidden></div>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        ${canCancel ? '' : '<button type="button" class="btn btn-block" data-a="settings">Pengaturan</button>'}
      </form>
    </div>
  </div>`;
  const $ = (s) => root.querySelector(s);
  const off = bus.on('conn', () => ($('[data-conn]').innerHTML = connBadgeHtml()));

  root.addEventListener('click', (e) => {
    const a = e.target.closest('[data-a]');
    if (a && a.dataset.a === 'back') go('lock');
    if (a && a.dataset.a === 'settings') go('pengaturan');
  });

  $('#f').addEventListener('submit', async (e) => {
    e.preventDefault();
    $('#err').hidden = true;
    const b = busy('Memeriksa akun…');
    let cashier;
    try {
      const r = await api.login($('#email').value.trim(), $('#pw').value);
      cashier = r.cashier;
    } catch (err) {
      b.done();
      $('#err').hidden = false;
      $('#err').textContent =
        err.kind === 'network' || err.kind === 'timeout'
          ? err.message + ' Login kasir baru harus online; kasir yang sudah ada di HP cukup pakai PIN.'
          : err.message;
      return;
    }
    b.done();
    $('#pw').value = '';
    createPin(cashier);
  });

  function createPin(cashier) {
    let first = null;
    const s = openSheet({ title: 'Buat PIN untuk ' + cashier.name, full: true });
    const step1 = () =>
      pinPad(s.body, {
        title: 'Buat PIN baru (4–6 angka)',
        subtitle: 'Tekan OK setelah selesai',
        onSubmit: async (pin) => {
          first = pin;
          step2();
          return null;
        },
      });
    const step2 = () =>
      pinPad(s.body, {
        title: 'Ulangi PIN',
        subtitle: 'Masukkan PIN yang sama sekali lagi',
        fixedLength: first.length,
        onSubmit: async (pin) => {
          if (pin !== first) {
            toast('PIN tidak sama, ulangi dari awal.', 'error');
            step1();
            return null;
          }
          const h = await hashPin(pin);
          await db.put('cashiers', {
            id: cashier.id,
            name: cashier.name,
            email: cashier.email,
            pin: { ...h, len: pin.length },
            addedAt: Date.now(),
          });
          await db.setKv('pinFail-' + cashier.id, null);
          session.cashier = { id: cashier.id, name: cashier.name, email: cashier.email };
          s.close();
          toast(cashier.name + ' siap dipakai ✓', 'ok');
          restoreDraft();
          go('kasir');
          return null;
        },
      });
    step1();
  }

  if (conn.state === 'offline' || conn.state === 'denied') {
    setTimeout(() => alertBox(conn.error || 'Tidak ada internet.', { title: 'Perlu internet' }), 300);
  }

  return { cleanup: off, back: canCancel ? () => go('lock') : undefined };
}
