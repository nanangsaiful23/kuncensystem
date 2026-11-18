-- Pengganti:
--   php artisan db:seed --class=PenataanKategoriTahap3JenisTambahanSeeder
--
-- TAHAP 3a: 24 jenis barang tambahan yang disetujui pemilik (2026-11-18).
-- SYARAT: SQL Tahap 1 dan Tahap 2 sudah dijalankan.
-- Jalankan di phpMyAdmin (tab SQL). Aman bila dijalankan dua kali.
-- Hanya menambah baris di tabel types; tidak mengubah barang.
-- Untuk membatalkan: 2026-11-18-tahap3a-jenis-tambahan-BATAL.sql

INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-OBAT-NYAMUK-SERANGGA', 'Obat Nyamuk & Serangga', NOW() FROM `categories` c WHERE c.`code` = 'CLEANING' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-OBAT-NYAMUK-SERANGGA');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-BASKOM-STAINLESS', 'Baskom Stainless', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-BASKOM-STAINLESS');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-GENTONG-TONG-AIR', 'Gentong & Tong Air', NOW() FROM `categories` c WHERE c.`code` = 'BUCKET' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-GENTONG-TONG-AIR');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-KEMOCENG', 'Kemoceng', NOW() FROM `categories` c WHERE c.`code` = 'CLEANTOOL' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-KEMOCENG');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-RAK-PLASTIK', 'Rak Plastik', NOW() FROM `categories` c WHERE c.`code` = 'STORAGEBOX' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-RAK-PLASTIK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-HANGER-JEPIT-JEMURAN', 'Hanger & Jepit Jemuran', NOW() FROM `categories` c WHERE c.`code` = 'HOMEGOODS' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-HANGER-JEPIT-JEMURAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-TAPLAK-MEJA', 'Taplak Meja', NOW() FROM `categories` c WHERE c.`code` = 'HOMEGOODS' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-TAPLAK-MEJA');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-HIASAN-CERMIN', 'Hiasan & Cermin', NOW() FROM `categories` c WHERE c.`code` = 'HOMEGOODS' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-HIASAN-CERMIN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-PERKAKAS-KUNCI', 'Perkakas & Kunci', NOW() FROM `categories` c WHERE c.`code` = 'HOMEGOODS' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-PERKAKAS-KUNCI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-PISAU', 'Pisau', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-PISAU');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-SARINGAN-SEROK', 'Saringan & Serok', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-SARINGAN-SEROK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-CETAKAN-LOYANG', 'Cetakan & Loyang', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-CETAKAN-LOYANG');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-SOTIL-IRUS-CAPIT', 'Sotil, Irus & Capit', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-SOTIL-IRUS-CAPIT');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-TALENAN-PARUTAN-COBEK', 'Talenan, Parutan & Cobek', NOW() FROM `categories` c WHERE c.`code` = 'COOKWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-TALENAN-PARUTAN-COBEK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-TEMPAT-BUMBU', 'Tempat Bumbu', NOW() FROM `categories` c WHERE c.`code` = 'TABLEWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-TEMPAT-BUMBU');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-TEMPAT-SENDOK', 'Tempat Sendok', NOW() FROM `categories` c WHERE c.`code` = 'TABLEWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-TEMPAT-SENDOK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-NAMPAN', 'Nampan', NOW() FROM `categories` c WHERE c.`code` = 'TABLEWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-NAMPAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-TUDUNG-SAJI', 'Tudung Saji', NOW() FROM `categories` c WHERE c.`code` = 'TABLEWARE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-TUDUNG-SAJI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-SABUN-KEWANITAAN', 'Sabun Kewanitaan', NOW() FROM `categories` c WHERE c.`code` = 'BEAUTY' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-SABUN-KEWANITAAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-SISIR', 'Sisir', NOW() FROM `categories` c WHERE c.`code` = 'BEAUTY' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-SISIR');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-SUKU-CADANG-KOMPOR', 'Suku Cadang Kompor', NOW() FROM `categories` c WHERE c.`code` = 'STOVE' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-SUKU-CADANG-KOMPOR');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-CELANA-DEWASA', 'Celana Dewasa', NOW() FROM `categories` c WHERE c.`code` = 'CLOTHING' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-CELANA-DEWASA');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-ATASAN-DEWASA', 'Atasan Dewasa', NOW() FROM `categories` c WHERE c.`code` = 'CLOTHING' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-ATASAN-DEWASA');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT c.`id`, 'K3-ATRIBUT-SERAGAM', 'Atribut Seragam', NOW() FROM `categories` c WHERE c.`code` = 'SCHOOL' AND NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K3-ATRIBUT-SERAGAM');

-- Cek: harus 24
SELECT COUNT(*) AS jenis_tambahan FROM `types` WHERE `code` IN ('K3-OBAT-NYAMUK-SERANGGA', 'K3-BASKOM-STAINLESS', 'K3-GENTONG-TONG-AIR', 'K3-KEMOCENG', 'K3-RAK-PLASTIK', 'K3-HANGER-JEPIT-JEMURAN', 'K3-TAPLAK-MEJA', 'K3-HIASAN-CERMIN', 'K3-PERKAKAS-KUNCI', 'K3-PISAU', 'K3-SARINGAN-SEROK', 'K3-CETAKAN-LOYANG', 'K3-SOTIL-IRUS-CAPIT', 'K3-TALENAN-PARUTAN-COBEK', 'K3-TEMPAT-BUMBU', 'K3-TEMPAT-SENDOK', 'K3-NAMPAN', 'K3-TUDUNG-SAJI', 'K3-SABUN-KEWANITAAN', 'K3-SISIR', 'K3-SUKU-CADANG-KOMPOR', 'K3-CELANA-DEWASA', 'K3-ATASAN-DEWASA', 'K3-ATRIBUT-SERAGAM') AND `deleted_at` IS NULL AND `category_id` IS NOT NULL;
