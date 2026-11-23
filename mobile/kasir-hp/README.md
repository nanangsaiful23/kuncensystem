# Kasir HP (APK Android)

Aplikasi kasir tambahan untuk HP Android, khusus transaksi penjualan.
**Local-first**: setiap transaksi disimpan di HP dulu, lalu dikirim ke server
(`/api/pos/v1`, lihat `docs/rancangan-kasir-hp-tahap1.md`) saat ada internet.
Kasir tidak pernah menunggu server.

---

## A. Panduan untuk toko (admin & kasir)

### 1. Pasang aplikasi di HP

1. Salin file `KasirHP-1.0.0.apk` ke HP (kabel USB, WhatsApp ke diri sendiri, Google Drive, dsb.).
2. Buka file itu di HP. Android akan bertanya soal **sumber tidak dikenal** →
   pilih **Setelan** → aktifkan **Izinkan dari sumber ini** → kembali → **Pasang**.
3. Kalau muncul peringatan Play Protect ("aplikasi tidak dikenal"), pilih **Tetap pasang**.
   (APK ini dibuat sendiri, tidak lewat Play Store.)

Update berikutnya: cukup pasang APK versi baru di atasnya. Data & transaksi di HP tetap ada.

### 2. Pasang perangkat (sekali per HP)

1. **Admin** (di komputer, login superadmin): buka **Pengaturan › Kasir HP** →
   **Tambah perangkat** → isi nama HP (mis. "HP Kasir 1"). Muncul **QR + kode 6 angka**
   (berlaku 15 menit, sekali pakai).
2. **Di HP**: buka Kasir HP → **Scan QR dari halaman admin** → arahkan kamera ke QR.
   Atau ketik **Alamat server** dan **Kode pasang** lalu tekan **Pasang**.
3. HP otomatis mengunduh data barang, harga, dan member (perlu internet, sekali saja;
   berikutnya hanya perubahan).

> Alamat server untuk produksi harus `https://…`. Alamat `http://` hanya diterima untuk
> jaringan lokal (192.168.x.x, 10.x.x.x, dsb.).

### 3. Login kasir & PIN

1. Pertama kali: **Tambah kasir** → isi email/username + password akun kasir yang sama
   dengan kasir komputer (perlu internet).
2. Buat **PIN 4–6 angka** (dua kali). PIN hanya disimpan di HP itu.
3. Berikutnya cukup pilih nama kasir + PIN — **tetap bisa walau offline**.
4. Satu HP bisa dipakai beberapa kasir. Lupa PIN → **Tambah kasir / lupa PIN** (login ulang online).
5. Salah PIN 5 kali → tunggu 1 menit.

### 4. Melayani pembeli

- Cari barang: ketik nama/kode, tekan 📷 untuk **scan barcode dengan kamera**
  (bisa scan berkali-kali tanpa menutup kamera), atau pakai scanner barcode USB/Bluetooth.
- Tekan nama barang = satuan terkecil; tekan tombol satuan (mis. `DUS 132.000`) untuk satuan lain.
- Tekan baris di keranjang untuk ganti satuan, jumlah (boleh desimal, mis. `0,5`), atau diskon baris.
- **Bayar** → pilih member (opsional, hanya dari daftar), potongan akhir, **Tunai** atau **Transfer**
  (centang "Transfer sudah masuk" setelah dicek), lalu **Simpan & Cetak**.
- Uang bayar harus ≥ total (tidak ada piutang). Tidak ada voucher dan retur di HP.
- **Harga grosir otomatis** sama dengan kasir web: PCS × 6 dengan PCS 3.500 dan PAK5 15.000 dihitung
  1 PAK5 + 1 PCS = 18.500 (keterangan "Harga grosir" tampil di baris). Disimpan per satuan seperti web.

### 5. Mengecek sambungan

| Yang dilihat | Arti |
|---|---|
| Badge **Online** (hijau) | Server terjangkau, cepat. |
| Badge **Lambat** (kuning) | Server terjangkau tapi jawab > 2 detik. Transaksi tetap aman. |
| Badge **Offline** (merah) | Tidak ada internet / server tidak terjangkau. Tetap bisa jualan; dikirim nanti. |
| Badge **Ditolak** (merah) | HP dinonaktifkan admin atau perlu dipasang ulang. Buka Pengaturan. |
| Strip "⏳ N belum terkirim" | Ada transaksi yang belum sampai server. Tekan **Sinkronkan** saat online. |
| Strip "✓ Semua terkirim" | Semua transaksi sudah di server. |
| "✗ N ditolak" | Server menolak transaksi (pesannya ada di Riwayat). Lapor admin. |

**Menu ☰ › Pengaturan › Tes koneksi** menampilkan alamat server, kode perangkat, jam server,
selisih jam HP, dan pesan bila gagal:

- **401** – perangkat belum terdaftar/sudah dihapus → minta kode baru ke admin → **Pasang ulang perangkat**.
- **403** – perangkat dinonaktifkan admin → minta admin mengaktifkan lagi.
- **Server tidak terjangkau** – cek data seluler/Wi-Fi HP dan alamat server.

Penting:
- Transaksi lebih lama dari **7 hari** ditolak server. Aplikasi memberi peringatan merah sejak
  transaksi berumur 5 hari — segera sambungkan internet dan tekan **Sinkronkan**.
- Pastikan **jam & tanggal HP otomatis** (aplikasi memberi peringatan bila jam HP selisih > 5 menit).
- Sinkron otomatis berjalan **selama aplikasi terbuka** (cek tiap 15 detik). Kalau aplikasi lama
  tertutup, buka sebentar saat ada internet supaya transaksi terkirim.
