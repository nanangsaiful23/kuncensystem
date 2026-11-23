# Kasir PC (aplikasi Windows, tetap jalan saat offline)

Aplikasi kasir untuk komputer toko, khusus transaksi penjualan. Cara kerjanya sama dengan
Kasir HP (`mobile/kasir-hp`): **setiap transaksi disimpan di komputer dulu**, lalu dikirim ke
server (`/api/pos/v1`, lihat `docs/rancangan-kasir-hp-tahap1.md`) saat ada internet.
Kasir tidak pernah menunggu server, jadi internet lambat/mati tidak menghentikan penjualan.

Bedanya dengan Kasir HP: satu layar lebar (cari, keranjang, bayar sekaligus), dipakai dengan
keyboard + scanner barcode USB, dan struk dicetak ke printer yang terpasang di Windows.

Kebutuhan: **Windows 10 atau 11, 64-bit**. (Windows 7/8 tidak didukung Electron versi ini.)

---

## A. Panduan untuk toko

### 1. Pasang aplikasi

1. Jalankan `KasirPC-Setup-<versi>.exe` (mis. `KasirPC-Setup-1.1.0.exe`) → ikuti langkahnya. Muncul shortcut **Kasir PC** di desktop.
2. Kalau Windows menampilkan "Windows melindungi PC Anda" (SmartScreen): klik **Info selengkapnya** →
   **Tetap jalankan**. (Installer ini dibuat sendiri, belum ditandatangani sertifikat.)
3. Update berikutnya: jalankan installer versi baru. Data & transaksi di komputer tetap ada.

### 2. Pasang perangkat (sekali per komputer)

1. **Admin** (login superadmin di sistem): **Pengaturan › Kasir HP** → pilih jenis
   **Komputer / Kasir PC**, isi nama (mis. "PC Kasir Depan") → **Tambah perangkat**.
   Muncul **kode 6 angka** dan **Alamat server** (berlaku 15 menit, sekali pakai).
   Komputer mendapat kode **PC1, PC2, …**, jadi nomor notanya `PC1-20261123-0001`.
2. **Di Kasir PC**: ketik **Alamat server** dan **Kode pasang** → **Pasang**.
3. Data barang, harga, dan member diunduh sekali (perlu internet). Setelah itu hanya perubahannya.

### 3. Kasir & PIN

Sama seperti Kasir HP: pertama kali **Tambah kasir** dengan email/username + password akun kasir
(perlu internet), lalu buat **PIN 4–6 angka**. Berikutnya cukup pilih nama + ketik PIN —
**bisa walau offline**. PIN diketik langsung dengan keyboard (angka, Backspace, Enter).

### 4. Melayani pembeli (keyboard)

Susunan layar dan tombol pintasnya **sama dengan Tambah Transaksi di web**, jadi kasir tidak perlu
belajar ulang: kolom cari di atas, tabel barang (yang terbaru di paling atas), member / pembayaran /
catatan di bawah tabel, dan panel pembayaran di kanan. Hampir semuanya bisa tanpa mouse.

**Alur cepat:** scan barang satu per satu → `F8` → ketik uang (atau `Alt+1` uang pas) → `Enter` →
`Enter` lagi untuk transaksi baru.

| Tombol | Fungsi |
|---|---|
| Scan barcode | Barang langsung masuk tabel. Barcode yang sama: jumlahnya bertambah |
| `F2` / `F4` / `/` | Ke kolom cari |
| Ketik nama → `↑` `↓` | Pilih barang di daftar hasil; `←` `→` ganti satuan (PCS, PAK, DUS…); `Enter` tambahkan |
| `Insert` / `Shift+↑↓` | Centang beberapa barang sekaligus, lalu `Enter` |
| `5*kode` / `5*nama` | Tambah dengan jumlah 5 |
| `+` / `−` (kolom cari kosong) | Tambah / kurangi jumlah baris terakhir |
| `F3` atau `↓` (kolom cari kosong) | Ke kolom Jumlah baris paling atas |
| Di tabel: `Enter` / `↑` `↓` / `←` `→` | Baris berikut / baris atas-bawah / Jumlah ↔ Potongan; `Esc` kembali ke cari |
| Di tabel: `Alt+←` `Alt+→` | Ganti satuan baris itu |
| Di tabel: `Alt+Del` | Hapus baris |
| `F7` | Member: ketik nama/toko/no. HP → `↑` `↓` → `Enter` (langsung ke Bayar). Kosongkan = umum |
| Pembayaran | `Tab` ke tombol Tunai/Transfer, `←` `→` untuk mengganti |
| `F8` | Bayar (transfer: centang "Transfer sudah masuk" dengan `Spasi`) |
| `Alt+1`…`Alt+6` | Uang cepat: uang pas, lalu pembulatan ke atas |
| `Enter` di Bayar, `Ctrl+Enter`, atau `F12` | Proses transaksi (cetak struk bila "Cetak struk" dicentang) |
| Sesudah proses: `Enter` / `Esc` | Transaksi baru; `P` cetak ulang struk |
| `F9` | Riwayat transaksi (cetak ulang, lihat yang belum terkirim/ditolak) |
| `Ctrl+L` | Ganti kasir / kunci |
| `Ctrl` `+` / `−` / `0`, `F11` | Ukuran tampilan, layar penuh |

**Harga grosir otomatis** (sama dengan web): ketik jumlah di satuan kecil, misalnya PCS × 6, maka harga satuan
besar dipakai bila lebih murah. Contoh: PCS 3.500 dan PCS5 15.000 → 6 PCS = 1 PCS5 + 1 PCS = 18.500, dengan keterangan
"Harga grosir: 1 PCS5 + 1 PCS (hemat 2.500)". Saat disimpan, baris itu dikirim per satuan (PCS5 × 1 dan PCS × 1)
supaya stok, HPP, dan struk sama seperti transaksi web.

