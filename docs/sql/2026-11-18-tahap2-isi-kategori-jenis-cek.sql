-- Pemeriksaan setelah 2026-11-18-tahap2-isi-kategori-jenis.sql. Semua query harus menghasilkan 0 baris,
-- kecuali yang disebut lain. Hanya membaca, tidak mengubah apa pun.

-- 1. Jenis aktif yang belum punya kategori induk (jenis yang dibuat setelah salinan data lokal)
SELECT `id`, `name` FROM `types` WHERE `deleted_at` IS NULL AND `category_id` IS NULL;

-- 2. Barang (termasuk yang terhapus) yang menunjuk jenis nonaktif
SELECT g.`id`, g.`name`, g.`type_id` FROM `goods` g JOIN `types` t ON t.`id` = g.`type_id` WHERE t.`deleted_at` IS NOT NULL;

-- 3. Kategori aktif tanpa Area
SELECT `id`, `name` FROM `categories` WHERE `deleted_at` IS NULL AND `main_category_id` IS NULL;

-- 4. Nama jenis aktif yang dobel
SELECT LOWER(`name`) AS jenis, COUNT(*) FROM `types` WHERE `deleted_at` IS NULL GROUP BY LOWER(`name`) HAVING COUNT(*) > 1;

-- 5. (Informasi, bukan 0) Ringkasan per Area
SELECT m.`code`, m.`name` AS area, COUNT(DISTINCT c.`id`) AS kategori, COUNT(DISTINCT t.`id`) AS jenis
FROM `main_categories` m LEFT JOIN `categories` c ON c.`main_category_id` = m.`id` AND c.`deleted_at` IS NULL
LEFT JOIN `types` t ON t.`category_id` = c.`id` AND t.`deleted_at` IS NULL
WHERE m.`deleted_at` IS NULL GROUP BY m.`id` ORDER BY m.`code`;

-- 6. (Informasi) Jumlah barang yang namanya berubah = harus sekitar 685
SELECT COUNT(*) FROM `goods` g JOIN `bak_tahap2_goods` b ON b.`id` = g.`id` WHERE NOT (g.`name` <=> b.`name`);
