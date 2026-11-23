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

1. Jalankan `KasirPC-Setup-1.0.0.exe` → ikuti langkahnya. Muncul shortcut **Kasir PC** di desktop.
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

| Tombol | Fungsi |
|---|---|
| Scan barcode | Barang langsung masuk keranjang (scanner USB bekerja seperti keyboard + Enter) |
| `F2` | Ke kolom cari. Ketik nama/kode → `↑` `↓` pilih → `Enter` |
| `5*kode` / `5*nama` | Tambah dengan jumlah 5 (mis. scan sesudah mengetik `5*`) |
| `Alt+1`…`Alt+9` | Di daftar hasil: tambah dengan satuan ke-1…9 (PCS, PAK, DUS, …) |
| `↑` `↓` (kolom cari kosong) | Pilih baris keranjang |
| `+` / `−` (kolom cari kosong) | Tambah / kurangi jumlah baris terpilih |
| `F3` | Ubah jumlah baris terpilih (boleh desimal, mis. `0,5`) |
| `Del` (kolom cari kosong) | Hapus baris terpilih |
| `F4` | Pilih member (hanya dari daftar; tidak bisa menambah member baru) |
| `F6` | Potongan akhir |
| `F7` | Ganti Tunai / Transfer (transfer: centang "Transfer sudah masuk") |
| `F8` | Uang diterima → `Enter` = simpan |
| `F12` atau `Ctrl+Enter` | Simpan (& cetak bila printer dipilih) |
| `Enter` / `Esc` sesudah simpan | Transaksi baru |
| `F9` | Riwayat transaksi (cetak ulang struk, lihat yang belum terkirim/ditolak) |
| `Ctrl+L` | Ganti kasir / kunci |
| `Ctrl` `+` / `−` / `0` | Perbesar / perkecil / normal tampilan; `F11` layar penuh |

Satuan, jumlah, dan diskon per baris juga bisa diubah langsung di tabel keranjang dengan mouse.
Uang bayar harus ≥ total (tidak ada piutang). Kembalian ≥ Rp500.000 diminta konfirmasi, dan angka
uang yang sangat besar (barcode ter-scan di kolom uang) ditolak.

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
