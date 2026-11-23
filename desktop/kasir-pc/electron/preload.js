// Jembatan aman antara tampilan (tanpa akses Node) dan proses utama Electron.
// Tampilan memakai window.kasirPc (lihat src/platform/*.js).

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('kasirPc', {
  http: (req) => ipcRenderer.invoke('kasir:http', req),
  secretGet: (key) => ipcRenderer.invoke('kasir:secret', 'get', key),
  secretSet: (key, value) => ipcRenderer.invoke('kasir:secret', 'set', key, value),
  secretRemove: (key) => ipcRenderer.invoke('kasir:secret', 'remove', key),
  printers: () => ipcRenderer.invoke('kasir:printers'),
  print: (job) => ipcRenderer.invoke('kasir:print', job),
  info: () => ipcRenderer.invoke('kasir:info'),
  openDataFolder: () => ipcRenderer.invoke('kasir:open-data'),
  setPending: (n) => ipcRenderer.send('kasir:pending', n),
});
