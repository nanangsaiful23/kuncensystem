// Kasir PC tidak memakai kamera (scanner barcode USB bekerja seperti keyboard).
// Pengganti '@capacitor-mlkit/barcode-scanning' supaya kode Kasir HP tetap bisa dibundel.

export const BarcodeFormat = new Proxy({}, { get: (_t, k) => String(k) });

const unavailable = () => Promise.reject(new Error('Kamera tidak dipakai di Kasir PC. Pakai scanner barcode USB.'));

export const BarcodeScanner = {
  checkPermissions: unavailable,
  requestPermissions: unavailable,
  addListener: unavailable,
  startScan: unavailable,
  stopScan: async () => {},
  toggleTorch: async () => {},
};
