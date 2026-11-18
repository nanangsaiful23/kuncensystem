# Penataan Kategori — Panduan di Server Produksi (tanpa terminal)

Semua langkah lewat **phpMyAdmin** dan **file manager / FTP** hosting.
Kerjakan berurutan. Jangan lanjut ke langkah berikutnya kalau ada hasil cek yang tidak sesuai.

Perkiraan waktu: 20–30 menit, di luar waktu memeriksa CSV. Sebaiknya di luar jam ramai kasir.

---

## Langkah 0 — Backup

1. phpMyAdmin → pilih database toko → tab **Export** → *Quick* → format **SQL** → **Go**.
2. Simpan file hasil export di komputer, jangan hanya di server.

## Langkah 1 — Struktur (Tahap 1)

1. phpMyAdmin → tab **SQL** → tempel seluruh isi `docs/sql/2026-11-18-tahap1-struktur-kategori.sql` → **Go**.
2. Query cek di akhir file harus menghasilkan **0 baris**.

Aplikasi lama tetap berjalan normal setelah langkah ini.

## Langkah 2 — Area, kategori, dan jenis (Tahap 2)

1. Tab **SQL** → tempel seluruh isi `docs/sql/2026-11-18-tahap2-isi-kategori-jenis.sql` → **Go**.
2. Tempel `docs/sql/2026-11-18-tahap2-isi-kategori-jenis-cek.sql` → **Go**.
   - Query 1–4 harus **0 baris**.
   - Query 1 tidak kosong berarti ada jenis barang yang dibuat di produksi setelah salinan data lokal diambil. Catat id dan namanya, lalu kirim ke Claude untuk dipetakan. Jangan lanjut dulu.
   - Query 6 menunjukkan jumlah nama barang yang diberi awalan. Angkanya sekitar 685, dan boleh sedikit berbeda dari angka itu.

## Langkah 3 — 24 jenis tambahan (Tahap 3a)

1. Tab **SQL** → tempel `docs/sql/2026-11-18-tahap3a-jenis-tambahan.sql` → **Go**.
2. Query cek di akhir file harus menghasilkan **24**.

## Langkah 4 — Unggah kode

Unggah atau timpa file-file berikut ke folder aplikasi (folder yang berisi `app`, `routes`, `resources`):

```
app/Console/Commands/PetakanKategoriBarang.php                 (baru)
app/Http/Controllers/Admin/PenataanKategoriController.php      (baru)
app/Http/Controllers/Base/GoodControllerBase.php               (ubah: filter Area di Stok Habis)
app/Models/Category.php                                        (ubah)
app/Models/MainCategory.php                                    (ubah)
app/Models/Type.php                                            (ubah)
app/Services/PenataanKategori/PemetaanBarang.php               (baru, buat folder)
app/Services/PenataanKategori/aturan.php                       (baru)
resources/views/admin/penataan-kategori/index.blade.php        (baru, buat folder)
resources/views/layout/sidebar.blade.php                       (ubah: menu baru)
routes/admin.php                                               (ubah: alamat halaman baru)
```

File lain (migrasi, seeder, tes, `docs/`) tidak perlu diunggah ke server.

**Cek setelah unggah:**
- Login sebagai **supervisor**. Menu **Penataan Kategori** muncul di samping, di bawah Sidak Stok Toko.
- Buka halaman Stok Habis, lalu coba pilih filter kategori utama (sekarang berisi Area). Halaman harus tampil normal.
- Kalau menu ada tetapi halamannya **404**, server memakai route cache. Hapus file `bootstrap/cache/routes-v7.php` lewat file manager (namanya bisa sedikit berbeda, diawali `routes`), lalu buka lagi.
- Kalau muncul **error 500**, pastikan semua file di atas sudah terunggah lengkap. Nama folder huruf besar/kecil harus sama persis: `PenataanKategori`, `penataan-kategori`.

## Langkah 5 — Periksa dan terapkan pemetaan barang (Tahap 3)

1. Buka **Penataan Kategori** → klik **Usulan pemetaan** (CSV) dan **Tidak terpetakan** (CSV).
2. Buka di Excel. Untuk CSV usulan pemetaan:
   - baris diurutkan dari barang berstok dan Peralatan Rumah Tangga lebih dulu;
   - periksa kolom **jenis baru**, **tingkat keyakinan** (dahulukan yang "sedang"), dan **nama baru**;
   - kalau salah, ganti isi **jenis baru** dengan nama jenis yang benar (harus persis sama dengan nama jenis yang ada), atau hapus barisnya;
   - jangan ubah kolom `id`, `nama`, `kategori_lama_id`, dan `jenis_lama_id`.
3. Simpan sebagai **CSV**. Format "CSV (pemisah titik koma)" maupun "CSV (koma)" bisa dipakai.
4. Kembali ke halaman → **Terapkan CSV** → pilih file → centang "sudah export database" → **Terapkan**.
5. Baca hasilnya: jumlah barang dipindah, jumlah nama diubah, dan baris yang dilewati. Baris dilewati kalau barangnya diubah orang lain sejak CSV diunduh.
6. Untuk barang tidak terpetakan:
   - isi kolom **jenis baru** di CSV tidak terpetakan untuk barang yang sudah Anda putuskan, lalu unggah dengan cara yang sama;
   - baris yang kosong diabaikan;
   - boleh dicicil beberapa kali.

Boleh juga diterapkan bertahap, misalnya hanya baris barang berstok dulu: hapus baris lain di Excel sebelum mengunggah.

## Kalau perlu membatalkan

| Yang dibatalkan | Cara |
|---|---|
| Pemetaan barang (Tahap 3) | Halaman Penataan Kategori → **Pulihkan dari backup** → pilih backup `bak_tahap3_goods_…` yang dibuat sebelum penerapan yang ingin dibatalkan |
| 24 jenis tambahan | SQL `2026-11-18-tahap3a-jenis-tambahan-BATAL.sql`. Hanya menghapus jenis yang belum dipakai barang |
| Tahap 2 | SQL `2026-11-18-tahap2-isi-kategori-jenis-BATAL.sql`. **Hanya sebelum Tahap 3 diterapkan** |
| Tahap 1 | SQL `2026-11-18-tahap1-struktur-kategori-BATAL.sql`. Hanya sebelum Tahap 2. Kembalikan juga file kode lama |
| Semua | Import file export dari Langkah 0 |

## Catatan

- Harga, stok, transaksi, loading, dan jurnal tidak diubah di langkah mana pun.
- Total laporan lama tidak berubah. Pembagian per kategori pada laporan lama mengikuti kategori baru, sesuai keputusan pemilik.
- Tabel `bak_tahap2_*` dan `bak_tahap3_goods_*` adalah backup. Biarkan sampai semua tahap selesai dan sudah dicek. Setelah itu tabel-tabel ini boleh dihapus lewat phpMyAdmin.
- Halaman Penataan Kategori membuat tabel backup dengan `CREATE TABLE`. Kalau akun database hosting tidak punya izin itu, penerapan gagal **sebelum** ada data yang diubah, dan pesan errornya akan terlihat.
