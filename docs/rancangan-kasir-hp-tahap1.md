# Rancangan Teknis Tahap 1 — Server API untuk Kasir HP

Status: **tahap 1 sudah diimplementasikan** (23 Nov 2026), belum dipasang di produksi.
Lihat §9 untuk perbedaan implementasi dengan rancangan awal.

Tujuan tahap 1: menyiapkan server supaya APK kasir Android (local-first) bisa
(1) mengunduh data barang/harga/member dan (2) mengirim transaksi yang dibuat
di HP, termasuk transaksi yang dibuat saat internet mati, **tanpa mengubah
perilaku kasir desktop**.

Keputusan yang sudah diambil:

- APK hanya untuk transaksi penjualan. Tidak ada retur, voucher, loading, laporan.
- Pembayaran: `cash` dan `transfer` (transfer dicek manual oleh kasir).
- Member: hanya pilih member yang sudah ada (tidak bisa tambah member baru dari HP).
- Semua HP Android, printer thermal 58mm Bluetooth (urusan tahap APK).
- Server produksi tanpa terminal → perubahan tabel lewat file SQL di `docs/sql`
  yang dijalankan di phpMyAdmin.

---

## 1. Temuan dari kode yang ada (penting untuk rancangan)

Semua kasir desktop menyimpan lewat `storeTransactionBase()` di
`app/Http/Controllers/Base/TransactionControllerBase.php`. Fungsi ini sudah
menghitung detail, HPP, jurnal penjualan, jurnal HPP, piutang. API akan
memanggil fungsi yang sama. Ada 4 hal yang membuat transaksi **offline**
berbeda dari transaksi desktop:

| # | Temuan | Dampak untuk transaksi offline | Solusi di rancangan |
|---|---|---|---|
| 1 | Jurnal memakai `date('Y-m-d')` (tanggal saat disimpan) dan `created_at` = waktu simpan | Transaksi Sabtu yang baru tersinkron Senin akan tercatat sebagai penjualan Senin | Tambah parameter opsional tanggal jual (lihat §5). Desktop tidak mengirimnya → perilaku sama persis |
| 2 | Anti-dobel (`submit_token`) disimpan di **Cache 2 hari** | Kalau HP offline > 2 hari atau cache terhapus, kiriman ulang bisa jadi dobel | Kolom `client_uuid` UNIQUE di tabel `transactions` |
| 3 | Atomik (all-or-nothing) dijamin middleware `AtomicRequest` yang hanya terpasang di grup `web` | Route API tidak otomatis atomik | Setiap transaksi diproses di `DB::transaction()` sendiri |
| 4 | Kalau `money_paid < total`, sistem membuat **piutang** | Kasir HP tidak boleh tanpa sengaja membuat piutang | API menolak transaksi dengan bayar kurang |

Catatan lain:
- `selling_price` diambil dari harga yang dikirim (harga yang dibayar pelanggan),
  `buy_price` diambil dari server. Ini sudah tepat untuk offline.
- `store` saat ini di-hardcode `'ntn getasan'` → transaksi HP ikut sama.
- `type` transaksi penjualan biasa = `'normal'`.
- Tutup buku (`PeriodLock`) menolak jurnal di periode yang sudah ditutup. Transaksi
  offline yang tanggalnya masuk periode tertutup akan ditolak dan dilaporkan.

---

## 2. Perubahan database (SQL lewat phpMyAdmin)

File: `docs/sql/AAAA-MM-DD-kasir-hp-tahap1.sql` (dibuat saat implementasi).
Semua **penambahan**, tidak mengubah/menghapus kolom lama.

### 2.1 Tabel baru `pos_devices` — HP yang terdaftar

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | int PK | |
| name | varchar(100) | mis. "HP Kasir 1 (Samsung A05)" |
| code | varchar(10) UNIQUE | awalan nota, mis. `HP1` |
| token_hash | char(64) null | SHA-256 token perangkat; token asli hanya ada di HP |
| pair_code | varchar(10) null | kode pasang sekali pakai (dari halaman admin) |
| pair_expires_at | datetime null | kode pasang kedaluwarsa 15 menit |
| is_active | tinyint | 0 = akses dicabut (HP hilang/rusak) |
| last_seen_at | datetime null | terakhir menghubungi server |
| last_sync_at | datetime null | terakhir mengirim transaksi |
| created_by | int null | admin yang mendaftarkan |
| created_at, updated_at, deleted_at | | |

