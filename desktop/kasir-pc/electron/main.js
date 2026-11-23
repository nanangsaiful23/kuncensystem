// Kasir PC: proses utama Electron.
// - Menyajikan tampilan (dist/) lewat app://kasir supaya IndexedDB & crypto.subtle jalan normal.
// - Jembatan untuk tampilan (lihat preload.js): HTTP ke server tanpa CORS, token perangkat
//   terenkripsi (Windows DPAPI lewat safeStorage), daftar printer Windows, dan cetak struk tanpa dialog.

const { app, BrowserWindow, protocol, net, ipcMain, safeStorage, session, dialog, shell, Menu } = require('electron');
const path = require('node:path');
const fs = require('node:fs');
const { pathToFileURL } = require('node:url');

const DIST = path.join(__dirname, '..', 'dist');
const DEV_URL = process.env.KASIR_DEV_URL || ''; // mis. http://localhost:5180 (npm run dev)
const APP_ORIGIN = 'app://kasir';

protocol.registerSchemesAsPrivileged([
  { scheme: 'app', privileges: { standard: true, secure: true, supportFetchAPI: true, stream: true } },
]);

app.setAppUserModelId('com.ntnmart.kasirpc');

// Satu aplikasi saja: dua jendela kasir pada data yang sama bisa membuat nomor nota dobel.
if (!app.requestSingleInstanceLock()) {
  app.quit();
}

let win = null;
let pendingCount = 0;
let allowClose = false;

// ---- Pengaturan kecil milik proses utama (zoom tampilan) ----
const configFile = () => path.join(app.getPath('userData'), 'kasir-pc.json');
function readConfig() {
  try {
    return JSON.parse(fs.readFileSync(configFile(), 'utf8'));
  } catch (e) {
    return {};
  }
}
function writeConfig(patch) {
  const c = { ...readConfig(), ...patch };
  fs.writeFileSync(configFile(), JSON.stringify(c, null, 2));
}

// ---- Rahasia (token perangkat) ----
const secretFile = () => path.join(app.getPath('userData'), 'secrets.json');
function readSecrets() {
  try {
    return JSON.parse(fs.readFileSync(secretFile(), 'utf8'));
  } catch (e) {
    return {};
  }
}
function writeSecrets(obj) {
  const tmp = secretFile() + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(obj));
  fs.renameSync(tmp, secretFile());
}
async function secretSet(key, value) {
  const all = readSecrets();
  const enc = await safeStorage.isAsyncEncryptionAvailable();
  all[key] = {
    enc,
    v: enc ? (await safeStorage.encryptStringAsync(String(value))).toString('base64') : Buffer.from(String(value), 'utf8').toString('base64'),
  };
  writeSecrets(all);
}
async function secretGet(key) {
  const s = readSecrets()[key];
  if (!s) return null;
  try {
    if (!s.enc) return Buffer.from(s.v, 'base64').toString('utf8');
    const r = await safeStorage.decryptStringAsync(Buffer.from(s.v, 'base64'));
    if (r.shouldReEncrypt) await secretSet(key, r.result); // kunci enkripsi Windows diperbarui
    return r.result;
  } catch (e) {
    return null; // tidak bisa dibuka (mis. akun Windows lain) -> dianggap belum dipasang
  }
}
function secretRemove(key) {
  const all = readSecrets();
  delete all[key];
  writeSecrets(all);
}

// ---- Keamanan IPC: hanya tampilan aplikasi sendiri yang boleh memanggil ----
function trusted(event) {
  const url = (event.senderFrame && event.senderFrame.url) || '';
  return url.startsWith(APP_ORIGIN + '/') || (DEV_URL && url.startsWith(DEV_URL));
}
function handle(channel, fn) {
  ipcMain.handle(channel, async (event, ...args) => {
    if (!trusted(event)) throw new Error('Akses ditolak');
    return fn(event, ...args);
  });
}

