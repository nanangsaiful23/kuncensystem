// Membuat build/icon.png (512x512) untuk installer & shortcut:  npm run icon
// Digambar dari SVG di bawah memakai Electron sendiri (tanpa alat gambar lain).

const { app, BrowserWindow } = require('electron');
const path = require('node:path');
const fs = require('node:fs');

const SVG = `<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <rect x="16" y="16" width="480" height="480" rx="104" fill="#0f7a45"/>
  <rect x="96" y="104" width="320" height="214" rx="26" fill="#ffffff"/>
  <rect x="124" y="132" width="264" height="104" rx="12" fill="#dcfce7"/>
  <rect x="148" y="160" width="120" height="20" rx="10" fill="#0f7a45"/>
  <rect x="148" y="194" width="200" height="20" rx="10" fill="#15803d" opacity=".55"/>
  <rect x="124" y="258" width="60" height="34" rx="8" fill="#0f7a45"/>
  <rect x="196" y="258" width="60" height="34" rx="8" fill="#0f7a45"/>
  <rect x="268" y="258" width="120" height="34" rx="8" fill="#f59e0b"/>
  <rect x="206" y="318" width="100" height="44" fill="#e5e7eb"/>
  <rect x="132" y="362" width="248" height="34" rx="17" fill="#ffffff"/>
</svg>`;

app.whenReady().then(async () => {
  const win = new BrowserWindow({ width: 512, height: 512, show: false, frame: false, transparent: true, useContentSize: true });
  await win.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(
    `<html><body style="margin:0;background:transparent">${SVG}</body></html>`));
  await new Promise((r) => setTimeout(r, 300));
  const img = await win.webContents.capturePage({ x: 0, y: 0, width: 512, height: 512 });
  const file = path.join(__dirname, '..', 'build', 'icon.png');
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, img.resize({ width: 512, height: 512 }).toPNG());
  console.log('Ikon dibuat: ' + file);
  app.exit(0);
});
