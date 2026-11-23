// Jembatan ke kode Android (android/app/src/main/java/.../KasirNativePlugin.java):
// penyimpanan rahasia (token perangkat, terenkripsi Android Keystore) dan printer Bluetooth.
// Di browser komputer (mode uji) memakai pengganti sederhana.

import { Capacitor, registerPlugin } from '@capacitor/core';
import { bytesToBase64 } from './util.js';

export const isNative = Capacitor.isNativePlatform();
const Native = registerPlugin('KasirNative');

export const secret = {
  async get(key) {
    if (!isNative) return localStorage.getItem('secret:' + key);
    const r = await Native.getSecret({ key });
    return r.value ?? null;
  },
  async set(key, value) {
    if (!isNative) return localStorage.setItem('secret:' + key, value);
    await Native.setSecret({ key, value });
  },
  async remove(key) {
    if (!isNative) return localStorage.removeItem('secret:' + key);
    await Native.removeSecret({ key });
  },
};

const listeners = new Set();
let lastState = { connected: false, address: null, name: null };

if (isNative) {
  Native.addListener('printerState', (s) => {
    lastState = s;
    listeners.forEach((fn) => fn(s));
  });
}

export const printer = {
  state: () => lastState,
  onState(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
  /** Daftar perangkat Bluetooth yang sudah di-pair di Pengaturan Android. */
  async listPaired() {
    if (!isNative) return [{ name: 'Printer Contoh (browser)', address: '00:11:22:33:44:55', printer: true }];
    const r = await Native.btListPaired();
    return r.devices || [];
  },
  async connect(address) {
    if (!isNative) {
      lastState = { connected: true, address, name: 'Printer Contoh' };
      listeners.forEach((fn) => fn(lastState));
      return lastState;
    }
    const r = await Native.btConnect({ address });
    lastState = r;
    return r;
  },
  async disconnect() {
    if (!isNative) {
      lastState = { connected: false, address: null, name: null };
      listeners.forEach((fn) => fn(lastState));
      return;
    }
    await Native.btDisconnect();
  },
  async status() {
    if (!isNative) return lastState;
    lastState = await Native.btStatus();
    return lastState;
  },
  /** Kirim byte ESC/POS. Menyambung otomatis ke address bila belum tersambung. */
  async write(bytes, address) {
    if (!isNative) {
      console.log('[printer] ' + bytes.length + ' byte\n' + new TextDecoder().decode(bytes.filter((b) => b === 10 || b >= 32)));
      return;
    }
    await Native.btWrite({ data: bytesToBase64(bytes), address });
  },
};