// ---- HTTP ke server Laravel (tanpa CORS, dengan batas waktu) ----
let apiSession = null;
async function httpRequest(_e, req) {
  const { method = 'GET', url, headers = {}, body, timeout = 15000 } = req || {};
  let u;
  try {
    u = new URL(url);
  } catch (e) {
    return { ok: false, code: 'badurl', error: 'Alamat server tidak valid.' };
  }
  if (u.protocol !== 'http:' && u.protocol !== 'https:') return { ok: false, code: 'badurl', error: 'Alamat harus http/https.' };
  if (!apiSession) apiSession = session.fromPartition('kasir-api', { cache: false });
  const ctl = new AbortController();
  const timer = setTimeout(() => ctl.abort(), Math.max(1000, Number(timeout) || 15000));
  try {
    const res = await apiSession.fetch(url, {
      method,
      headers,
      body: body === undefined || body === null ? undefined : String(body),
      signal: ctl.signal,
      cache: 'no-store',
      credentials: 'omit',
    });
    const text = await res.text();
    return { ok: true, status: res.status, text };
  } catch (e) {
    if (ctl.signal.aborted) return { ok: false, code: 'timeout', error: 'timeout: server tidak menjawab' };
    return { ok: false, code: 'network', error: String((e && e.message) || e) };
  } finally {
    clearTimeout(timer);
  }
}

// ---- Cetak struk (HTML) ke printer Windows ----
async function listPrinters() {
  if (!win) return [];
  const list = await win.webContents.getPrintersAsync();
  return list.map((p) => ({
    name: p.name,
    displayName: p.displayName || p.name,
    isDefault: !!p.isDefault,
    status: p.status,
    description: p.description || '',
  }));
}

function printHtml(_e, job) {
  const { html, deviceName, widthMm = 58, silent = true } = job || {};
  return new Promise((resolve) => {
    const pw = new BrowserWindow({
      show: false,
      width: Math.round((widthMm / 25.4) * 96) + 40,
      height: 600,
      webPreferences: { sandbox: true, contextIsolation: true, javascript: true, spellcheck: false },
    });
    let finished = false;
    const finish = (r) => {
      if (finished) return;
      finished = true;
      clearTimeout(guard);
      setTimeout(() => !pw.isDestroyed() && pw.destroy(), 500);
      resolve(r);
    };
    const guard = setTimeout(() => finish({ ok: false, error: 'Printer tidak merespons (waktu habis).' }), 60000);
    pw.webContents.once('did-fail-load', (_ev, _code, desc) => finish({ ok: false, error: 'Struk gagal disiapkan: ' + desc }));
    pw.webContents.once('did-finish-load', async () => {
      let heightPx = 600;
      try {
        heightPx = await pw.webContents.executeJavaScript('Math.ceil(document.documentElement.getBoundingClientRect().height)');
      } catch (e) {
        /* pakai perkiraan */
      }
      // Tinggi kertas = tinggi isi (mikron), supaya struk tidak terpotong jadi beberapa "halaman".
      const height = Math.max(40000, Math.ceil((heightPx * 25400) / 96) + 4000);
      const opts = {
        silent: !!silent,
        printBackground: true,
        margins: { marginType: 'none' },
        pageSize: { width: Math.round(widthMm * 1000), height },
        copies: 1,
      };
      if (deviceName) opts.deviceName = deviceName;
      pw.webContents.print(opts, (success, failureReason) => {
        const why = String(failureReason || '');
        if (success) finish({ ok: true });
        else if (/cancel/i.test(why)) finish({ ok: false, cancelled: true, error: 'Cetak dibatalkan.' });
        else if (/deviceName/i.test(why)) {
          finish({ ok: false, error: `Printer "${deviceName}" tidak ditemukan di Windows. Cek kabel/daya printer, atau pilih ulang di Pengaturan.` });
        } else finish({ ok: false, error: 'Gagal mencetak: ' + (why || 'tidak diketahui') });
      });
    });
    pw.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(html || '<p>(kosong)</p>'));
  });
}

