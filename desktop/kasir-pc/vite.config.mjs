import { defineConfig } from 'vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';

// Kasir PC memakai ulang kode Kasir HP (mobile/kasir-hp/src): database lokal, sinkron, keranjang,
// login PIN, riwayat. Bagian yang khusus Android diganti di sini:
//  - modul yang DIGANTI (OVERRIDES): setiap import ke file Kasir HP itu diarahkan ke versi PC;
//  - paket Capacitor diganti tiruan yang memakai jembatan Electron (src/platform/*);
//  - beberapa teks "HP" diganti "komputer" (TEXTS). Bila teks di Kasir HP berubah, build memberi peringatan.

const here = path.dirname(fileURLToPath(import.meta.url));
const HP = path.resolve(here, '../../mobile/kasir-hp/src');
const API = process.env.KASIR_API || 'http://127.0.0.1:8084'; // = "kuncen-php84" di .claude/launch.json

const OVERRIDES = {
  'native.js': 'src/platform/native.js',
  'sales.js': 'src/sales.js',
};

const TEXTS = {
  'api.js': [
    ['Pasang ulang HP dengan kode dari admin.', 'Pasang ulang komputer ini dengan kode dari admin.'],
    ['Cek internet/Wi-Fi HP dan alamat server.', 'Cek internet/Wi-Fi komputer dan alamat server.'],
    [
      `' Catatan: "localhost" di HP berarti HP itu sendiri; pakai alamat IP komputer server (mis. http://192.168.1.5:8000).'`,
      `' Catatan: "localhost" berarti komputer kasir ini sendiri; pakai alamat IP komputer server (mis. http://192.168.1.5:8000).'`,
    ],
    ['HP belum dipasang ke server.', 'Komputer ini belum dipasang ke server.'],
  ],
  'status.js': [
    ['Jam HP ', 'Jam komputer '],
    ['di Pengaturan Android supaya', 'di Windows (Pengaturan › Waktu & bahasa) supaya'],
  ],
  'views/lock.js': [
    ['<div class="tb-title">Kasir HP <span', '<div class="tb-title">Kasir PC <span'],
    ['PIN hanya disimpan di HP ini', 'PIN hanya disimpan di komputer ini'],
    ['kasir yang sudah ada di HP cukup pakai PIN', 'kasir yang sudah ada di komputer ini cukup pakai PIN'],
  ],
  'views/download.js': [['diunduh sekali ke HP.', 'diunduh sekali ke komputer ini.']],
  'views/riwayat.js': [['Tersimpan di HP ini', 'Tersimpan di komputer ini']],
};

const norm = (p) => path.resolve(String(p).split('?')[0]).toLowerCase();

function kasirHpBridge() {
  const overrides = new Map(Object.entries(OVERRIDES).map(([k, v]) => [norm(path.join(HP, k)), path.resolve(here, v)]));
  const texts = new Map(Object.entries(TEXTS).map(([k, v]) => [norm(path.join(HP, k)), { file: k, pairs: v }]));
  return {
    name: 'kasir-hp-bridge',
    enforce: 'pre',
    async resolveId(source, importer, options) {
      if (!importer || source.startsWith('\0')) return null;
      const r = await this.resolve(source, importer, { ...options, skipSelf: true });
      if (!r || r.external) return null;
      const target = overrides.get(norm(r.id));
      // Versi PC sendiri boleh mengimpor file aslinya (mis. src/sales.js memakai saveSale dari Kasir HP).
      if (target && norm(importer) !== norm(target)) return target;
      return null;
    },
    transform(code, id) {
      const t = texts.get(norm(id));
      if (!t) return null;
      for (const [from, to] of t.pairs) {
        if (!code.includes(from)) {
          this.warn(`Teks Kasir HP tidak ditemukan di ${t.file}: ${from.slice(0, 60)}… (cek TEXTS di vite.config.mjs)`);
          continue;
        }
        code = code.split(from).join(to);
      }
      return { code, map: null };
    },
  };
}

const pkg = JSON.parse(readFileSync(path.join(here, 'package.json'), 'utf8'));

export default defineConfig({
  base: './',
  plugins: [kasirHpBridge()],
  define: { __APP_VERSION__: JSON.stringify(pkg.version) },
  resolve: {
    alias: {
      '@hp': HP,
      '@capacitor/core': path.resolve(here, 'src/platform/capacitor-core.js'),
      '@capacitor/app': path.resolve(here, 'src/platform/capacitor-app.js'),
      '@capacitor-mlkit/barcode-scanning': path.resolve(here, 'src/platform/no-camera.js'),
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    target: 'es2020',
    assetsInlineLimit: 0,
  },
  server: {
    port: 5180,
    fs: { allow: [here, HP] },
    // Mode uji di browser (npm run dev, tanpa Electron): /api diteruskan ke Laravel lokal.
    proxy: { '/api': { target: API, changeOrigin: true } },
  },
});
