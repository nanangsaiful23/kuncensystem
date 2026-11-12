-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_12_000001_add_loss_reason_to_transactions_table.php
--
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
-- Menambah kolom "penyebab barang keluar" (hilang / expired / rusak) untuk
-- transaksi internal 5215 - Biaya Penyusutan Barang. Data lama tidak diubah
-- (kolom berisi NULL dan dibaca sebagai "hilang").
--
-- Jika muncul error "Duplicate column name 'loss_reason'", kolomnya sudah
-- ada: lewati query 1 dan jalankan query 2 saja.

-- 1. Tambah kolom
ALTER TABLE `transactions`
    ADD COLUMN `loss_reason` VARCHAR(20) NULL DEFAULT NULL AFTER `type`;

-- 2. Catat migrasi sebagai sudah dijalankan, supaya `php artisan migrate`
--    di kemudian hari tidak mencoba menjalankannya lagi.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_12_000001_add_loss_reason_to_transactions_table' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   ALTER TABLE `transactions` DROP COLUMN `loss_reason`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_12_000001_add_loss_reason_to_transactions_table';