### 2.2 Tambahan kolom di `transactions`

| Kolom | Tipe | Keterangan |
|---|---|---|
| client_uuid | char(36) null UNIQUE | ID transaksi dari HP; kunci anti-dobel |
| pos_device_id | int null | HP asal transaksi; null = desktop |
| receipt_no | varchar(30) null | nomor nota HP, mis. `HP1-20261123-0042` |
| sold_at | datetime null | waktu transaksi di HP (bisa beda dengan waktu sinkron) |

Transaksi desktop lama dan baru tetap null di semua kolom ini.

### 2.3 Tabel baru `pos_sync_rejects` — transaksi HP yang ditolak

Supaya transaksi yang gagal tidak hilang dan bisa dicek admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | int PK | |
| pos_device_id | int | |
| client_uuid | char(36) | |
| receipt_no | varchar(30) | |
| payload | longtext | JSON transaksi asli dari HP |
| reason | text | alasan ditolak |
| resolved_at | datetime null | diisi saat admin menandai selesai |
| created_at, updated_at | | |

### 2.4 PIN kasir

~~Kolom `pos_pin_hash` di `cashiers`~~ — tidak jadi (lihat §9). PIN dibuat dan
disimpan (sebagai hash) di HP sendiri setelah login online berhasil; server
tidak menyimpan PIN.

---

## 3. Keamanan & alur pemasangan HP

1. Admin membuka halaman baru **Perangkat Kasir HP** → "Tambah perangkat" →
   isi nama, sistem membuat `code` (HP1, HP2, …) dan **kode pasang 6 digit**
   (berlaku 15 menit, juga tampil sebagai QR).
2. Di APK, kasir memasukkan alamat server + kode pasang → server memberi
   **token perangkat** (acak 40+ karakter), menyimpan hash-nya, lalu menghapus
   kode pasang.
3. Kasir login dengan **akun kasir yang sudah ada** (username/password, harus
   online) → membuat PIN 4–6 digit. Setelah itu buka APK cukup dengan PIN.
4. Setiap request API membawa header `Authorization: Bearer <token perangkat>`.
   Middleware baru `PosDevice` mengecek token & `is_active`.
5. HP hilang → admin klik **Nonaktifkan** → token langsung tidak berlaku.
   Transaksi yang belum tersinkron di HP itu memang tidak bisa diselamatkan.

Token perangkat hanya bisa memanggil endpoint di §4. Tidak ada endpoint lain
yang bisa diakses dengan token ini, jadi dari APK tidak ada jalan ke laporan,
stok opname, hapus data, dsb.

---

## 4. Endpoint API

Prefix: `/api/pos/v1`. Semua JSON. Rate limit wajar (mis. 60/menit/perangkat).

| Method | Path | Auth | Fungsi |
|---|---|---|---|
| POST | `/pair` | kode pasang | Tukar kode pasang → token perangkat |
| GET | `/ping` | token | Cek koneksi + jam server + status perangkat |
| POST | `/login` | token | Login akun kasir (username/password) → data kasir |
| GET | `/catalog?since=` | token | Data barang & satuan & harga (penuh atau perubahan sejak waktu tertentu) |
| GET | `/members?since=` | token | Data member (penuh atau perubahan) |
| POST | `/transactions` | token | Kirim 1–20 transaksi sekaligus |

### 4.1 `GET /catalog`

Tanpa `since` → semua data aktif. Dengan `since` → hanya yang berubah
(`updated_at > since`), termasuk yang dihapus/discontinued supaya HP bisa
menyembunyikannya.

```json
{
  "server_time": "2026-11-23T10:15:00+07:00",
  "full": false,
  "goods": [
    { "id": 812, "code": "8996001600146", "name": "Indomie Goreng",
      "is_active": true, "stock_estimate": 240,
      "units": [
        { "good_unit_id": 1203, "unit": "PCS", "quantity": 1,  "selling_price": 3500 },
        { "good_unit_id": 1204, "unit": "DUS", "quantity": 40, "selling_price": 132000 }
      ] }
  ]
}
```

