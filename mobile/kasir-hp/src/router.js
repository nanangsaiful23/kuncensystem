// Perpindahan layar sederhana. Setiap layar: fn(root, params) -> { cleanup?, back? }
// Setiap layar mendapat elemen root BARU, jadi pendengar klik layar lama ikut hilang.

import { closeAllSheets } from './ui.js';

const views = {};
let current = null;

export const session = { cashier: null };

export function register(name, fn) {
  views[name] = fn;
}

export function go(name, params = {}) {
  closeAllSheets();
  if (current && current.cleanup) {
    try {
      current.cleanup();
    } catch (e) {
      console.error(e);
    }
  }
  const app = document.getElementById('app');
  app.innerHTML = '';
  const root = document.createElement('div');
  root.className = 'view view-' + name;
  app.appendChild(root);
  current = { name, params, cleanup: null, back: null };
  const r = views[name](root, params) || {};
  // Layar bisa langsung pindah ke layar lain (mis. belum login); jangan timpa layar baru itu.
  if (current && current.name === name && app.contains(root)) {
    current.cleanup = r.cleanup;
    current.back = r.back;
  } else if (r.cleanup) {
    r.cleanup();
  }
  window.scrollTo(0, 0);
}

export const currentView = () => current;
