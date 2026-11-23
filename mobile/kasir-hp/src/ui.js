// Komponen tampilan umum: pesan singkat, lembar (sheet), konfirmasi, tombol kembali Android.

import { esc } from './util.js';

const stack = []; // lembar yang sedang terbuka (paling atas = terakhir)

export function toast(msg, type = 'info', ms = 2600) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast toast-' + type;
  el.hidden = false;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => (el.hidden = true), ms);
}

/**
 * Buka lembar dari bawah. Mengembalikan { el, body, close }.
 * opts: { title, html, full, onClose, closable }
 */
export function openSheet({ title = '', html = '', full = false, onClose = null, closable = true } = {}) {
  const ov = document.createElement('div');
  ov.className = 'overlay';
  ov.innerHTML = `
    <div class="sheet ${full ? 'sheet-full' : ''}" role="dialog">
      <div class="sheet-head">
        <b>${esc(title)}</b>
        ${closable ? '<button type="button" class="icon-btn sheet-x" aria-label="Tutup">✕</button>' : ''}
      </div>
      <div class="sheet-body">${html}</div>
    </div>`;
  document.body.appendChild(ov);
  let closed = false;
  const entry = {
    el: ov,
    body: ov.querySelector('.sheet-body'),
    closable,
    close() {
      if (closed) return;
      closed = true;
      const i = stack.indexOf(entry);
      if (i >= 0) stack.splice(i, 1);
      ov.remove();
      onClose && onClose();
    },
  };
  stack.push(entry);
  if (closable) {
    ov.querySelector('.sheet-x').onclick = () => entry.close();
    ov.addEventListener('click', (e) => {
      if (e.target === ov) entry.close();
    });
  }
  return entry;
}

export function confirmBox(message, { title = 'Konfirmasi', ok = 'Ya', cancel = 'Batal', danger = false } = {}) {
  return new Promise((resolve) => {
    let answered = false;
    const s = openSheet({
      title,
      html: `<p class="confirm-msg">${esc(message).replace(/\n/g, '<br>')}</p>
        <div class="btn-row">
          <button type="button" class="btn" data-a="no">${esc(cancel)}</button>
          <button type="button" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-a="yes">${esc(ok)}</button>
        </div>`,
      onClose: () => !answered && resolve(false),
    });
    s.body.addEventListener('click', (e) => {
      const a = e.target.closest('[data-a]');
      if (!a) return;
      answered = true;
      s.close();
      resolve(a.dataset.a === 'yes');
    });
  });
}

export function alertBox(message, { title = 'Info', ok = 'OK' } = {}) {
  return new Promise((resolve) => {
    const s = openSheet({
      title,
      html: `<p class="confirm-msg">${esc(message).replace(/\n/g, '<br>')}</p>
        <div class="btn-row"><button type="button" class="btn btn-primary" data-a="ok">${esc(ok)}</button></div>`,
      onClose: resolve,
    });
    s.body.querySelector('[data-a]').onclick = () => s.close();
  });
}

/** Layar tunggu. Mengembalikan { set(text), done() }. */
export function busy(text = 'Mohon tunggu…') {
  const el = document.createElement('div');
  el.className = 'busy';
  el.innerHTML = `<div class="busy-box"><div class="spinner"></div><div class="busy-text">${esc(text)}</div></div>`;
  document.body.appendChild(el);
  return {
    set: (t) => (el.querySelector('.busy-text').textContent = t),
    done: () => el.remove(),
  };
}

/** Tutup lembar paling atas. true bila ada yang ditutup (untuk tombol kembali Android). */
export function closeTopSheet() {
  const top = stack[stack.length - 1];
  if (!top) return false;
  if (top.closable) top.close();
  return true;
}

export function closeAllSheets() {
  while (stack.length) stack[stack.length - 1].close();
}

/** Isi input uang dengan titik ribuan sambil diketik. */
export function moneyInput(input, onChange) {
  input.addEventListener('input', () => {
    const digits = input.value.replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
    input.value = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    onChange && onChange(digits ? parseInt(digits, 10) : 0);
  });
}

/** Bunyi "bip" pendek (scan berhasil). */
let audioCtx = null;
export function beep(ok = true) {
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    const o = audioCtx.createOscillator();
    const g = audioCtx.createGain();
    o.frequency.value = ok ? 1900 : 320;
    g.gain.value = 0.15;
    o.connect(g);
    g.connect(audioCtx.destination);
    o.start();
    o.stop(audioCtx.currentTime + (ok ? 0.08 : 0.25));
  } catch (e) {
    /* abaikan */
  }
  try {
    navigator.vibrate && navigator.vibrate(ok ? 40 : [80, 60, 80]);
  } catch (e) {
    /* abaikan */
  }
}
