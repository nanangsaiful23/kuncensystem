-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_27_000001_add_clearance_and_seasons.php
--
-- Obral & musim untuk menu Perputaran Barang dan Rekomendasi Reorder.
--
-- Cara termudah: buka menu Laporan > Perputaran Barang sebagai admin;
-- kolom & tabel dibuat otomatis. File ini hanya cadangan kalau pembuatan
-- otomatis gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
-- Kalau muncul "Duplicate column name", bagian itu sudah pernah dijalankan:
-- jalankan sisanya saja.
--
-- Semua penambahan. Data lama tidak diubah; kolom baru berisi 0 / NULL.
-- Catatan: ALTER TABLE goods bisa makan waktu beberapa detik. Jalankan saat
-- toko sepi.

ALTER TABLE `goods`
  ADD COLUMN `is_clearance` tinyint NOT NULL DEFAULT 0 AFTER `discontinued_by`,
  ADD COLUMN `clearance_at` timestamp NULL DEFAULT NULL AFTER `is_clearance`,
  ADD COLUMN `clearance_reason` text DEFAULT NULL AFTER `clearance_at`,
  ADD COLUMN `clearance_by` bigint unsigned DEFAULT NULL AFTER `clearance_reason`,
  ADD COLUMN `clearance_stock` decimal(12,2) DEFAULT NULL AFTER `clearance_by`,
  ADD COLUMN `season_id` bigint unsigned DEFAULT NULL AFTER `clearance_stock`,
  ADD INDEX `idx_goods_clearance` (`is_clearance`);

ALTER TABLE `types`
  ADD COLUMN `season_id` bigint unsigned DEFAULT NULL AFTER `max_model`;

CREATE TABLE IF NOT EXISTS `seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `repeats_yearly` tinyint NOT NULL DEFAULT 0,
  `prep_days` smallint unsigned NOT NULL DEFAULT 30,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `season_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_season_periods_season` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_27_000001_add_clearance_and_seasons' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (SELECT 1 FROM `migrations` m WHERE m.`migration` = '2026_11_27_000001_add_clearance_and_seasons');

-- Membatalkan (hanya kalau benar-benar perlu; data obral & musim hilang):
--   ALTER TABLE `goods` DROP INDEX `idx_goods_clearance`, DROP COLUMN `is_clearance`, DROP COLUMN `clearance_at`,
--     DROP COLUMN `clearance_reason`, DROP COLUMN `clearance_by`, DROP COLUMN `clearance_stock`, DROP COLUMN `season_id`;
--   ALTER TABLE `types` DROP COLUMN `season_id`;
--   DROP TABLE `season_periods`; DROP TABLE `seasons`;
