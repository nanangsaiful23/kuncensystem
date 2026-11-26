-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_26_000002_create_stock_minus_logs_table.php
--
-- Mencatat akun yang membuat stok barang jadi minus (stock_minus_logs) dan
-- siapa yang mengedit/menghapus loading atau mengedit/membatalkan transaksi
-- (action_logs).
--
-- Cara termudah: buka dashboard admin; tabelnya dibuat otomatis. File ini
-- hanya cadangan kalau pembuatan otomatis gagal. Jalankan SEKALI di
-- phpMyAdmin (tab SQL) pada database toko. Aman kalau terjalankan dua kali.
--
-- Hanya menambah dua tabel baru. Data lama tidak diubah.

CREATE TABLE IF NOT EXISTS `stock_minus_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `good_id` bigint unsigned NOT NULL,
  `stock` decimal(15,2) NOT NULL DEFAULT 0,
  `action` varchar(30) NOT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `good_loading_id` bigint unsigned DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(100) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_minus_logs_good_id_ended_at_index` (`good_id`, `ended_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Barang yang sekarang sudah minus: penyebabnya tidak diketahui.
INSERT INTO `stock_minus_logs` (`good_id`, `stock`, `action`, `created_at`, `updated_at`)
SELECT g.`id`, g.`last_stock`, 'sebelum_pencatatan', NOW(), NOW()
FROM `goods` g
WHERE g.`deleted_at` IS NULL AND g.`last_stock` < 0
  AND NOT EXISTS (SELECT 1 FROM `stock_minus_logs` s WHERE s.`good_id` = g.`id` AND s.`ended_at` IS NULL);

CREATE TABLE IF NOT EXISTS `action_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(30) NOT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `good_loading_id` bigint unsigned DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_logs_transaction_id_index` (`transaction_id`),
  KEY `action_logs_good_loading_id_index` (`good_loading_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_26_000002_create_stock_minus_logs_table' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   DROP TABLE `stock_minus_logs`;
--   DROP TABLE `action_logs`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_26_000002_create_stock_minus_logs_table';
