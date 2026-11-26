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
// Kotak bidik (aim): hanya barcode yang titik tengahnya di dalam kotak putih yang dibaca, supaya
// barcode barang sebelah / QR / kode produksi di kemasan tidak ikut terbaca. Posisi dari kamera
// hanya perkiraan (gambar kamera & layar beda perbandingan, bilah status), jadi diberi kelonggaran.
const AIM_SLACK_X = 1.25; // kali setengah lebar kotak
const AIM_SLACK_Y = 1.5; // kali setengah tinggi kotak
const AIM_PAD_PX = 24;

/** Barcode dalam satu gambar kamera: [{ code, x, y }] (px layar WebView; x/y null bila tanpa posisi). */
function readBarcodes(ev) {
  const dpr = window.devicePixelRatio || 1;
  const out = new Map();
  for (const b of (ev && ev.barcodes) || []) {
    const code = b && b.rawValue;
    if (!code || out.has(code)) continue;
    const pts = Array.isArray(b.cornerPoints) && b.cornerPoints.length ? b.cornerPoints : null;
    out.set(code, {
      code,
      x: pts ? pts.reduce((s, p) => s + Number(p[0]), 0) / pts.length / dpr : null,
      y: pts ? pts.reduce((s, p) => s + Number(p[1]), 0) / pts.length / dpr : null,
    });
  }
  return [...out.values()];
}

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

/** Tulis pesan di bagian atas layar scan. ok: true hijau, false merah, null kuning (info). */
export function setScanText(text, ok = true) {
  const el = document.getElementById('scan-last');
  if (!el) return;
  el.textContent = text || '';
  el.className = ok === true ? 'scan-ok' : ok === false ? 'scan-bad' : 'scan-info';
}

/**
 * Buka kamera.
 *  onCode(code) -> untuk mode terus-menerus boleh mengembalikan { ok, text, silent }:
 *    ok true = berhasil (bip), false = gagal (tot-tot), null = info (kuning); silent = tanpa bunyi.
 *  isKnown(code) -> (opsional) true bila kode ada di data; dipakai untuk menyaring salah baca dan
 *    mendahulukan barcode yang dikenal bila beberapa barcode terlihat sekaligus.
 *  aim: true = hanya baca barcode di dalam kotak bidik (kasir).
 *  continuous: true = kamera tetap terbuka untuk scan barang berikutnya (kasir), false = sekali (QR pasang).
 * Promise selesai saat kamera ditutup.
 */
export async function scan({ title = 'Arahkan kamera ke barcode', continuous = false, onCode, isKnown = null, aim = false }) {
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
  const frame = overlay.querySelector('.scan-frame');
  const marks = document.getElementById('scan-marks');
  const hint = document.getElementById('scan-hint');
  document.getElementById('scan-title').textContent = title;
  setScanText('', null);
  overlay.classList.toggle('aim', !!aim);
  paused = false;

  // Titik kecil di posisi barcode yang terlihat (hijau = di dalam kotak, abu-abu = diabaikan).
  let marksTimer = null;
  const clearMarks = () => {
    clearTimeout(marksTimer);
    if (marks) marks.innerHTML = '';
    if (hint) hint.hidden = true;
  };
  function showMarks(list) {
    if (!aim) return;
    if (marks) {
      marks.innerHTML = list
        .filter((b) => b.x != null)
        .map((b) => `<i class="${b.inside ? 'in' : 'out'}" style="left:${b.x}px;top:${b.y}px"></i>`)
        .join('');
    }
    if (hint) hint.hidden = list.some((b) => b.inside);
    clearTimeout(marksTimer);
    marksTimer = setTimeout(clearMarks, 400);
  }

  /** Tandai barcode yang di dalam kotak bidik; hasil: yang di dalam, terdekat ke tengah kotak dulu. */
  function inAim(list) {
    const r = aim && frame ? frame.getBoundingClientRect() : null;
    if (!r || !r.width) {
      for (const b of list) b.inside = true;
      return list;
    }
    const cx = r.left + r.width / 2;
    const cy = r.top + r.height / 2;
    const rx = (r.width / 2) * AIM_SLACK_X + AIM_PAD_PX;
    const ry = (r.height / 2) * AIM_SLACK_Y + AIM_PAD_PX;
    for (const b of list) {
      if (b.x == null) {
        b.inside = true; // kamera tidak memberi posisi: jangan ditolak
        b.dist = 0;
        continue;
      }
      const nx = (b.x - cx) / rx;
      const ny = (b.y - cy) / ry;
      b.inside = Math.abs(nx) <= 1 && Math.abs(ny) <= 1;
      b.dist = nx * nx + ny * ny;
    }
    return list.filter((b) => b.inside).sort((a, b) => a.dist - b.dist);
  }

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
      document.documentElement.classList.remove('scanning');
      document.body.classList.remove('scanning');
      overlay.hidden = true;
      clearMarks();
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
      const all = readBarcodes(ev);
      if (!all.length) return;
      const now = Date.now();
      if (paused) {
        clearMarks();
        choose(all.map((b) => b.code), now); // hanya menandai barcode yang terlihat
        return;
      }
      const aimed = inAim(all);
      showMarks(all);
      const codes = aimed.map((b) => b.code);
      if (!codes.length) return;
      if (isKnown && codes.some(isKnown)) lastKnownAt = now;
      if (handling) return;
      const code = choose(codes, now);
      if (!code) return;
      recent.set(code, { at: now, seen: now });
      if (pending && pending.code === code) pending = null;
      handling = true;
      try {
        const r = (await onCode(code)) || {};
        const ok = r.ok === undefined ? true : r.ok;
        if (!r.silent) beep(ok !== false);
        if (r.text) setScanText(r.text, ok);
        if (!continuous && ok === true) await stop();
      } finally {
        handling = false;
      }
    });

    // Kamera ada di BELAKANG WebView: <html> dan <body> harus transparan supaya gambarnya terlihat.
    document.documentElement.classList.add('scanning');
    document.body.classList.add('scanning');
    overlay.hidden = false;
    try {
      await BarcodeScanner.startScan({ formats: FORMATS });
    } catch (e) {
      await stop(new Error('Kamera tidak bisa dibuka: ' + (e.message || e)));
    }
  });
}
