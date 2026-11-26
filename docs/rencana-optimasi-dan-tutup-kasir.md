# Rencana Perbaikan Sistem Kasir NTN

Status (26 Nov 2026): **poin 4 dan poin 3 sudah dibuat** (lihat "Yang sudah
dibuat" di masing-masing poin), belum dipasang di produksi. Poin 1–2 masih rencana.

Ringkasan keputusan:

- Tidak bergantung pada cron. Server produksi tidak bisa menjalankan terminal,
  jadi rekap dan pengecekan dipicu dari aksi admin (Tutup Kasir dan membuka
  dashboard).
- Cek kecocokan berjalan bersama tombol **Tutup Kasir**, dan hasilnya tampil
  sebagai notifikasi di dashboard admin.
- Selisih kas saat tutup kasir dibuatkan **jurnal penyesuaian otomatis**,
  supaya saldo Kas di Tangan di sistem selalu sama dengan uang fisik.
- Perubahan tabel dibuat sebagai file SQL di `docs/sql` untuk dijalankan di
  phpMyAdmin.

Urutan kerja yang disarankan: **4 → 3 → 2 → 1**. Tutup kasir menjadi pemicu
cek kecocokan dan pengisian rekap. Tabel rekap menjadi dasar untuk
mempercepat sebagian halaman lambat.

---

## 1. Mempercepat halaman yang lambat

Diukur 24 Nov 2026 sekitar pukul 05.30 WIB. Targetnya setiap halaman terbuka
di bawah 3 detik.

| No | Halaman | Waktu sekarang | Alamat |
|---|---|---|---|
| 1 | Resume Transaksi per Barang | 43,8 dtk (1 bulan), 7,9 dtk (1 hari) | `/admin/transaction/resume/normal/all/all/{dari}/{sampai}/100` |
| 2 | Rekomendasi Reorder | 18,4 dtk (HTML 1,9 MB, 737 baris) | `/admin/reports/reorder` |
| 3 | Grafik Penjualan per Kategori | 11,8 dtk (periode sejak 2020) | `/admin/salesGraph/category/{dari}/{sampai}` |
| 4 | Daftar Transaksi | 8,0 dtk (1 bulan) | `/admin/transaction/all/all/{dari}/{sampai}/20` |
| 5 | Perputaran Barang | 6,7 dtk (HTML 10 MB, 3.926 baris) | `/admin/reports/movement` |
| 6 | Laporan Penjualan | 6,5 dtk | `/admin/reports/sales` |
| 7 | Dashboard Admin | 4,8 dtk (buka pertama 9,4 dtk) | `/admin` |
| 8 | Resume Transaksi Total | 4,7 dtk (1 bulan) | `/admin/transaction/resumeTotal/{dari}/{sampai}` |
| 9 | Ringkasan Pemilik | 3,2 dtk | `/admin/reports/owner` |
| 10 | Cari Barang (dipakai kasir terus-menerus) | 2,5–3,4 dtk | `/search/{kata}` |

Cara memperbaikinya:

- Total dihitung langsung di database (query agregat), jadi tidak semua data
  ditarik dulu lalu dijumlah di PHP.
- Kolom yang sering dicari atau difilter diberi indeks.
- Data dipecah per halaman (paginasi), terutama untuk halaman yang HTML-nya
  besar (no. 2 dan 5).
- Halaman laporan untuk periode panjang (misalnya no. 1, 3, dan 8) membaca
  tabel rekap dari poin 2 untuk tanggal yang sudah lewat. Hanya hari ini yang
  dihitung langsung.
- **Cari Barang (no. 10) didahulukan.** Halaman ini paling sering dipakai
  kasir, jadi perbaikan kecil di sini paling terasa.

Setelah diperbaiki, setiap halaman diukur ulang dengan periode yang sama
seperti tabel di atas.

## 2. Tabel ringkasan harian (tanpa cron)

Isi: satu baris per barang per hari, hanya untuk barang yang ada transaksi
atau mutasi pada hari itu. Kolomnya:

- jumlah terjual
- nilai penjualan
- HPP
- barang masuk
- barang keluar

Kapan rekap diisi, tanpa scheduler:

- Rekap suatu tanggal dibuat **setelah tanggal itu lewat**. Hari ini selalu
  dihitung langsung karena datanya cuma satu hari dan ringan.
- Pemicu otomatis:
  1. saat **Tutup Kasir** diklik,
  2. saat admin **membuka dashboard**, sebagai cadangan kalau kemarin lupa
     tutup kasir.

  Setiap kali terpicu, sistem mengisi semua tanggal lampau yang belum punya
  rekap. Biasanya hanya satu hari, jadi cepat.
- Tanggal lampau yang datanya berubah ditandai **"perlu dihitung ulang"** dan
  dihitung ulang pada pemicu berikutnya. Contohnya:
  - transaksi diedit atau dihapus admin di web,
  - ada retur,
  - transaksi Kasir HP/PC baru masuk setelah sempat offline.
- Ada tombol manual **Hitung Ulang Ringkasan** per rentang tanggal. Tombol ini
  dipakai sekali di awal untuk mengisi data lama sejak 2020, dicicil per bulan
  supaya tidak timeout di hosting, dan untuk jaga-jaga kalau angka terlihat
  janggal.
- Kalau suatu saat hosting bisa cron, cron cukup memanggil proses yang sama.
  Ini tidak wajib.

## 3. Cek kecocokan harian

Pengecekan berjalan otomatis setiap kali **Tutup Kasir** diklik. Yang dicek:

| Cek | Dianggap tidak cocok kalau |
|---|---|
| Kas | Uang fisik ≠ saldo Kas di Tangan (1111) sebelum penyesuaian |
| Penjualan | Total transaksi hari itu ≠ total jurnal Penjualan (4101) hari itu |
| HPP | Total HPP transaksi hari itu ≠ total jurnal HPP (5101) hari itu |
| Stok | Ada barang dengan stok minus, atau stok barang yang bergerak hari itu tidak sama dengan hitungan dari mutasinya |
| Kasir HP/PC | Ada transaksi yang ditolak saat sinkron dan belum diselesaikan (`pos_sync_rejects`) |

Notifikasi di **dashboard admin**:

- Hasil yang tidak cocok tampil sebagai kotak peringatan di dashboard, berisi
  daftar masalah dan tautan ke halaman terkait.
- Peringatan tetap tampil sampai admin klik **Sudah dicek**. Nama admin dan
  waktunya ikut dicatat.
- Peringatan "KEMAREN BELUM PENARIKAN UANG" yang sudah ada diubah menjadi
  "Kemarin belum tutup kasir" dan masuk ke kotak yang sama.
- Kalau semuanya cocok, dashboard cukup menampilkan satu baris hijau
  "Tutup kasir {tanggal}: semua cocok".

### Yang sudah dibuat (26 Nov 2026)

- Cek berjalan otomatis setelah tutup kasir tersimpan (admin maupun kasir),
  di request yang sama. Pesan setelah simpan menyebut hasilnya; kasir hanya
  melihat ringkasan ("sudah dilaporkan ke admin"), admin melihat rinciannya di
  kotak Tutup Kasir dan di dashboard.
- Hasil disimpan di tabel `reconciliation_checks` (satu baris per tutup kasir,
  semua cek beserta rinciannya dalam JSON). Batal tutup kasir ikut menghapus
  hasil ceknya; tutup ulang menjalankan cek baru.
- Yang dicek untuk tanggal tutup kasir:
  - **Kas**: selisih uang fisik vs saldo Kas di Tangan, plus selisih per laci
    kalau perkiraannya diketahui.
  - **Penjualan** dan **HPP**: aturan sama dengan `php artisan kuncen:audit`
    (penjualan tunai per hari, penjualan transfer per transaksi, transaksi tanpa
    detail, HPP per hari, retur tanpa jurnal HPP, batal transaksi tanpa jurnal
    HPP balik). Perintah itu sekarang memakai kode yang sama
    (`CekKecocokan::selisihJurnal()`), dan cek stoknya jauh lebih cepat
    (42 dtk → 8 dtk di lokal).
  - **Stok**: hanya barang yang punya transaksi/loading yang dibuat, diubah,
    atau dihapus hari itu. Tidak cocok kalau stok tercatat beda dengan hitungan
    dari riwayat loading & transaksi, atau stoknya minus. Stok minus barang
    yang tidak bergerak hari itu tidak dilaporkan setiap hari (di lokal ada 51
    barang seperti itu), supaya peringatannya tidak selalu muncul.
  - **Kasir HP/PC**: transaksi yang ditolak saat sinkron dan belum ditandai
    selesai (semua tanggal).
- Dashboard admin: kotak **Cek Kecocokan** menggantikan "KEMAREN BELUM
  PENARIKAN UANG". Isinya "Kemarin belum tutup kasir" (dengan tautan), lalu
  setiap hasil yang tidak cocok dan belum ditandai, dengan rincian dan tautan
  ke jurnal, transaksi, barang, atau Kasir HP. Tombolnya:
  - **Sudah dibaca** / **Sudah ditangani** (catatan boleh diisi): nama admin,
    waktu, dan catatannya disimpan, lalu hasil itu hilang dari dashboard.
  - **Cek ulang**: jalankan lagi ceknya setelah data diperbaiki. Kalau semua
    sudah cocok, otomatis ditandai sudah ditangani. Selisih kas tidak hilang
    dengan cek ulang (sudah dijurnal), jadi tetap perlu ditandai.
  - **Samakan stok N barang**: menyamakan stok tercatat dengan riwayatnya
    (pengganti `php artisan stock:sync` untuk barang itu saja, karena server
    produksi tidak punya terminal), lalu cek ulang.
- Hak akses tombol: `kas.cek_kecocokan` di Hak Akses Menu (bawaan semua role).
- Lama cek di lokal 1–4 detik per tutup kasir. Tabel transaksi dibaca sekali
  saja karena tidak punya indeks tanggal.
- Daftar ID di query dipecah per 500: MariaDB mengubah `IN (...)` berisi 1000
  nilai atau lebih menjadi subquery, dan di server lokal (10.4) hasilnya malah
  kosong.
- Logika di `app/Services/CekKecocokan.php`. Tabel `reconciliation_checks`
  dibuat otomatis saat dashboard admin atau Resume Transaksi Total dibuka;
  cadangannya `docs/sql/2026-11-26-cek-kecocokan.sql`.

## 4. Tutup kasir harian

Fitur **Penarikan Uang** yang sekarang disempurnakan menjadi **Tutup Kasir**.

Yang sudah berjalan sekarang:

- **Kas di Tangan (1111)** adalah uang yang benar-benar ada di laci kasir.
- **Kas di Nanang (1113)** adalah uang yang sudah diserahkan ke owner.
- Penarikan membuat jurnal `cash_draw`: debit 1113, kredit 1111. Riwayatnya
  ada di menu "Riwayat Kas di Tangan".

Alur Tutup Kasir yang baru:

1. **Cek sinkron Kasir HP/PC.** Layar menampilkan waktu sinkron terakhir tiap
   perangkat (`pos_devices.last_sync_at`). Perangkat yang belum sinkron sejak
   toko tutup diberi peringatan. Kalau ada transaksi yang belum masuk ke
   server, saldo sistem akan lebih kecil dari uang fisik dan selisihnya jadi
   palsu.
2. Sistem menampilkan **saldo Kas di Tangan menurut sistem**.
3. Admin mengisi **jumlah uang fisik** hasil hitungan di laci.
4. Sistem menampilkan **selisihnya** (pas, kurang, atau lebih). Kalau tidak
   pas, sistem otomatis membuat **jurnal penyesuaian**:
   - Uang **kurang**: debit **5219 Biaya Uang Hilang**, kredit 1111 Kas di
     Tangan.
   - Uang **lebih**: debit 1111 Kas di Tangan, kredit **6101 Pendapatan
     Lain-lain**.
   - Jenis jurnal baru: `cash_adjustment` ("Selisih kas"), ditambahkan ke
     daftar jenis jurnal.

   Tujuannya supaya selisih tidak menumpuk. Tanpa penyesuaian, selisih kemarin
   akan terbawa ke perbandingan hari ini, sehingga cek kas tidak berguna lagi.
5. Admin mengisi **jumlah yang diserahkan ke owner**. Jurnal penarikan dibuat
   seperti sekarang (1111 → 1113). Sisanya tetap di laci.
6. Hasil tutup kasir disimpan di tabel riwayat tutup kasir:
   - tanggal
   - saldo sistem
   - uang fisik
   - selisih
   - jumlah yang ditarik
   - admin yang menutup
   - waktu
7. Sistem langsung menjalankan **cek kecocokan** (poin 3) dan **mengisi
   rekap** tanggal yang belum ada (poin 2).

Catatan:

- Pastikan akun 5219 dan 6101 memang ada di database produksi. Isi seeder
  belum tentu sama dengan produksi.
- Satu tanggal hanya boleh ditutup satu kali. Kalau perlu koreksi, admin
  membatalkan tutup kasir tanggal itu (jurnalnya ikut dihapus), lalu menutup
  ulang.

### Yang sudah dibuat (24–26 Nov 2026)

- Tutup kasir ada di halaman **Transaksi Barang › Resume Transaksi Total**,
  di tempat form Penarikan Uang lama, karena kasir membuat pembukuan di sana.
  Berlaku untuk admin dan kasir. Tidak ada menu terpisah.
- **Tanggal halaman = tanggal tutup kasir.** Pilih tanggal awal dan akhir
  yang sama. Kalau kemarin lupa input, buka Resume Total tanggal kemarin.
  Saldo sistem dihitung sampai akhir tanggal itu, dan jurnalnya memakai
  tanggal itu. Kalau periodenya beberapa hari, halaman menampilkan daftar
  tutup kasir di periode itu.
- **Dua kasir, dua laci, satu Kas di Tangan.** Uang fisik diisi per laci
  (satu baris per kasir/admin yang ada uang cash masuk/keluar di tanggal itu,
  bisa tambah laci). Tidak ada akun kas per kasir. Panduan per laci = penjualan
  cash + Transaksi Lain cash (plastik, kardus, dll.) - Biaya Lain dari kas
  (pengamen, dll.) yang diinput akun itu; rinciannya disimpan (kolom
  `drawers`). Selisih resmi tetap dihitung dari total semua laci, karena modal
  kembalian dan penarikan tengah hari tidak dicatat per laci.
- **Sisa kemarin & penarikan per laci.** Kasir tetap di lacinya, jadi laci =
  akun kasir. Tiap laci tampil sebagai kartu: sisa kemarin (uang fisik -
  ditarik pada tutup kasir sebelumnya), uang masuk hari ini, perkiraan isi
  laci, uang fisik, selisih laci (panduan), ditarik ke owner, dan sisa di
  laci. Laci yang kemarin masih ada sisanya tetap muncul walaupun hari ini
  tidak ada transaksi. Penarikan per laci dijumlah menjadi **satu jurnal**
  `cash_draw` Kas di Tangan -> Kas di Nanang. Sisa kemarin baru diketahui
  setelah ada satu tutup kasir dengan penarikan per laci.
- Kasir hanya mengisi uang fisik: nama laci diambil dari sistem (tidak bisa
  diubah), tiap laci wajib diisi (0 kalau kosong), dan kasir tidak bisa
  menambah/menghapus laci (dicek juga di server). Hanya admin yang bisa
  menambah laci tambahan. Daftar Kasir HP/PC ada di bawah form dan tertutup
  (bisa dibuka); kalau ada yang tidak terhubung, peringatan singkat tampil di
  atas form.
- Supaya Transaksi Lain & Biaya Lain bisa dibagi per laci, setiap jurnal baru
  mencatat akun penginputnya (`journals.created_by_role`, `created_by_id`,
  diisi otomatis oleh model `Journal`). Jurnal lama kosong; jumlahnya
  ditampilkan terpisah sebagai "tidak tercatat kasirnya".
- **Berurutan:** satu tutup kasir per tanggal, tidak boleh tanggal besok, dan
  tanggal lampau hanya bisa diisi kalau sesudahnya belum ada tutup kasir.
  Kalau tidak, selisih tutup kasir sesudahnya dihitung dari saldo yang sudah
  berubah.
- Hanya **tutup kasir terakhir** yang bisa dibatalkan, oleh role yang punya
  hak `aksi.tutup_kasir.batal` (bawaan: superadmin). Jurnalnya ikut dihapus.
- Hak akses simpan: `kas.tutup` (bawaan semua role) di Hak Akses Menu.
- Logika di `app/Services/TutupKasir.php`. Tabel `cash_closings` dibuat
  otomatis saat Resume Transaksi Total dibuka; cadangannya
  `docs/sql/2026-11-24-tutup-kasir.sql`.
- Langkah 1–6 dari alur di atas, dan cek kecocokan dari langkah 7 (poin 3).
  Pengisian rekap (langkah 7, poin 2) menyusul.
- Simpan ditolak kalau saldo Kas di Tangan berubah sejak halaman dibuka (ada
  transaksi baru), supaya selisih tidak dihitung dari angka yang sudah basi.
- Peringatan "KEMAREN BELUM PENARIKAN UANG" di dashboard admin hilang kalau
  kemarin sudah tutup kasir (walaupun tanpa uang yang diserahkan), dan punya
  tautan ke Resume Total tanggal kemarin.
- Perbaikan kecil: ganti tanggal di Resume Total untuk kasir dulu membuka
  alamat `/admin/...`; sekarang tetap di `/cashier/...`.
