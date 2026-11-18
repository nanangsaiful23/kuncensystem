-- MEMBATALKAN Tahap 2 (2026-11-18-tahap2-isi-kategori-jenis.sql), memakai tabel backup bak_tahap2_*.
-- Hanya dijalankan SEBELUM Tahap 3 (pemindahan kategori barang) dijalankan.
-- Mengembalikan nama & jenis barang, tabel types, categories, dan main_categories
-- persis seperti sebelum Tahap 2.

START TRANSACTION;

UPDATE `goods` g JOIN `bak_tahap2_goods` b ON b.`id` = g.`id` SET g.`name` = b.`name`, g.`type_id` = b.`type_id`;

DELETE FROM `types` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_types`)
  AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`type_id` = `types`.`id`);
UPDATE `types` t JOIN `bak_tahap2_types` b ON b.`id` = t.`id`
SET t.`category_id` = b.`category_id`, t.`code` = b.`code`, t.`name` = b.`name`, t.`eng_name` = b.`eng_name`,
    t.`tipe_kontrol` = b.`tipe_kontrol`, t.`min_model` = b.`min_model`, t.`max_model` = b.`max_model`,
    t.`updated_at` = b.`updated_at`, t.`deleted_at` = b.`deleted_at`;

DELETE FROM `categories` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_categories`)
  AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`category_id` = `categories`.`id`);
UPDATE `categories` c JOIN `bak_tahap2_categories` b ON b.`id` = c.`id`
SET c.`main_category_id` = b.`main_category_id`, c.`code` = b.`code`, c.`name` = b.`name`, c.`eng_name` = b.`eng_name`,
    c.`unit_id` = b.`unit_id`, c.`color` = b.`color`, c.`habis_pakai` = b.`habis_pakai`,
    c.`updated_at` = b.`updated_at`, c.`deleted_at` = b.`deleted_at`;

DELETE FROM `main_categories` WHERE `id` NOT IN (SELECT `id` FROM `bak_tahap2_main_categories`);
UPDATE `main_categories` m JOIN `bak_tahap2_main_categories` b ON b.`id` = m.`id`
SET m.`code` = b.`code`, m.`name` = b.`name`, m.`eng_name` = b.`eng_name`, m.`description` = b.`description`,
    m.`updated_at` = b.`updated_at`, m.`deleted_at` = b.`deleted_at`;

COMMIT;

-- Setelah yakin, tabel backup boleh dihapus:
--   DROP TABLE `bak_tahap2_goods`, `bak_tahap2_types`, `bak_tahap2_categories`, `bak_tahap2_main_categories`;
