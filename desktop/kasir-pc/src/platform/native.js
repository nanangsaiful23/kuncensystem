// Pengganti native.js Kasir HP untuk PC: token perangkat disimpan terenkripsi oleh Electron
// (Windows DPAPI), printer = printer yang terpasang di Windows (USB/LAN/Bluetooth dengan driver).
// "Tersambung" di sini berarti printer pilihan ada di daftar printer Windows.

const bridge = typeof window !== 'undefined' ? window.kasirPc : null;

export const isNative = !!bridge;

export const secret = {
  async get(key) {
    if (!bridge) return localStorage.getItem('secret:' + key);
    return (await bridge.secretGet(key)) ?? null;
  },
  async set(key, value) {
    if (!bridge) return localStorage.setItem('secret:' + key, value);
    await bridge.secretSet(key, value);
  },
  async remove(key) {
    if (!bridge) return localStorage.removeItem('secret:' + key);
    await bridge.secretRemove(key);
  },
};

const listeners = new Set();
let lastState = { connected: false, address: null, name: null };

function setState(s) {
  lastState = s;
  listeners.forEach((fn) => {
    try {
      fn(s);
    } catch (e) {
      console.error(e);
    }
  });
  return s;
}

async function windowsPrinters() {
  if (!bridge) {
    return [
      { name: 'POS-58 (contoh)', displayName: 'POS-58 (contoh)', isDefault: false },
      { name: 'Microsoft Print to PDF', displayName: 'Microsoft Print to PDF', isDefault: true },
    ];
  }
  return bridge.printers();
}

export const printer = {
  state: () => lastState,
  onState(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
  /** Daftar printer Windows. Dibentuk sama dengan Kasir HP: { name, address, printer, isDefault }. */
  async listPaired() {
    const list = await windowsPrinters();
    return list.map((p) => ({
      name: p.displayName || p.name,
      address: p.name,
      printer: true,
      isDefault: !!p.isDefault,
      description: p.description || '',
    }));
  },
  async connect(address) {
    const list = await windowsPrinters();
    const p = list.find((x) => x.name === address);
    if (!p) {
      setState({ connected: false, address, name: address });
      throw new Error(`Printer "${address}" tidak ada di Windows. Cek kabel/daya printer atau pasang ulang driver-nya.`);
    }
    return setState({ connected: true, address: p.name, name: p.displayName || p.name });
  },
  async disconnect() {
    setState({ connected: false, address: null, name: null });
  },
  /** Periksa ulang printer pilihan (dipanggil saat aplikasi dibuka / jendela difokuskan). */
  async status(address = lastState.address) {
    if (!address) return lastState;
    try {
      return await printer.connect(address);
    } catch (e) {
      return lastState;
    }
  },
  /** Byte ESC/POS (cara Kasir HP) tidak dipakai di PC: struk dicetak lewat driver Windows. */
  async write() {
    throw new Error('Kasir PC mencetak lewat driver Windows (printHtml).');
  },
  /** Cetak HTML struk. widthMm: lebar kertas (58/80). silent=false memunculkan dialog cetak Windows. */
  async printHtml(html, { deviceName, widthMm = 58, silent = true } = {}) {
    if (!bridge) {
      console.log('[printer] ' + (deviceName || '-') + '\n', html);
      const w = window.open('', '_blank', `width=${Math.round(widthMm * 4.5)},height=700`);
      if (w) {
        w.document.write(html);
        w.document.close();
      }
      return;
    }
    const r = await bridge.print({ html, deviceName, widthMm, silent });
    if (!r || !r.ok) {
      const e = new Error((r && r.error) || 'Gagal mencetak.');
      e.cancelled = !!(r && r.cancelled);
      throw e;
    }
  },
};
