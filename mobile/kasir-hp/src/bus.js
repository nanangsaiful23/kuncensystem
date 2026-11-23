// Pengumuman antar bagian aplikasi (mis. status sambungan berubah -> tampilan ikut berubah).
const handlers = new Map();

export const bus = {
  on(event, fn) {
    if (!handlers.has(event)) handlers.set(event, new Set());
    handlers.get(event).add(fn);
    return () => handlers.get(event).delete(fn);
  },
  emit(event, payload) {
    (handlers.get(event) || []).forEach((fn) => {
      try {
        fn(payload);
      } catch (e) {
        console.error(e);
      }
    });
  },
};
