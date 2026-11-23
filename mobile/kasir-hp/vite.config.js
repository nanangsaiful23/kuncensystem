import { defineConfig } from 'vite';

// Server Laravel untuk mode uji di browser (npm run dev): permintaan /api diteruskan ke sini
// supaya tidak kena CORS. Di APK tidak dipakai (HP langsung ke alamat server).
const API = process.env.KASIR_API || 'http://127.0.0.1:8010';

export default defineConfig({
  base: './',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    // WebView HP murah bisa versi lama.
    target: ['es2019', 'chrome64'],
    assetsInlineLimit: 0,
  },
  server: {
    host: true,
    port: 5173,
    proxy: { '/api': { target: API, changeOrigin: true } },
  },
});
