-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_18_000001_add_area_and_model_control_to_categories_and_types.php
--
-- TAHAP 1 penataan kategori: Area > Kategori > Jenis Barang.
--
-- SEBELUM MENJALANKAN: export dulu seluruh database lewat phpMyAdmin
-- (tab Export > Quick > SQL) dan simpan filenya.
--
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko. Tempel seluruh
-- isi file ini lalu klik Go. Tidak mengubah data barang, harga, stok,
-- transaksi, loading, maupun jurnal.
--
-- Isi perubahan:
--   - categories.main_category_id : Area kategori (tabel main_categories),
--                                   disalin dari tabel sub_categories.
--   - categories.habis_pakai      : 1 = habis pakai, 0 = alat (default 0).
--   - types.category_id           : kategori induk jenis barang (masih kosong).
--   - types.tipe_kontrol, min_model, max_model : kontrol jumlah model (kosong).
--   - collation types & main_categories disamakan ke utf8mb4_unicode_ci.
--
-- Jika muncul error "Duplicate column name", file ini sudah pernah dijalankan:
-- jangan dijalankan lagi.

-- 1. Kolom Area dan habis pakai di kategori
ALTER TABLE `categories` ADD `main_category_id` BIGINT NULL AFTER `id`;
ALTER TABLE `categories` ADD `habis_pakai` TINYINT NOT NULL DEFAULT '0' AFTER `color`;
ALTER TABLE `categories` ADD INDEX `idx_categories_main_category` (`main_category_id`);

-- 2. Salin Area dari tabel penghubung lama
UPDATE `categories` c
JOIN `sub_categories` s ON s.`category_id` = c.`id` AND s.`deleted_at` IS NULL
SET c.`main_category_id` = s.`main_category_id`;

-- 3. Kolom kategori induk dan kontrol model di jenis barang
ALTER TABLE `types` ADD `category_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `types` ADD `tipe_kontrol` ENUM('fungsional', 'pilihan_desain', 'musiman') NULL AFTER `eng_name`;
ALTER TABLE `types` ADD `min_model` SMALLINT UNSIGNED NULL AFTER `tipe_kontrol`;
ALTER TABLE `types` ADD `max_model` SMALLINT UNSIGNED NULL AFTER `min_model`;
ALTER TABLE `types` ADD INDEX `idx_types_category` (`category_id`);

-- 4. Samakan collation
ALTER TABLE `types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `main_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 5. Catat migrasi sebagai sudah dijalankan
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_18_000001_add_area_and_model_control_to_categories_and_types' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- 6. Cek hasil: harus 0 baris (area kategori sama dengan tabel lama)
SELECT c.`id`, c.`name`, c.`main_category_id`, s.`main_category_id` AS area_lama
FROM `categories` c
LEFT JOIN `sub_categories` s ON s.`category_id` = c.`id` AND s.`deleted_at` IS NULL
WHERE NOT (c.`main_category_id` <=> s.`main_category_id`);

-- Untuk membatalkan: jalankan docs/sql/2026-11-18-tahap1-struktur-kategori-BATAL.sql