// ---- Jendela utama ----
function createWindow() {
  const cfg = readConfig();
  win = new BrowserWindow({
    width: 1366,
    height: 768,
    minWidth: 1000,
    minHeight: 620,
    show: false,
    backgroundColor: '#f1f4f2',
    title: 'Kasir PC',
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      sandbox: true,
      nodeIntegration: false,
      spellcheck: false,
    },
  });
  win.once('ready-to-show', () => {
    win.maximize();
    win.show();
  });
  win.webContents.on('did-finish-load', () => {
    if (cfg.zoom) win.webContents.setZoomFactor(cfg.zoom);
  });

  // Jangan pernah pindah ke situs lain di dalam jendela kasir.
  win.webContents.setWindowOpenHandler(({ url }) => {
    if (/^https?:\/\//i.test(url)) shell.openExternal(url);
    return { action: 'deny' };
  });
  win.webContents.on('will-navigate', (e, url) => {
    if (!url.startsWith(APP_ORIGIN + '/') && !(DEV_URL && url.startsWith(DEV_URL))) e.preventDefault();
  });

  // Tombol pintas jendela: Ctrl +/-/0 ukuran tampilan, F11 layar penuh, Ctrl+Shift+I alat pengembang.
  win.webContents.on('before-input-event', (e, input) => {
    if (input.type !== 'keyDown') return;
    const wc = win.webContents;
    if (input.control && input.shift && input.key.toLowerCase() === 'i') {
      wc.toggleDevTools();
      e.preventDefault();
    } else if (input.control && (input.key === '=' || input.key === '+')) {
      const z = Math.min(2, Math.round((wc.getZoomFactor() + 0.1) * 10) / 10);
      wc.setZoomFactor(z);
      writeConfig({ zoom: z });
      e.preventDefault();
    } else if (input.control && input.key === '-') {
      const z = Math.max(0.6, Math.round((wc.getZoomFactor() - 0.1) * 10) / 10);
      wc.setZoomFactor(z);
      writeConfig({ zoom: z });
      e.preventDefault();
    } else if (input.control && input.key === '0') {
      wc.setZoomFactor(1);
      writeConfig({ zoom: 1 });
      e.preventDefault();
    } else if (input.key === 'F11') {
      win.setFullScreen(!win.isFullScreen());
      e.preventDefault();
    } else if ((input.control && input.key.toLowerCase() === 'r') || input.key === 'F5') {
      e.preventDefault(); // muat ulang tidak sengaja = kasir harus login PIN lagi
    }
  });

  win.on('close', (e) => {
    if (allowClose || pendingCount <= 0) return;
    const choice = dialog.showMessageBoxSync(win, {
      type: 'warning',
      title: 'Kasir PC',
      message: `Masih ada ${pendingCount} transaksi belum terkirim ke server.`,
      detail:
        'Transaksi tetap aman tersimpan di komputer ini dan dikirim otomatis saat Kasir PC dibuka lagi dan ada internet.\n' +
        'Server menolak transaksi yang lebih lama dari 7 hari, jadi jangan biarkan terlalu lama.',
      buttons: ['Tutup aplikasi', 'Batal'],
      defaultId: 1,
      cancelId: 1,
      noLink: true,
    });
    if (choice !== 0) e.preventDefault();
    else allowClose = true;
  });
  win.on('closed', () => (win = null));

  if (DEV_URL) win.loadURL(DEV_URL);
  else win.loadURL(APP_ORIGIN + '/index.html');
}

app.on('second-instance', () => {
  if (!win) return;
  if (win.isMinimized()) win.restore();
  win.show();
  win.focus();
});

app.whenReady().then(() => {
  Menu.setApplicationMenu(null);

  protocol.handle('app', async (request) => {
    const u = new URL(request.url);
    let rel = decodeURIComponent(u.pathname);
    if (!rel || rel === '/') rel = '/index.html';
    const file = path.normalize(path.join(DIST, rel));
    if (u.host !== 'kasir' || !file.startsWith(DIST + path.sep) || !fs.existsSync(file)) {
      return new Response('Tidak ditemukan', { status: 404 });
    }
    const res = await net.fetch(pathToFileURL(file).toString());
    if (!file.endsWith('.html')) return res;
    const headers = new Headers(res.headers);
    headers.set(
      'Content-Security-Policy',
      "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'none'"
    );
    return new Response(res.body, { status: res.status, headers });
  });

  handle('kasir:http', httpRequest);
  handle('kasir:secret', (_e, op, key, value) => {
    if (op === 'get') return secretGet(key);
    if (op === 'set') return secretSet(key, value);
    if (op === 'remove') return secretRemove(key);
    throw new Error('Perintah tidak dikenal');
  });
  handle('kasir:printers', listPrinters);
  handle('kasir:print', printHtml);
  handle('kasir:info', async () => ({
    version: app.getVersion(),
    electron: process.versions.electron,
    chrome: process.versions.chrome,
    platform: `${process.platform} ${process.arch}`,
    dataDir: app.getPath('userData'),
    encrypted: await safeStorage.isAsyncEncryptionAvailable(),
  }));
  handle('kasir:open-data', () => shell.openPath(app.getPath('userData')));
  ipcMain.on('kasir:pending', (event, n) => {
    if (trusted(event)) pendingCount = Number(n) || 0;
  });

  createWindow();
});

app.on('window-all-closed', () => app.quit());
