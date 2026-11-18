-- Pengganti:
--   php artisan migrate:rollback --path=database/migrations/2026_11_18_000001_add_area_and_model_control_to_categories_and_types.php
--
-- MEMBATALKAN Tahap 1 (docs/sql/2026-11-18-tahap1-struktur-kategori.sql).
-- Hanya dijalankan kalau Tahap 1 perlu dibatalkan, dan SEBELUM Tahap 2
-- dijalankan. Isi kolom Area, habis pakai, kategori induk jenis, dan kontrol
-- model akan hilang. Data barang tidak tersentuh.

ALTER TABLE `main_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE `types` DROP INDEX `idx_types_category`;
ALTER TABLE `types` DROP COLUMN `category_id`, DROP COLUMN `tipe_kontrol`, DROP COLUMN `min_model`, DROP COLUMN `max_model`;

ALTER TABLE `categories` DROP INDEX `idx_categories_main_category`;
ALTER TABLE `categories` DROP COLUMN `main_category_id`, DROP COLUMN `habis_pakai`;

DELETE FROM `migrations` WHERE `migration` = '2026_11_18_000001_add_area_and_model_control_to_categories_and_types';
