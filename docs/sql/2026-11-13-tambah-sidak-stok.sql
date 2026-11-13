-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_13_000002_create_stock_inspections_table.php
--
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
-- Menambah 2 tabel untuk fitur Sidak Stok (cek stok acak oleh supervisor
-- lewat HP). Tidak ada data lama yang diubah.

-- 1. Tabel sesi sidak
CREATE TABLE IF NOT EXISTS `stock_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(20) DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_si_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel barang yang dicek
CREATE TABLE IF NOT EXISTS `stock_inspection_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_inspection_id` bigint(20) unsigned NOT NULL,
  `good_id` bigint(20) unsigned NOT NULL,
  `good_unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit_name` varchar(50) DEFAULT NULL,
  `system_stock` decimal(14,4) NOT NULL DEFAULT 0,
  `real_stock` decimal(14,4) NOT NULL DEFAULT 0,
  `difference` decimal(14,4) NOT NULL DEFAULT 0,
  `buy_price` decimal(14,2) NOT NULL DEFAULT 0,
  `source` varchar(10) NOT NULL DEFAULT 'scan',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sid_inspection_good` (`stock_inspection_id`, `good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Catat migrasi sebagai sudah dijalankan, supaya `php artisan migrate`
--    di kemudian hari tidak mencoba menjalankannya lagi.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_13_000002_create_stock_inspections_table' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   DROP TABLE `stock_inspection_details`;
--   DROP TABLE `stock_inspections`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_13_000002_create_stock_inspections_table';