Retur (`F6` di web) hanya bisa di kasir web. Barcode yang tidak sengaja ter-scan saat kursor ada di
kolom lain (Bayar, Jumlah, Member) tetap masuk sebagai barang dan isi kolom itu dikembalikan.
Uang bayar harus ≥ total (tidak ada hutang). Kembalian ≥ Rp500.000 diminta konfirmasi.

### 5. Printer struk

1. Pasang driver printer thermal di Windows (dari CD/situs pembuatnya, mis. POS-58 / XP-58 / POS-80)
   sampai printer muncul di **Pengaturan Windows › Bluetooth & perangkat › Printer**.
2. Kasir PC: **Pengaturan › Pilih printer** → pilih printernya → pilih **58mm / 80mm** → **Cetak tes**.
3. **Cara cetak "Langsung"** = struk keluar tanpa jendela cetak. Kalau struk terpotong / sangat kecil,
   pilih **"Tampilkan dialog"** lalu atur ukuran kertas di jendela cetak Windows.
4. Isi struk (nama toko, alamat, pesan bawah) di **Pengaturan › Isi struk**.

### 6. Sambungan & sinkron

Badge **Online / Lambat / Offline / Ditolak** dan strip "⏳ N belum terkirim" sama artinya dengan
Kasir HP (lihat `mobile/kasir-hp/README.md` bagian 5). **Pengaturan › Tes koneksi** menampilkan
alamat server, kode perangkat, jam server, dan pesan bila gagal.

Penting:
- Sinkron otomatis berjalan **selama Kasir PC terbuka** (cek tiap 15 detik). Saat menutup aplikasi
  dengan transaksi belum terkirim, muncul peringatan — boleh ditutup, data tetap aman, tapi buka lagi
  saat ada internet. Server menolak transaksi yang lebih lama dari **7 hari**.
- Jam & tanggal Windows harus otomatis (aplikasi memberi peringatan bila selisih > 5 menit).
- Semua data ada di folder data (lihat **Pengaturan › Perangkat & data › Buka folder data**,
  biasanya `%APPDATA%\Kasir PC`). Uninstall **tidak** menghapus folder itu. Jangan hapus folder itu
  sebelum semua transaksi terkirim.
- Hanya satu jendela Kasir PC per komputer (membuka dua kali hanya memunculkan jendela yang sama).

---

## B. Untuk pengembang

### Berbagi kode dengan Kasir HP

Kasir PC **tidak menyalin** kode Kasir HP; ia mengimpor langsung dari `mobile/kasir-hp/src`
(alias `@hp`): database lokal (`db.js`), sinkron (`sync.js`, `status.js`), API (`api.js`),
katalog & keranjang, `saveSale`, layar PIN/kasir, unduh data, riwayat. Perbaikan di modul itu
otomatis ikut ke Kasir PC saat dibuild ulang.

Yang khusus PC ada di `desktop/kasir-pc`:

| Bagian | File |
|---|---|
| Jembatan Electron (HTTP tanpa CORS, token terenkripsi DPAPI, printer Windows, cetak HTML) | `electron/main.js`, `electron/preload.js` |
| Pengganti modul Android | `src/platform/native.js` (ganti `native.js` HP), `capacitor-core.js`, `capacitor-app.js`, `no-camera.js` |
| Layar khusus PC | `src/views/kasir.js` (kasir+bayar satu layar), `pengaturan.js`, `pair.js` |
| Struk HTML (58/80mm) | `src/receipt-html.js`, `src/sales.js` (ganti cara cetak, `saveSale` tetap dari HP) |
| Keyboard untuk layar HP (PIN, Esc, Enter) | `src/main.js` |
| Aturan berbagi kode | `vite.config.mjs`: `OVERRIDES` (file HP yang diganti) dan `TEXTS` (teks "HP" → "komputer"; build memberi peringatan bila teks di Kasir HP berubah) |

Jangan ubah perilaku modul Kasir HP hanya untuk PC; tambahkan pengganti di `OVERRIDES`.

### Perintah

```powershell
cd desktop\kasir-pc
npm install
npm start          # build tampilan lalu buka di Electron
npm run dist       # installer: release\KasirPC-Setup-<versi>.exe
npm run icon       # buat ulang build\icon.png
```

Versi baru: naikkan `version` di `package.json` (tampil di Pengaturan dan nama installer).

Mode browser tanpa Electron (untuk mengubah tampilan): `npm run dev` lalu buka
`http://localhost:5180`, alamat server `localhost:5180` (Vite meneruskan `/api` ke `KASIR_API`,
bawaan `http://127.0.0.1:8084` = konfigurasi `kuncen-php84` di `.claude/launch.json`).
Printer diganti contoh; "cetak" membuka jendela berisi struk.

Uji cepat Electron (profil data terpisah, tidak mengganggu data asli):

```powershell
npm run build
npx electron scripts/smoke-test.js http://127.0.0.1:8084
# hasil: release\smoke\hasil.json + layar.png
```

### Catatan

- Belum diuji dengan printer thermal fisik — lakukan **Cetak tes** dulu di toko.
- Installer tidak ditandatangani (SmartScreen memperingatkan sekali). Sertifikat code signing berbayar.
- Electron 45 butuh Windows 10/11 64-bit. Bila toko masih memakai Windows 7, perlu dibuild ulang
  dengan Electron 22 (versi terakhir untuk Windows 7) — belum dicoba.
- Data tampilan disimpan di IndexedDB origin `app://kasir` dalam folder data Electron; token
  perangkat di `secrets.json` (terenkripsi Windows DPAPI, hanya bisa dibuka akun Windows yang sama).
