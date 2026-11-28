-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_28_000002_add_cash_drawer_to_good_loadings.php
--
-- Laci asal uang loading barang yang dibayar dari 1111 Kas di Tangan, supaya
-- perkiraan isi laci di Tutup Kasir ikut berkurang.
--
-- Cara termudah: buka halaman Tambah Loading atau Resume Transaksi Total
-- sebagai admin; kolom dibuat otomatis. File ini hanya cadangan kalau
-- pembuatan otomatis gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada
-- database toko. Kalau muncul "Duplicate column name", kolomnya sudah ada.
--
-- Hanya penambahan kolom. Data lama tidak diubah; kolom baru berisi NULL
-- (tampil sebagai "belum dialokasikan ke laci" sampai lacinya dipilih di
-- halaman Ubah Loading).

ALTER TABLE `good_loadings`
  ADD COLUMN `cash_drawer` varchar(40) DEFAULT NULL AFTER `payment`;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_28_000002_add_cash_drawer_to_good_loadings' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = '2026_11_28_000002_add_cash_drawer_to_good_loadings');

-- Membatalkan (pilihan laci di loading hilang):
--   ALTER TABLE `good_loadings` DROP COLUMN `cash_drawer`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_28_000002_add_cash_drawer_to_good_loadings';
