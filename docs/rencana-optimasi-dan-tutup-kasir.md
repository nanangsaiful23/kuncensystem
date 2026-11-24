# Rencana Perbaikan Sistem Kasir NTN

Status (24 Nov 2026): **poin 4 sudah dibuat** (lihat "Yang sudah dibuat" di poin 4),
belum dipasang di produksi. Poin 1–3 masih rencana.

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

### Yang sudah dibuat (24 Nov 2026)

- **Menu Utama › Tutup Kasir** untuk admin dan kasir (`/admin/cash-closing`,
  `/cashier/cash-closing`). Hak akses: `kas.tutup` (bawaan semua role) di
  Hak Akses Menu.
- Logika di `app/Services/TutupKasir.php`. Tabel `cash_closings` dibuat
  otomatis saat halaman dibuka; cadangannya `docs/sql/2026-11-24-tutup-kasir.sql`.
- Langkah 1–6 dari alur di atas. Langkah 7 (cek kecocokan dan isi rekap)
  menyusul bersama poin 3 dan poin 2.
- Simpan ditolak kalau saldo Kas di Tangan berubah sejak halaman dibuka (ada
  transaksi baru), supaya selisih tidak dihitung dari angka yang sudah basi.
- Hanya tutup kasir **hari ini** yang bisa dibatalkan, dan hanya oleh role
  yang punya hak `aksi.tutup_kasir.batal` (bawaan: superadmin).
- Form Penarikan Uang lama di Resume Transaksi Total tetap ada untuk
  penarikan di tengah hari. Halaman itu sekarang memberi tautan ke Tutup Kasir.
- Dashboard admin tidak lagi menampilkan "KEMAREN BELUM PENARIKAN UANG" kalau
  kemarin sudah tutup kasir, walaupun tanpa uang yang diserahkan.
