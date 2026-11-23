-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_23_000001_create_kasir_hp_tables.php
--
-- Kasir HP (APK Android) tahap 1. Lihat docs/rancangan-kasir-hp-tahap1.md.
--
-- Cara termudah: buka halaman Pengaturan > Kasir HP sebagai superadmin;
-- tabel & kolom dibuat otomatis. File ini hanya cadangan kalau pembuatan
-- otomatis gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
--
-- Semua penambahan. Data lama tidak diubah; transaksi desktop tetap berisi
-- NULL di kolom baru.
-- Catatan: ALTER TABLE transactions bisa makan waktu beberapa detik
-- (tabelnya besar). Jalankan saat toko sepi.

CREATE TABLE IF NOT EXISTS `pos_devices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `token_hash` char(64) DEFAULT NULL,
  `pair_code` varchar(10) DEFAULT NULL,
  `pair_expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_devices_code_unique` (`code`),
  UNIQUE KEY `pos_devices_token_hash_unique` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_sync_rejects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pos_device_id` int unsigned NOT NULL,
  `client_uuid` char(36) DEFAULT NULL,
  `receipt_no` varchar(30) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `reason` text NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_rejects_client_uuid_index` (`client_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kalau muncul error "Duplicate column name 'client_uuid'", bagian ini
-- sudah pernah dijalankan; lewati saja.
ALTER TABLE `transactions`
  ADD COLUMN `client_uuid` char(36) DEFAULT NULL AFTER `id`,
  ADD COLUMN `pos_device_id` int unsigned DEFAULT NULL AFTER `client_uuid`,
  ADD COLUMN `receipt_no` varchar(30) DEFAULT NULL AFTER `pos_device_id`,
  ADD COLUMN `sold_at` datetime DEFAULT NULL AFTER `receipt_no`,
  ADD UNIQUE KEY `transactions_client_uuid_unique` (`client_uuid`);

-- Catat migrasi sebagai sudah dijalankan.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_23_000001_create_kasir_hp_tables' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   ALTER TABLE `transactions` DROP INDEX `transactions_client_uuid_unique`,
--     DROP COLUMN `client_uuid`, DROP COLUMN `pos_device_id`,
--     DROP COLUMN `receipt_no`, DROP COLUMN `sold_at`;
--   DROP TABLE `pos_sync_rejects`;
--   DROP TABLE `pos_devices`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_23_000001_create_kasir_hp_tables';
