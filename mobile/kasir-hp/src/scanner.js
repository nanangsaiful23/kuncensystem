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

// Kamera melaporkan barcode yang sama berkali-kali per detik selama masih terlihat.
const GONE_MS = 700; // barcode dianggap sudah dijauhkan bila tidak terlihat selama ini
const REPEAT_MS = 1000; // jarak minimal dua scan barcode yang sama (dua barang sama berturut-turut)
// Kamera kadang salah membaca sebagian garis barcode (mis. saat HP dimiringkan) menjadi angka lain.
// Kode yang tidak dikenal baru dilaporkan bila terbaca terus selama UNKNOWN_HOLD_MS dan tidak ada
// barang dikenal yang terlihat dalam KNOWN_NEAR_MS terakhir (salah baca muncul di sekitar barcode asli).
const UNKNOWN_HOLD_MS = 600;
const KNOWN_NEAR_MS = 1500;

let active = null;
let paused = false;

export const isScanning = () => !!active;

export function stopScan() {
  if (active) active.stop();
}

/** Abaikan barcode sementara (mis. kasir sedang mengetik jumlah di layar scan). */
export function pauseScan(on) {
  paused = !!on;
}

/** Tulis pesan di bagian atas layar scan. ok: true hijau, false merah, null putih. */
export function setScanText(text, ok = true) {
  const el = document.getElementById('scan-last');
  if (!el) return;
  el.textContent = text || '';
  el.className = ok === true ? 'scan-ok' : ok === false ? 'scan-bad' : '';
}

/**
 * Buka kamera.
 *  onCode(code) -> untuk mode terus-menerus boleh mengembalikan { ok, text } yang ditampilkan di layar scan.
 *  isKnown(code) -> (opsional) true bila kode ada di data; dipakai untuk menyaring salah baca dan
 *    mendahulukan barcode yang dikenal bila beberapa barcode terlihat sekaligus.
 *  continuous: true = kamera tetap terbuka untuk scan barang berikutnya (kasir), false = sekali (QR pasang).
 * Promise selesai saat kamera ditutup.
 */
export async function scan({ title = 'Arahkan kamera ke barcode', continuous = false, onCode, isKnown = null }) {
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
  document.getElementById('scan-title').textContent = title;
  setScanText('', null);
  paused = false;

  return new Promise(async (resolve, reject) => {
    const recent = new Map(); // kode -> { at: kapan diproses, seen: terakhir terlihat }
    let lastKnownAt = 0;
    let pending = null; // kode tak dikenal yang sedang ditunggu: { code, at, seen }
    let handling = false;
    let listener = null;

    const stop = async (error) => {
      if (!active) return;
      active = null;
      paused = false;
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

    /** Kode dari satu gambar kamera yang perlu diproses sekarang, atau null. */
    function choose(codes, now) {
      const fresh = [];
      for (const c of codes) {
        const r = recent.get(c);
        if (r && (now - r.seen < GONE_MS || now - r.at < REPEAT_MS)) r.seen = now; // masih barcode yang tadi
        else fresh.push(c);
      }
      if (paused) {
        // jangan langsung masuk begitu papan angka ditutup; jauhkan dulu lalu scan lagi
        for (const c of fresh) recent.set(c, { at: now, seen: now });
        return null;
      }
      if (!isKnown) return fresh[0] || null;

      const known = fresh.find(isKnown);
      if (known) return known;
      const unknown = fresh[0];
      if (!unknown) return null;
      if (!pending || pending.code !== unknown || now - pending.seen > GONE_MS) {
        pending = { code: unknown, at: now, seen: now };
        return null;
      }
      pending.seen = now;
      if (now - pending.at < UNKNOWN_HOLD_MS || now - lastKnownAt < KNOWN_NEAR_MS) return null;
      return unknown;
    }

    listener = await BarcodeScanner.addListener('barcodesScanned', async (ev) => {
      if (!active) return;
      const codes = [...new Set(((ev && ev.barcodes) || []).map((b) => b && b.rawValue).filter(Boolean))];
      if (!codes.length) return;
      const now = Date.now();
      if (isKnown && codes.some(isKnown)) lastKnownAt = now;
      if (handling) return;
      const code = choose(codes, now);
      if (!code) return;
      recent.set(code, { at: now, seen: now });
      if (pending && pending.code === code) pending = null;
      handling = true;
      try {
        const r = await onCode(code);
        const ok = !r || r.ok !== false;
        beep(ok);
        if (r && r.text) setScanText(r.text, ok);
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