- `buy_price` **tidak** dikirim ke HP (tidak perlu, dan rahasia toko).
- `stock_estimate` dari `goods.last_stock`, hanya untuk tampilan.
- HP menyimpan `server_time` sebagai `since` untuk tarikan berikutnya.
- Perubahan harga di `good_units` harus mengubah `updated_at` barang/satuan —
  dicek saat implementasi; kalau tidak, pakai `max(updated_at)` dari
  `goods` dan `good_units`.

### 4.2 `POST /transactions`

```json
{
  "transactions": [
    {
      "client_uuid": "5b0c1e7a-2f4d-4c55-9a3e-0b2d8e9f1a77",
      "receipt_no": "HP1-20261123-0042",
      "sold_at": "2026-11-23T09:41:12+07:00",
      "cashier_id": 3,
      "member_id": 57,
      "payment": "cash",
      "items": [
        { "good_unit_id": 1203, "quantity": 5, "price": 3500, "discount": 0 },
        { "good_unit_id": 2210, "quantity": 2, "price": 4000, "discount": 0 }
      ],
      "total_discount": 0,
      "total": 25500,
      "money_paid": 30000,
      "money_returned": 4500,
      "note": null
    }
  ]
}
```

Jawaban per transaksi (urutan sama):

```json
{
  "results": [
    { "client_uuid": "5b0c…", "status": "saved",     "transaction_id": 98213 },
    { "client_uuid": "9a1f…", "status": "duplicate", "transaction_id": 98190 },
    { "client_uuid": "c33d…", "status": "rejected",  "message": "Bayar kurang dari total" }
  ]
}
```

- `saved` / `duplicate` → HP menandai ✓ terkirim.
- `rejected` → HP menandai ✗ dengan pesan; server sudah menyimpannya di
  `pos_sync_rejects` untuk dicek admin. HP tidak mengirim ulang otomatis.
- Error jaringan / 5xx → HP mencoba lagi nanti (aman karena `client_uuid`).

---

## 5. Cara server memproses satu transaksi

Controller baru `Api\Pos\TransactionSyncController`. Untuk setiap transaksi:

1. **Cek dobel**: kalau `client_uuid` sudah ada di `transactions` → `duplicate`.
2. **Validasi**:
   - `cashier_id` ada dan aktif.
   - `member_id` kosong atau ada di tabel `members` (tidak ada pembuatan member baru;
     `member_name` tidak pernah diteruskan ke `storeTransactionBase`).
   - `payment` ∈ {cash, transfer}.
   - Semua `good_unit_id` ada, `quantity > 0`.
   - Hitung ulang: Σ(qty × price − discount) − total_discount = `total`
     (toleransi pembulatan Rp1). Beda → `rejected`.
   - `money_paid ≥ total` (tidak boleh jadi piutang).
   - `sold_at` tidak di masa depan (> 10 menit dari jam server) dan tidak lebih
     lama dari batas (mis. 7 hari). Di luar batas → `rejected`, admin yang
     memutuskan.
3. **Ubah ke bentuk form desktop** (`barcodes[]`, `quantities[]`, `prices[]`,
   `discounts[]`, `sums[]`, `total_sum_price`, `type = 'normal'`, dst.) lalu
   panggil `storeTransactionBase('cashier', cashier_id, $request, $soldAt)`
   di dalam `DB::transaction()`.
4. Isi `client_uuid`, `pos_device_id`, `receipt_no`, `sold_at` pada transaksi.
5. Kalau gagal di tengah (mis. periode tutup buku) → rollback transaksi itu
   saja, catat ke `pos_sync_rejects`, lanjut ke transaksi berikutnya.

Transaksi dalam satu kiriman diproses **berurutan sesuai `sold_at`**.

### 5.1 Satu-satunya perubahan pada kode lama

`storeTransactionBase()` mendapat parameter keempat **opsional**:

```php
public function storeTransactionBase($role, $role_id, Request $request, ?Carbon $soldAt = null)
```

- `$soldAt` null (semua pemanggil desktop) → pakai waktu sekarang, **perilaku
  sama persis seperti sekarang**.
- Dari API → semua `date('Y-m-d')` di fungsi itu memakai tanggal `$soldAt`,
  dan `created_at` transaksi = `$soldAt`, supaya laporan harian & jurnal masuk
  ke hari penjualan sebenarnya.

Perubahan ini mekanis (ganti `date('Y-m-d')` dengan satu variabel `$today`).
Akan diuji dengan membandingkan hasil simpan desktop sebelum & sesudah.

---

