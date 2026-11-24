-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_24_000001_create_cash_closings_table.php
--
-- Tutup Kasir harian. Lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 4.
--
-- Cara termudah: buka halaman Tutup Kasir (menu utama, admin atau kasir);
-- tabel dibuat otomatis. File ini hanya cadangan kalau pembuatan otomatis
-- gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
--
-- Hanya menambah tabel baru. Data lama tidak diubah.

CREATE TABLE IF NOT EXISTS `cash_closings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `closing_date` date NOT NULL,
  `system_balance` decimal(14,2) NOT NULL,
  `physical_cash` decimal(14,2) NOT NULL,
  `difference` decimal(14,2) NOT NULL,
  `draw_amount` decimal(14,2) NOT NULL DEFAULT 0,
  `adjustment_journal_id` bigint unsigned DEFAULT NULL,
  `draw_journal_id` bigint unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `role_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_closings_closing_date_index` (`closing_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_24_000001_create_cash_closings_table' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan (jurnal selisih & penarikan yang sudah dibuat tetap ada):
--   DROP TABLE `cash_closings`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_24_000001_create_cash_closings_table';
