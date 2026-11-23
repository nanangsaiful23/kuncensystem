// Scan barcode/QR dengan kamera HP (Google ML Kit, model ikut di APK sehingga jalan offline).
// Kamera dirender DI BELAKANG WebView, jadi tampilan aplikasi disembunyikan selama scan.

import { BarcodeScanner, BarcodeFormat } from '@capacitor-mlkit/barcode-scanning';
import { isNative } from './native.js';
import { beep } from './ui.js';

const FORMATS = [
  BarcodeFormat.Ean13,
  BarcodeFormat.Ean8,
  BarcodeFormat.UpcA,
  BarcodeFormat.UpcE,
  BarcodeFormat.Code128,
  BarcodeFormat.Code39,
  BarcodeFormat.Code93,
  BarcodeFormat.Itf,
  BarcodeFormat.Codabar,
  BarcodeFormat.QrCode,
];

let active = null;

export const isScanning = () => !!active;

export function stopScan() {
  if (active) active.stop();
}

/**
 * Buka kamera.
 *  onCode(code) -> untuk mode terus-menerus boleh mengembalikan { ok, text } yang ditampilkan di layar scan.
 *  continuous: true = kamera tetap terbuka untuk scan barang berikutnya (kasir), false = sekali (QR pasang).
 * Promise selesai saat kamera ditutup.
 */
export async function scan({ title = 'Arahkan kamera ke barcode', continuous = false, onCode }) {
  if (active) return;
  if (!isNative) {
    const code = window.prompt('Mode browser (tanpa kamera): ketik isi barcode/QR');
    if (code) await onCode(code);
    return;
  }

  let perm = await BarcodeScanner.checkPermissions();
  if (perm.camera !== 'granted') perm = await BarcodeScanner.requestPermissions();
  if (perm.camera !== 'granted') {
    throw new Error('Izin kamera ditolak. Buka Pengaturan Android > Aplikasi > Kasir HP > Izin > Kamera.');
  }

  const overlay = document.getElementById('scan-overlay');
  const lastEl = document.getElementById('scan-last');
  document.getElementById('scan-title').textContent = title;
  lastEl.textContent = '';
  lastEl.className = '';

  return new Promise(async (resolve, reject) => {
    let lastCode = null;
    let lastAt = 0;
    let handling = false;
    let listener = null;

    const stop = async (error) => {
      if (!active) return;
      active = null;
      try {
        listener && (await listener.remove());
        await BarcodeScanner.stopScan();
      } catch (e) {
        /* abaikan */
      }
      document.body.classList.remove('scanning');
      overlay.hidden = true;
      if (error) reject(error);
      else resolve();
    };
    active = { stop };

    document.getElementById('scan-done').onclick = () => stop();
    document.getElementById('scan-torch').onclick = () => BarcodeScanner.toggleTorch().catch(() => {});

    listener = await BarcodeScanner.addListener('barcodesScanned', async (ev) => {
      const code = ev && ev.barcodes && ev.barcodes[0] && ev.barcodes[0].rawValue;
      if (!code || handling || !active) return;
      const now = Date.now();
      if (code === lastCode && now - lastAt < 1800) return; // barcode yang sama masih di depan kamera
      lastCode = code;
      lastAt = now;
      handling = true;
      try {
        const r = await onCode(code);
        const ok = !r || r.ok !== false;
        beep(ok);
        if (r && r.text) {
          lastEl.textContent = r.text;
          lastEl.className = ok ? 'scan-ok' : 'scan-bad';
        }
        if (!continuous && ok) await stop();
      } finally {
        handling = false;
      }
    });

    document.body.classList.add('scanning');
    overlay.hidden = false;
    try {
      await BarcodeScanner.startScan({ formats: FORMATS });
    } catch (e) {
      await stop(new Error('Kamera tidak bisa dibuka: ' + (e.message || e)));
    }
  });
}
