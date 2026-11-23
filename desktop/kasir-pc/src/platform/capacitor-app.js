// Pengganti '@capacitor/app' untuk Kasir PC.
// sync.js memakai appStateChange untuk langsung cek server saat aplikasi aktif lagi:
// di PC dipicu saat jendela kembali difokuskan.

const listeners = { appStateChange: new Set(), backButton: new Set() };

if (typeof window !== 'undefined') {
  window.addEventListener('focus', () => listeners.appStateChange.forEach((fn) => fn({ isActive: true })));
  window.addEventListener('blur', () => listeners.appStateChange.forEach((fn) => fn({ isActive: false })));
}

export const App = {
  addListener(event, fn) {
    const set = listeners[event] || (listeners[event] = new Set());
    set.add(fn);
    const handle = { remove: async () => set.delete(fn) };
    // Capacitor mengembalikan Promise berisi handle; dibuat bisa dipakai keduanya.
    return Object.assign(Promise.resolve(handle), handle);
  },
  minimizeApp: async () => {},
  exitApp: async () => window.close(),
};