## 6. Halaman admin baru

1. **Perangkat Kasir HP**: daftar HP, tambah (kode pasang/QR), nonaktifkan,
   terakhir terlihat/sinkron, jumlah transaksi hari ini.
2. **Transaksi HP ditolak**: daftar dari `pos_sync_rejects`, detail isi
   transaksi, tombol "Tandai selesai" (admin input manual bila perlu).
3. Di daftar transaksi yang sudah ada: tampilkan `receipt_no` / asal perangkat
   bila ada (perubahan tampilan kecil, opsional).

Implementasi: keduanya jadi satu halaman **Pengaturan > Kasir HP**
(`/admin/kasir-hp`), khusus superadmin seperti Hak Akses Menu.

---

## 7. Urutan implementasi

1. File SQL tahap 1 (§2) → dijalankan di phpMyAdmin (lokal dulu, lalu produksi).
2. Model `PosDevice`, `PosSyncReject`; middleware `PosDevice`.
3. Parameter `$soldAt` di `storeTransactionBase()` + uji transaksi desktop tetap sama.
4. Endpoint `/pair`, `/ping`, `/login`.
5. Endpoint `/catalog`, `/members`.
6. Endpoint `/transactions` + uji: normal, dobel, bayar kurang, total tidak cocok,
   tanggal kemarin, barang tidak ada, kirim ulang setelah koneksi putus.
7. Halaman admin Perangkat Kasir HP & Transaksi HP ditolak.
8. Uji ujung-ke-ujung dengan halaman uji sederhana (sebelum APK ada).

Tahap 2 (terpisah): APK Android (Capacitor) local-first + cetak 58mm.

---

## 8. Keputusan (23 Nov 2026)

1. Transaksi offline diterima otomatis bila `sold_at` paling lama **7 hari**;
   lebih lama → `rejected` dan dicek admin.
2. **Diskon per item boleh** di HP (field `discount` per baris tetap ada).
3. **HP tidak bisa menambah member baru.** Kasir hanya memilih dari daftar member
   yang sudah ada (hasil unduhan `/members`), atau tanpa member. API mengabaikan /
   menolak `member_name`; `member_id` harus ada di tabel `members`.
4. Stok minus karena transaksi HP **tidak ditandai khusus**; tampil seperti biasa.

---

## 9. Catatan implementasi (beda dengan rancangan di atas)

- **PIN** tidak disimpan di server; tidak ada kolom `pos_pin_hash` dan endpoint `/pin`.
  Login kasir memakai **email + password** akun kasir (tabel `cashiers` tidak punya username).
- **Tanpa member** → disimpan sebagai member ID 1 "Non Member", sama dengan form kasir desktop.
- Status jawaban keempat: **`error`** = gangguan server (bukan kesalahan data). Tidak dicatat
  di `pos_sync_rejects`; HP boleh mengirim ulang nanti.
- Transaksi yang ditolak lalu dikirim ulang dengan `client_uuid` sama → tetap `rejected`
  (tidak dicatat dua kali) selama admin belum menandainya selesai.
- Tabel dibuat **otomatis** saat superadmin membuka halaman Kasir HP. File
  `docs/sql/2026-11-23-kasir-hp-tahap1.sql` hanya cadangan kalau pembuatan otomatis gagal.
- Kode QR di halaman admin berisi `{"server": "<alamat>", "pair_code": "<6 angka>"}`.
- Endpoint `/pair` dan `/login` dibatasi 10 request/menit; endpoint lain mengikuti batas
  grup `api` (60/menit).

File utama:

| Bagian | File |
|---|---|
| Migrasi / SQL | `database/migrations/2026_11_23_000001_create_kasir_hp_tables.php`, `docs/sql/2026-11-23-kasir-hp-tahap1.sql` |
| Model | `app/Models/PosDevice.php`, `app/Models/PosSyncReject.php` |
| Middleware | `app/Http/Middleware/PosDeviceAuth.php` (alias `pos.device`) |
| API | `app/Http/Controllers/Api/Pos/*`, `routes/api.php` |
| Perubahan kode lama | `storeTransactionBase()` parameter opsional `$soldAt` |
| Halaman admin | `app/Http/Controllers/Admin/KasirHpController.php`, `resources/views/admin/kasir-hp.blade.php` |
| Tes | `tests/Feature/Stock/KasirHpApiTest.php` |
