// Pengganti '@capacitor/core' untuk Kasir PC.
// api.js (milik Kasir HP) memanggil CapacitorHttp.request bila "native": di sini diteruskan ke
// proses utama Electron (electron/main.js) sehingga tidak kena CORS dan punya batas waktu.
// Tanpa Electron (npm run dev di browser) dianggap bukan native -> api.js memakai fetch + proxy Vite.

const bridge = typeof window !== 'undefined' ? window.kasirPc : null;

export const Capacitor = {
  isNativePlatform: () => !!bridge,
  getPlatform: () => (bridge ? 'electron' : 'web'),
};

export const CapacitorHttp = {
  async request({ method = 'GET', url, headers = {}, data, readTimeout }) {
    const r = await bridge.http({
      method,
      url,
      headers,
      body: data === undefined ? undefined : JSON.stringify(data),
      timeout: readTimeout || 15000,
    });
    if (!r.ok) {
      const e = new Error(r.error || 'Jaringan bermasalah');
      e.code = r.code;
      throw e;
    }
    let body = r.text;
    try {
      body = JSON.parse(r.text);
    } catch (e) {
      /* bukan JSON: biarkan teks, api.js yang menilai */
    }
    return { status: r.status, data: body, headers: {}, url };
  },
};

/** Plugin Capacitor tidak ada di PC; native.js versi PC tidak memakainya. */
export function registerPlugin(name) {
  return new Proxy(
    {},
    { get: () => () => Promise.reject(new Error(`Fitur ${name} tidak tersedia di Kasir PC`)) }
  );
}
