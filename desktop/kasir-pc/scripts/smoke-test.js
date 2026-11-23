// Uji cepat aplikasi Electron (bukan bagian aplikasi): buka Kasir PC dengan profil data terpisah,
// cek jembatan (HTTP ke server, token terenkripsi, daftar printer), cetak ke printer yang tidak ada
// (harus gagal dengan pesan jelas), lalu simpan tangkapan layar.
//
//   npm run build
//   npx electron scripts/smoke-test.js [http://127.0.0.1:8084]
//   npx electron scripts/smoke-test.js --asar release/win-unpacked/resources/app.asar   (hasil npm run dist)
//
// Hasil: release/smoke/hasil.json dan release/smoke/layar.png

const { app } = require('electron');
const path = require('node:path');
const fs = require('node:fs');

const out = path.join(__dirname, '..', 'release', 'smoke');
fs.mkdirSync(out, { recursive: true });
app.setPath('userData', path.join(out, 'userdata')); // tidak menyentuh data Kasir PC yang asli
const server = process.argv.find((a) => /^https?:\/\//.test(a)) || 'http://127.0.0.1:8084';

const asarIdx = process.argv.indexOf('--asar');
const appRoot = asarIdx > 0 ? path.resolve(process.argv[asarIdx + 1]) : path.join(__dirname, '..');
require(path.join(appRoot, 'electron', 'main.js'));

app.on('browser-window-created', (_e, win) => {
  if (win.__smoke) return;
  win.__smoke = true;
  win.webContents.once('did-finish-load', () => setTimeout(() => run(win), 1500));
});

async function run(win) {
  const js = (code) => win.webContents.executeJavaScript(code);
  const result = {};
  try {
    result.bridge = await js('typeof window.kasirPc');
    result.title = await js('document.title');
    result.screen = await js("(document.querySelector('.tb-title') || {}).textContent || ''");
    result.ping = await js(`window.kasirPc.http({ url: ${JSON.stringify(server + '/api/pos/v1/ping')}, headers: { Accept: 'application/json' }, timeout: 8000 })
      .then(r => ({ ok: r.ok, status: r.status, body: String(r.text || r.error).slice(0, 120) }))`);
    result.unreachable = await js(`window.kasirPc.http({ url: 'http://127.0.0.1:9/api/pos/v1/ping', timeout: 3000 })`);
    result.secret = await js(`(async () => {
      await window.kasirPc.secretSet('smoke', 'rahasia-123');
      const v = await window.kasirPc.secretGet('smoke');
      await window.kasirPc.secretRemove('smoke');
      return { roundtrip: v === 'rahasia-123', afterRemove: await window.kasirPc.secretGet('smoke') };
    })()`);
    const raw = fs.readFileSync(path.join(out, 'userdata', 'secrets.json'), 'utf8');
    result.secretFileHasPlainText = raw.includes('rahasia-123');
    result.info = await js('window.kasirPc.info()');
    result.printers = await js('window.kasirPc.printers().then(l => l.map(p => p.name))');
    result.printMissing = await js(`window.kasirPc.print({ html: '<p>tes</p>', deviceName: 'Printer Tidak Ada XYZ', widthMm: 58, silent: true })`);
    const img = await win.webContents.capturePage();
    fs.writeFileSync(path.join(out, 'layar.png'), img.toPNG());
  } catch (e) {
    result.error = String((e && e.stack) || e);
  }
  fs.writeFileSync(path.join(out, 'hasil.json'), JSON.stringify(result, null, 2));
  console.log(JSON.stringify(result, null, 2));
  app.exit(0);
}
