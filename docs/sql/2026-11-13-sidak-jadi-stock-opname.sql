-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_13_000003_add_stock_opname_id_to_stock_inspections_table.php
--
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko, SETELAH
-- docs/sql/2026-11-13-tambah-sidak-stok.sql.
-- Menambah kolom penanda "sidak ini sudah dijadikan Stock Opname".
--
-- Jika muncul error "Duplicate column name 'stock_opname_id'", kolomnya sudah
-- ada: lewati query 1 dan jalankan query 2 saja.

-- 1. Tambah kolom
ALTER TABLE `stock_inspections`
    ADD COLUMN `stock_opname_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `finished_at`;

-- 2. Catat migrasi sebagai sudah dijalankan
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_13_000003_add_stock_opname_id_to_stock_inspections_table' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   ALTER TABLE `stock_inspections` DROP COLUMN `stock_opname_id`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_13_000003_add_stock_opname_id_to_stock_inspections_table';
