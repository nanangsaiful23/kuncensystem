-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_24_000001_create_cash_closings_table.php
--
-- Tutup Kasir harian. Lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 4.
--
-- Cara termudah: buka halaman Resume Transaksi Total (admin atau kasir);
-- tabel & kolom dibuat otomatis. File ini hanya cadangan kalau pembuatan
-- otomatis gagal. Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
--
-- Menambah tabel cash_closings dan dua kolom kosong di journals (akun yang
-- menginput jurnal). Data lama tidak diubah.
-- Catatan: ALTER TABLE journals bisa makan waktu beberapa detik (tabelnya
-- besar). Jalankan saat toko sepi.

CREATE TABLE IF NOT EXISTS `cash_closings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `closing_date` date NOT NULL,
  `system_balance` decimal(14,2) NOT NULL,
  `physical_cash` decimal(14,2) NOT NULL,
  `drawers` text DEFAULT NULL,
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

-- Akun (admin/kasir) yang menginput jurnal; diisi otomatis untuk jurnal baru.
-- Kalau muncul error "Duplicate column name", kolomnya sudah ada: lewati baris ini.
ALTER TABLE `journals`
  ADD COLUMN `created_by_role` varchar(20) DEFAULT NULL,
  ADD COLUMN `created_by_id` int unsigned DEFAULT NULL;

-- Catat migrasinya supaya `php artisan migrate` tidak mencoba membuat ulang.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch` FROM (
    SELECT '2026_11_24_000001_create_cash_closings_table' AS `migration`, COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Kalau tabel cash_closings sudah pernah dibuat dengan versi awal file ini
-- (tanpa kolom drawers = rincian per laci), jalankan juga:
--   ALTER TABLE `cash_closings` ADD COLUMN `drawers` text DEFAULT NULL AFTER `physical_cash`;

-- Untuk membatalkan (jurnal selisih & penarikan yang sudah dibuat tetap ada):
--   ALTER TABLE `journals` DROP COLUMN `created_by_role`, DROP COLUMN `created_by_id`;
--   DROP TABLE `cash_closings`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_24_000001_create_cash_closings_table';
