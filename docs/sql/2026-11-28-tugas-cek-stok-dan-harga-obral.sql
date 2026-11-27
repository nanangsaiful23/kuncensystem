-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_28_000001_add_sidak_targets_and_clearance_price.php
--
-- Tugas cek stok (Sidak berisi daftar barang) dan harga obral.
--
-- Cara termudah: buka menu Laporan > Perputaran Barang atau Sidak Stok Toko
-- sebagai admin; kolom & tabel dibuat otomatis. File ini hanya cadangan kalau
-- pembuatan otomatis gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada
-- database toko, SETELAH docs/sql/2026-11-13-tambah-sidak-stok.sql.
-- Kalau muncul "Duplicate column name", bagian itu sudah pernah dijalankan:
-- jalankan sisanya saja.
--
-- Semua penambahan. Data lama tidak diubah; kolom baru berisi NULL.

-- 1. Nama yang menghitung fisik (kalau bukan yang menginput)
ALTER TABLE `stock_inspections`
  ADD COLUMN `counted_by` varchar(100) DEFAULT NULL AFTER `note`;

-- 2. Daftar barang yang harus dicek pada satu sesi sidak
CREATE TABLE IF NOT EXISTS `stock_inspection_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_inspection_id` bigint unsigned NOT NULL,
  `good_id` bigint unsigned NOT NULL,
  `position` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sit_inspection_good` (`stock_inspection_id`, `good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Harga jual sebelum obral (harga coret & untuk mengembalikan harga)
ALTER TABLE `good_units`
  ADD COLUMN `clearance_normal_price` decimal(12,2) DEFAULT NULL AFTER `selling_price`;

-- 4. Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_28_000001_add_sidak_targets_and_clearance_price' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = '2026_11_28_000001_add_sidak_targets_and_clearance_price');

-- Membatalkan (hanya kalau benar-benar perlu; harga normal barang obral hilang):
--   ALTER TABLE `good_units` DROP COLUMN `clearance_normal_price`;
--   DROP TABLE `stock_inspection_targets`;
--   ALTER TABLE `stock_inspections` DROP COLUMN `counted_by`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_28_000001_add_sidak_targets_and_clearance_price';
