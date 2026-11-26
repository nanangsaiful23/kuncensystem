-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_26_000001_create_reconciliation_checks_table.php
--
-- Cek kecocokan harian. Lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 3.
--
-- Cara termudah: buka dashboard admin atau halaman Resume Transaksi Total;
-- tabelnya dibuat otomatis. File ini hanya cadangan kalau pembuatan otomatis
-- gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
--
-- Hanya menambah satu tabel baru. Data lama tidak diubah.

CREATE TABLE IF NOT EXISTS `reconciliation_checks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `check_date` date NOT NULL,
  `cash_closing_id` int unsigned DEFAULT NULL,
  `problem_count` smallint unsigned NOT NULL DEFAULT 0,
  `results` mediumtext NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution` varchar(20) DEFAULT NULL,
  `resolved_by_role` varchar(20) DEFAULT NULL,
  `resolved_by_id` int unsigned DEFAULT NULL,
  `resolve_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_checks_check_date_index` (`check_date`),
  KEY `reconciliation_checks_cash_closing_id_index` (`cash_closing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_26_000001_create_reconciliation_checks_table' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   DROP TABLE `reconciliation_checks`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_26_000001_create_reconciliation_checks_table';