- Jangan **hapus data aplikasi / uninstall** sebelum semua transaksi terkirim.

### 6. Printer struk Bluetooth 58mm

1. Nyalakan printer → **Pengaturan Android › Bluetooth** → pair printer (PIN biasanya `0000` atau `1234`).
2. Di Kasir HP: **Pengaturan › Pilih printer** → pilih nama printer → **Cetak tes**.
   Penggaris 32 karakter harus muat satu baris. Printer 80mm: pilih lebar **80mm**.
3. Status printer tampil di atas layar kasir: "🖨 tersambung" / "tidak tersambung".
4. Cetak ulang struk: **Menu › Riwayat transaksi** → pilih transaksi → **Cetak ulang struk**.
5. Isi struk (nama toko, alamat, pesan bawah) diatur di **Pengaturan › Isi struk**.

---

## B. Untuk pengembang

### Struktur

| Bagian | File |
|---|---|
| Tampilan (vanilla JS + Vite) | `index.html`, `src/views/*.js`, `src/style.css` |
| Penyimpanan HP (IndexedDB) | `src/db.js` — `sales` (transaksi), `goods`, `members`, `cashiers` (hash PIN), `kv` |
| API server | `src/api.js` (HTTP native `CapacitorHttp`: tanpa CORS, ada batas waktu) |
| Sinkron & status sambungan | `src/sync.js`, `src/status.js` |
| Simpan transaksi & cetak | `src/sales.js`, `src/receipt.js` (ESC/POS 32/48 kolom) |
| Harga grosir otomatis (sama dengan web `unit-tier.blade.php`) | `src/tier.js`, dipakai `cart.js` & `sales.js` |
| Scan kamera | `src/scanner.js` (`@capacitor-mlkit/barcode-scanning`, model ML Kit ikut di APK → offline) |
| Native Android | `android/app/src/main/java/com/ntnmart/kasirhp/KasirNativePlugin.java`: token terenkripsi Android Keystore + printer Bluetooth Classic (SPP/RFCOMM) |

Keamanan: token perangkat di Keystore (bukan IndexedDB); PIN di-hash PBKDF2-SHA256;
`allowBackup=false`. HTTP cleartext diizinkan di `network_security_config.xml`, tetapi
aplikasi menolak `http://` untuk alamat non-lokal (`normalizeServer` di `src/util.js`).

### Kebutuhan build

- Node.js 22+ (diuji 26), JDK 21 (diuji Temurin 21 di `C:\Users\Saiful\.jdks\jdk-21.0.13.1+2`),
  Android SDK Platform 36 + Build-tools 35 (`%LOCALAPPDATA%\Android\Sdk`).
- `android/local.properties` berisi `sdk.dir=C:/Users/<nama>/AppData/Local/Android/Sdk` (tidak di git).

### Build APK

```powershell
cd mobile\kasir-hp
npm install
npm run build
npx cap sync android
$env:JAVA_HOME = "C:\Users\Saiful\.jdks\jdk-21.0.13.1+2"
cd android; .\gradlew.bat assembleRelease
# hasil: android\app\build\outputs\apk\release\app-release.apk
```

Sebelum membuat versi baru: naikkan `versionCode` (dan `versionName`) di `android/app/build.gradle`
serta `APP_VERSION` di `src/views/pengaturan.js`.

### Kunci tanda tangan (keystore) — JANGAN HILANG

- Lokasi: `C:\Users\Saiful\kasir-hp-keystore\kasir-hp-release.jks`, password di
  `C:\Users\Saiful\kasir-hp-keystore\keystore.properties`. Keduanya **di luar git**.
- Simpan cadangan kedua file itu (flashdisk / Google Drive pribadi). Tanpa kunci yang sama,
  APK versi baru **tidak bisa dipasang sebagai update** — HP harus uninstall dulu dan semua
  transaksi yang belum terkirim di HP itu ikut hilang.
- Lokasi lain bisa diatur dengan variabel lingkungan `KASIR_KEYSTORE_PROPS`.

### Uji dengan server Laravel lokal

1. Jalankan Laravel yang bisa dijangkau HP di Wi-Fi yang sama:
   `php artisan serve --host=0.0.0.0 --port=8000` (izinkan PHP di Windows Firewall bila ditanya).
2. Buka halaman admin dari **alamat IP komputer** (mis. `http://192.168.1.5:8000/admin/kasir-hp`),
   bukan `localhost`, supaya QR berisi alamat yang bisa dijangkau HP.
3. Mode browser (tanpa APK): `npm run dev` lalu buka `http://localhost:5173`, alamat server
   `localhost:5173` (Vite meneruskan `/api` ke `KASIR_API`, bawaan `http://127.0.0.1:8010`).
   Kamera & printer diganti tiruan di mode ini.
4. Emulator Android: jaringan NAT emulator (`10.0.2.2`) di komputer ini terbukti kadang memotong
   data. Pakai `adb reverse tcp:8010 tcp:8010` lalu alamat server `127.0.0.1:8010`.

### Catatan

- `php artisan serve` dan semua HP di toko lewat satu IP publik berbagi batas API 60 permintaan/menit
  (grup `api`). Aplikasi ping tiap 15 detik, jadi aman untuk beberapa HP.
- Belum diuji dengan printer fisik (tidak tersedia saat pengembangan) — lakukan **Cetak tes** dulu.
