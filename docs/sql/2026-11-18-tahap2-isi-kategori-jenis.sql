-- Pengganti:
--   php artisan db:seed --class=PenataanKategoriTahap2Seeder
--
-- TAHAP 2 penataan kategori: isi Area, Kategori, dan Jenis Barang baru,
-- serta gabungkan jenis barang yang dobel/terpecah.
--
-- SYARAT: docs/sql/2026-11-18-tahap1-struktur-kategori.sql sudah dijalankan.
-- SEBELUM MENJALANKAN: export seluruh database lewat phpMyAdmin dan simpan.
--
-- Jalankan di phpMyAdmin (tab SQL): tempel seluruh isi file lalu klik Go.
-- Aman bila tidak sengaja dijalankan dua kali (perubahan tidak dobel).
-- Setelah itu jalankan 2026-11-18-tahap2-isi-kategori-jenis-cek.sql untuk memeriksa hasil.
--
-- Yang diubah:
--   - main_categories (Area): ganti nama + tambah area baru.
--   - categories: ganti nama (id & kode tetap), isi Area & habis pakai,
--     tambah kategori baru; kategori yang dipecah diberi akhiran "(lama)".
--   - types: ganti nama, isi kategori induk, gabungkan yang dobel, tambah jenis baru.
--   - goods: HANYA kolom type_id dan name (diberi awalan) untuk barang yang
--     jenisnya digabung, supaya nama tampil di struk tetap sama.
--     Kategori barang BELUM dipindah (itu Tahap 3).
-- Tidak mengubah harga, stok, transaksi, loading, maupun jurnal.
--
-- Untuk membatalkan: 2026-11-18-tahap2-isi-kategori-jenis-BATAL.sql (sebelum Tahap 3 dijalankan).

-- 0. Backup tabel yang diubah (dibuat sekali; tidak ditimpa bila dijalankan ulang)
CREATE TABLE IF NOT EXISTS `bak_tahap2_goods` AS SELECT `id`, `name`, `type_id`, `category_id` FROM `goods`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_types` AS SELECT * FROM `types`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_categories` AS SELECT * FROM `categories`;
CREATE TABLE IF NOT EXISTS `bak_tahap2_main_categories` AS SELECT * FROM `main_categories`;

START TRANSACTION;

-- 1. Area
UPDATE `main_categories` SET `code` = 'A1', `name` = 'Makanan dan Minuman', `eng_name` = 'Food and Beverages' WHERE `id` = 2;
UPDATE `main_categories` SET `code` = 'A4', `name` = 'Alat Rumah Tangga', `eng_name` = 'Household Tools' WHERE `id` = 5;
UPDATE `main_categories` SET `code` = 'A6', `name` = 'Furniture dan Kamar', `eng_name` = 'Furniture and Bedroom' WHERE `id` = 1;
UPDATE `main_categories` SET `code` = 'A7', `name` = 'Alat Tulis dan Sekolah', `eng_name` = 'Stationery and School' WHERE `id` = 3;
UPDATE `main_categories` SET `code` = 'A8', `name` = 'Pakaian dan Aksesori', `eng_name` = 'Clothing and Accessories' WHERE `id` = 4;
INSERT INTO `main_categories` (`code`, `name`, `eng_name`, `created_at`) SELECT 'A2', 'Sabun dan Perawatan Diri', 'Soap and Personal Care', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `main_categories` WHERE `code` = 'A2');
INSERT INTO `main_categories` (`code`, `name`, `eng_name`, `created_at`) SELECT 'A3', 'Bayi', 'Baby', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `main_categories` WHERE `code` = 'A3');
INSERT INTO `main_categories` (`code`, `name`, `eng_name`, `created_at`) SELECT 'A5', 'Dapur dan Alat Makan', 'Kitchen and Tableware', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `main_categories` WHERE `code` = 'A5');
INSERT INTO `main_categories` (`code`, `name`, `eng_name`, `created_at`) SELECT 'A9', 'Kebun dan Olahraga', 'Garden and Sport', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `main_categories` WHERE `code` = 'A9');

-- 2. Kategori: ganti nama (id & kode tetap), isi Area dan habis pakai
UPDATE `categories` SET `name` = 'Makanan & Minuman Kemasan', `eng_name` = 'Packaged Food & Drinks', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A1') WHERE `id` = 2; -- dulu: Makanan dan Minuman
UPDATE `categories` SET `name` = 'Snack Kiloan', `eng_name` = 'Bulk Snacks', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A1') WHERE `id` = 3; -- dulu: Makanan dan Minuman Kiloan
UPDATE `categories` SET `name` = 'Beras Kiloan', `eng_name` = 'Bulk Rice', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A1') WHERE `id` = 4; -- dulu: Beras Kiloan
UPDATE `categories` SET `name` = 'Sabun Cuci & Pembersih Rumah', `eng_name` = 'Laundry & Home Cleaning', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A2') WHERE `id` = 11; -- dulu: Peralatan Pembersih
UPDATE `categories` SET `name` = 'Sabun Mandi & Perawatan Gigi', `eng_name` = 'Bath & Oral Care', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A2') WHERE `id` = 9; -- dulu: Perlengkapan Mandi
UPDATE `categories` SET `name` = 'Tisu, Pembalut & Kecantikan', `eng_name` = 'Tissue, Feminine & Beauty', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A2') WHERE `id` = 10; -- dulu: Tisu, Perawatan dan Kecantikan
UPDATE `categories` SET `name` = 'Obat & Kesehatan', `eng_name` = 'Medicine & Health', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A2') WHERE `id` = 5; -- dulu: Kesehatan
UPDATE `categories` SET `name` = 'Plastik Kemasan', `eng_name` = 'Packaging Plastic', `habis_pakai` = 1, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A4') WHERE `id` = 23; -- dulu: Plastik
UPDATE `categories` SET `name` = 'Pecah Belah Kaca & Keramik', `eng_name` = 'Glass & Ceramic Ware', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A5') WHERE `id` = 21; -- dulu: Barang Pecah Belah
UPDATE `categories` SET `name` = 'Kompor', `eng_name` = 'Stove', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A5') WHERE `id` = 22; -- dulu: Kompor
UPDATE `categories` SET `name` = 'Elektronik Rumah Tangga', `eng_name` = 'Home Electronics', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A5') WHERE `id` = 20; -- dulu: Barang Elektronik
UPDATE `categories` SET `name` = 'Furniture', `eng_name` = 'Furniture', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A6') WHERE `id` = 17; -- dulu: Lemari
UPDATE `categories` SET `name` = 'Kasur, Bantal & Sprei', `eng_name` = 'Mattress, Pillows & Sheets', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A6') WHERE `id` = 6; -- dulu: Bedding
UPDATE `categories` SET `name` = 'Karpet & Keset', `eng_name` = 'Carpets & Doormats', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A6') WHERE `id` = 24; -- dulu: Karpet/Keset
UPDATE `categories` SET `name` = 'Buku & Alat Tulis', `eng_name` = 'Books & Stationery', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A7') WHERE `id` = 7; -- dulu: Buku dan Alat Tulis
UPDATE `categories` SET `name` = 'Tas & Perlengkapan Sekolah', `eng_name` = 'School Bags & Supplies', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A7') WHERE `id` = 16; -- dulu: Peralatan Sekolah
UPDATE `categories` SET `name` = 'Pakaian', `eng_name` = 'Clothing', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A8') WHERE `id` = 12; -- dulu: Pakaian
UPDATE `categories` SET `name` = 'Sepatu & Sandal', `eng_name` = 'Footwear', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A8') WHERE `id` = 13; -- dulu: Sepatu & Sandal
UPDATE `categories` SET `name` = 'Tas & Dompet', `eng_name` = 'Bags & Wallets', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A8') WHERE `id` = 14; -- dulu: Tas, Dompet, Dkk
UPDATE `categories` SET `name` = 'Perlengkapan Sholat', `eng_name` = 'Prayer Supplies', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A8') WHERE `id` = 15; -- dulu: Peralatan Sholat
UPDATE `categories` SET `name` = 'Alat & Bahan Berkebun', `eng_name` = 'Gardening', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A9') WHERE `id` = 18; -- dulu: Peralatan Berkebun
UPDATE `categories` SET `name` = 'Alat Olahraga', `eng_name` = 'Sports', `habis_pakai` = 0, `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A9') WHERE `id` = 27; -- dulu: Alat Olahraga

-- 3. Kategori baru
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A1'), 'SEMBAKO', 'Sembako', 'Staple Groceries', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#E9C46A', 1, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'SEMBAKO');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A3'), 'BABYCARE', 'Popok & Perawatan Bayi', 'Baby Diapers & Care', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#9BD18B', 1, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'BABYCARE');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A3'), 'BABYGEAR', 'Pakaian & Peralatan Bayi', 'Baby Clothing & Gear', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#5E9C57', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'BABYGEAR');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A4'), 'CLEANTOOL', 'Alat Kebersihan', 'Cleaning Tools', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#6FA8B5', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'CLEANTOOL');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A4'), 'BUCKET', 'Ember, Bak & Alat Cuci', 'Buckets, Basins & Laundry', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#F4A261', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'BUCKET');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A4'), 'STORAGEBOX', 'Box Penyimpanan', 'Storage Boxes', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#C08552', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'STORAGEBOX');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A4'), 'HOMEGOODS', 'Kursi Plastik & Perlengkapan Rumah', 'Plastic Chairs & Home Goods', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#E76F51', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'HOMEGOODS');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A5'), 'COOKWARE', 'Alat Masak', 'Cookware', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#B5838D', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'COOKWARE');
INSERT INTO `categories` (`main_category_id`, `code`, `name`, `eng_name`, `unit_id`, `color`, `habis_pakai`, `created_at`, `updated_at`) SELECT (SELECT `id` FROM `main_categories` WHERE `code` = 'A5'), 'TABLEWARE', 'Alat Makan & Minum Tidak Pecah', 'Unbreakable Tableware', (SELECT `unit_id` FROM `categories` WHERE `code` = 'HOUSEWARE'), '#FFB4A2', 0, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` c2 WHERE c2.`code` = 'TABLEWARE');

-- 4. Kategori lama yang dipecah/digabung: diberi akhiran (lama); barangnya dipindah di Tahap 3
UPDATE `categories` SET `name` = 'Peralatan Rumah Tangga (lama)', `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A4') WHERE `id` = 1; -- Dipecah ke Alat Kebersihan, Ember Bak & Alat Cuci, Box Penyimpanan, Kursi Plastik & Perlengkapan Rumah, Alat Masak, Alat Makan & Minum Tidak Pecah
UPDATE `categories` SET `name` = 'Perlengkapan Bayi (lama)', `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A3') WHERE `id` = 8; -- Dipecah ke Popok & Perawatan Bayi dan Pakaian & Peralatan Bayi
UPDATE `categories` SET `name` = 'Obat-obatan (lama)', `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A2') WHERE `id` = 26; -- Digabung ke Obat & Kesehatan
UPDATE `categories` SET `name` = 'Karpet/Keset Meteran (lama)', `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A6') WHERE `id` = 25; -- Digabung ke Karpet & Keset (keputusan no. 5)
UPDATE `categories` SET `name` = 'Peralatan Berkebun Kiloan (lama)', `main_category_id` = (SELECT `id` FROM `main_categories` WHERE `code` = 'A9') WHERE `id` = 19; -- Kosong (hanya 1 barang terhapus); dinonaktifkan setelah barang itu dipindah

-- 5. Barang pada jenis yang digabung/diganti nama: pindah jenis dan beri awalan nama.
--    Syarat nama jenis lama di setiap baris membuat perintah ini tidak dobel bila diulang.
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 1 AND t.`name` = 'Tempat Sampah Plastik'; -- Tempat Sampah Plastik -> Tempat Sampah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('gantung ', g.`name`) WHERE t.`id` = 2 AND t.`name` = 'Lemari Plastik Gantung'; -- Lemari Plastik Gantung -> Lemari Plastik
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('gantung ', g.`name`) WHERE t.`id` = 4 AND t.`name` = 'Lemari Besi Gantung'; -- Lemari Besi Gantung -> Lemari Besi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('saji ', g.`name`) WHERE t.`id` = 18 AND t.`name` = 'Piring Kaca Saji'; -- Piring Kaca Saji -> Piring Kaca
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('dewasa ', g.`name`) WHERE t.`id` = 28 AND t.`name` = 'Selimut Dewasa'; -- Selimut Dewasa -> Selimut
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cream ', g.`name`) WHERE t.`id` = 39 AND t.`name` = 'Sabun Cuci Piring Cream'; -- Sabun Cuci Piring Cream -> Sabun Cuci Piring
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kuping ', g.`name`) WHERE t.`id` = 40 AND t.`name` = 'Gelas Kaca Kuping'; -- Gelas Kaca Kuping -> Gelas Kaca
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('tutup plastik ', g.`name`) WHERE t.`id` = 51 AND t.`name` = 'Ember Tutup Plastik'; -- Ember Tutup Plastik -> Ember
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sprei ', g.`name`) WHERE t.`id` = 56 AND t.`name` = 'Bed Cover + Sprei'; -- Bed Cover + Sprei -> Bed Cover
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('stainless steel ', g.`name`) WHERE t.`id` = 68 AND t.`name` = 'Garpu Stainless Steel'; -- Garpu Stainless Steel -> Sendok & Garpu
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('alumunium ', g.`name`) WHERE t.`id` = 75 AND t.`name` = 'Dandang Alumunium'; -- Dandang Alumunium -> Dandang
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 76 AND t.`name` = 'Bak Mandi Bayi Plastik'; -- Bak Mandi Bayi Plastik -> Bak Mandi Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('ijuk ', g.`name`) WHERE t.`id` = 79 AND t.`name` = 'Sapu Ijuk'; -- Sapu Ijuk -> Sapu
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 85 AND t.`name` = 'Kontainer Plastik'; -- Kontainer Plastik -> Kontainer
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi samping ', g.`name`) WHERE t.`id` = 87 AND t.`name` = 'Gendongan Bayi Samping'; -- Gendongan Bayi Samping -> Gendongan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bubuk ', g.`name`) WHERE t.`id` = 94 AND t.`name` = 'Susu Bubuk'; -- Susu Bubuk -> Susu & Yogurt
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('listrik ', g.`name`) WHERE t.`id` = 97 AND t.`name` = 'Kipas Angin Listrik'; -- Kipas Angin Listrik -> Kipas Angin
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('charger ', g.`name`) WHERE t.`id` = 99 AND t.`name` = 'Kabel Charger'; -- Kabel Charger -> Kabel
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('elektrik ', g.`name`) WHERE t.`id` = 105 AND t.`name` = 'Pompa Galon Elektrik'; -- Pompa Galon Elektrik -> Pompa Galon
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('keramik ', g.`name`) WHERE t.`id` = 111 AND t.`name` = 'Cangkir + Lepek Keramik'; -- Cangkir + Lepek Keramik -> Cangkir + Lepek
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('gelas ', g.`name`) WHERE t.`id` = 113 AND t.`name` = 'Teko Set Gelas Kaca'; -- Teko Set Gelas Kaca -> Teko Set Kaca
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('dewasa ', g.`name`) WHERE t.`id` = 118 AND t.`name` = 'Cutton Bud Dewasa'; -- Cutton Bud Dewasa -> Cotton Bud
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('setelan anak panjang ', g.`name`) WHERE t.`id` = 120 AND t.`name` = 'Setelan Anak Panjang'; -- Setelan Anak Panjang -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('dewasa ', g.`name`) WHERE t.`id` = 126 AND t.`name` = 'Handuk Dewasa'; -- Handuk Dewasa -> Handuk
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baby hair ', g.`name`) WHERE t.`id` = 130 AND t.`name` = 'Baby Hair Lotion'; -- Baby Hair Lotion -> Bedak & Lotion Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi ', g.`name`) WHERE t.`id` = 135 AND t.`name` = 'Perlak Bayi'; -- Perlak Bayi -> Perlak
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi ', g.`name`) WHERE t.`id` = 150 AND t.`name` = 'Dot Bayi'; -- Dot Bayi -> Botol & Dot
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('deo roll ', g.`name`) WHERE t.`id` = 178 AND t.`name` = 'Deo Roll'; -- Deo Roll -> Deodoran
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('face wash ', g.`name`) WHERE t.`id` = 182 AND t.`name` = 'Face Wash'; -- Face Wash -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('hair ', g.`name`) WHERE t.`id` = 191 AND t.`name` = 'Hair Conditioner'; -- Hair Conditioner -> Conditioner
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('refill ', g.`name`) WHERE t.`id` = 197 AND t.`name` = 'Pelicin Pakaian Refill'; -- Pelicin Pakaian Refill -> Pelicin Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('renceng ', g.`name`) WHERE t.`id` = 202 AND t.`name` = 'Pewangi Pakaian Renceng'; -- Pewangi Pakaian Renceng -> Pewangi Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('gantung ', g.`name`) WHERE t.`id` = 203 AND t.`name` = 'Pengharum Ruangan Gantung'; -- Pengharum Ruangan Gantung -> Pengharum Ruangan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 211 AND t.`name` = 'Keranjang Pakaian Plastik'; -- Keranjang Pakaian Plastik -> Keranjang Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 212 AND t.`name` = 'Keranjang Plastik'; -- Keranjang Plastik -> Keranjang Serbaguna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 214 AND t.`name` = 'Centong Nasi Plastik'; -- Centong Nasi Plastik -> Centong Nasi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 216 AND t.`name` = 'Tempat Tisu Plastik'; -- Tempat Tisu Plastik -> Tempat Tisu
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('crayon ', g.`name`) WHERE t.`id` = 244 AND t.`name` = 'Crayon'; -- Crayon -> Alat Warna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('plastik ', g.`name`) WHERE t.`id` = 251 AND t.`name` = 'Penggaris Plastik'; -- Penggaris Plastik -> Penggaris
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cair ', g.`name`) WHERE t.`id` = 260 AND t.`name` = 'Lem Cair'; -- Lem Cair -> Lem
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('isi ', g.`name`) WHERE t.`id` = 263 AND t.`name` = 'Isi Staples'; -- Isi Staples -> Staples
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('jarum ', g.`name`) WHERE t.`id` = 272 AND t.`name` = 'Jarum Jahit'; -- Jarum Jahit -> Alat Jahit
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('harga ', g.`name`) WHERE t.`id` = 289 AND t.`name` = 'Label Harga'; -- Label Harga -> Label & Stiker
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('lipat ', g.`name`) WHERE t.`id` = 292 AND t.`name` = 'Kertas Lipat'; -- Kertas Lipat -> Kertas
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 40 WHERE t.`id` = 9 AND t.`name` = 'Gelas Kaca'; -- Gelas Kaca -> Gelas Kaca
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 18 WHERE t.`id` = 12 AND t.`name` = 'Piring Kaca'; -- Piring Kaca -> Piring Kaca
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('saji ', g.`name`), g.`type_id` = 13 WHERE t.`id` = 17 AND t.`name` = 'Piring Plastik Saji'; -- Piring Plastik Saji -> Piring Plastik
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('ceper ', g.`name`), g.`type_id` = 20 WHERE t.`id` = 19 AND t.`name` = 'Piring Keramik Ceper'; -- Piring Keramik Ceper -> Piring Keramik
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cair ', g.`name`), g.`type_id` = 39 WHERE t.`id` = 38 AND t.`name` = 'Sabun Cuci Piring Cair'; -- Sabun Cuci Piring Cair -> Sabun Cuci Piring
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('batang ', g.`name`), g.`type_id` = 72 WHERE t.`id` = 70 AND t.`name` = 'Sabun Bayi Batang'; -- Sabun Bayi Batang -> Sabun & Sampo Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cair ', g.`name`), g.`type_id` = 72 WHERE t.`id` = 71 AND t.`name` = 'Sabun Bayi Cair'; -- Sabun Bayi Cair -> Sabun & Sampo Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 56 WHERE t.`id` = 78 AND t.`name` = 'Bed Cover'; -- Bed Cover -> Bed Cover
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi depan ', g.`name`), g.`type_id` = 87 WHERE t.`id` = 88 AND t.`name` = 'Gendongan Bayi Depan'; -- Gendongan Bayi Depan -> Gendongan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi duduk ', g.`name`), g.`type_id` = 87 WHERE t.`id` = 90 AND t.`name` = 'Gendongan Bayi Duduk'; -- Gendongan Bayi Duduk -> Gendongan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('isi ulang ', g.`name`), g.`type_id` = 102 WHERE t.`id` = 103 AND t.`name` = 'Baterai Isi Ulang'; -- Baterai Isi Ulang -> Baterai
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 29 WHERE t.`id` = 108 AND t.`name` = 'Kasur Bayi'; -- Kasur Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('colokan ', g.`name`), g.`type_id` = 99 WHERE t.`id` = 109 AND t.`name` = 'Kabel Colokan'; -- Kabel Colokan -> Kabel
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaca ', g.`name`), g.`type_id` = 111 WHERE t.`id` = 112 AND t.`name` = 'Cangkir + Lepek Kaca'; -- Cangkir + Lepek Kaca -> Cangkir + Lepek
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaca ', g.`name`), g.`type_id` = 111 WHERE t.`id` = 114 AND t.`name` = 'Lepek Kaca'; -- Lepek Kaca -> Cangkir + Lepek
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 130 WHERE t.`id` = 115 AND t.`name` = 'Bedak Bayi'; -- Bedak Bayi -> Bedak & Lotion Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi ', g.`name`), g.`type_id` = 227 WHERE t.`id` = 116 AND t.`name` = 'Sikat Gigi Bayi'; -- Sikat Gigi Bayi -> Sikat Gigi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('setelan anak pendek ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 121 AND t.`name` = 'Setelan Anak Pendek'; -- Setelan Anak Pendek -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baju anak pendek ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 122 AND t.`name` = 'Baju Anak Pendek'; -- Baju Anak Pendek -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('celana anak pendek ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 123 AND t.`name` = 'Celana Anak Pendek'; -- Celana Anak Pendek -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('celana anak panjang ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 124 AND t.`name` = 'Celana Anak Panjang'; -- Celana Anak Panjang -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('handuk ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 125 AND t.`name` = 'Handuk Bayi'; -- Handuk Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baby oil ', g.`name`), g.`type_id` = 130 WHERE t.`id` = 128 AND t.`name` = 'Baby Oil'; -- Baby Oil -> Bedak & Lotion Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baby ', g.`name`), g.`type_id` = 130 WHERE t.`id` = 129 AND t.`name` = 'Baby Lotion'; -- Baby Lotion -> Bedak & Lotion Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baby cream ', g.`name`), g.`type_id` = 130 WHERE t.`id` = 131 AND t.`name` = 'Baby Cream'; -- Baby Cream -> Bedak & Lotion Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baby hair body wash ', g.`name`), g.`type_id` = 72 WHERE t.`id` = 132 AND t.`name` = 'Baby Hair & Body Wash'; -- Baby Hair & Body Wash -> Sabun & Sampo Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('baju anak panjang ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 133 AND t.`name` = 'Baju Anak Panjang'; -- Baju Anak Panjang -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaos dalam anak ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 137 AND t.`name` = 'Kaos Dalam Anak'; -- Kaos Dalam Anak -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaos kaki ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 139 AND t.`name` = 'Kaos Kaki Bayi'; -- Kaos Kaki Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaos kaki anak ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 140 AND t.`name` = 'Kaos Kaki Anak'; -- Kaos Kaki Anak -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('anak ', g.`name`), g.`type_id` = 226 WHERE t.`id` = 143 AND t.`name` = 'Pasta Gigi Anak'; -- Pasta Gigi Anak -> Pasta Gigi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('+ sikat gigi anak ', g.`name`), g.`type_id` = 226 WHERE t.`id` = 144 AND t.`name` = 'Pasta Gigi + Sikat Gigi Anak'; -- Pasta Gigi + Sikat Gigi Anak -> Pasta Gigi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sleeping bag ', g.`name`), g.`type_id` = 29 WHERE t.`id` = 145 AND t.`name` = 'Sleeping Bag Bayi'; -- Sleeping Bag Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bedong ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 146 AND t.`name` = 'Bedong Bayi'; -- Bedong Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('topi ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 147 AND t.`name` = 'Topi Bayi'; -- Topi Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bayi ', g.`name`), g.`type_id` = 150 WHERE t.`id` = 151 AND t.`name` = 'Dot + Botol Bayi'; -- Dot + Botol Bayi -> Botol & Dot
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('matras ', g.`name`), g.`type_id` = 29 WHERE t.`id` = 152 AND t.`name` = 'Matras Bayi'; -- Matras Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kaos tangan ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 157 AND t.`name` = 'Kaos Tangan Bayi'; -- Kaos Tangan Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('celemek ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 159 AND t.`name` = 'Celemek Bayi'; -- Celemek Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('lendang ', g.`name`), g.`type_id` = 87 WHERE t.`id` = 160 AND t.`name` = 'Lendang'; -- Lendang -> Gendongan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kelambu kojong ', g.`name`), g.`type_id` = 29 WHERE t.`id` = 161 AND t.`name` = 'Kelambu/Kojong Bayi'; -- Kelambu/Kojong Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('set mandi ', g.`name`), g.`type_id` = 72 WHERE t.`id` = 162 AND t.`name` = 'Set Mandi Bayi'; -- Set Mandi Bayi -> Sabun & Sampo Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sofa ', g.`name`), g.`type_id` = 29 WHERE t.`id` = 164 AND t.`name` = 'Sofa Bayi'; -- Sofa Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bouncer ', g.`name`), g.`type_id` = 29 WHERE t.`id` = 166 AND t.`name` = 'Bouncer Bayi'; -- Bouncer Bayi -> Kasur & Selimut Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('waslap ', g.`name`), g.`type_id` = 120 WHERE t.`id` = 167 AND t.`name` = 'Waslap Bayi'; -- Waslap Bayi -> Pakaian Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('tempat bedak ', g.`name`), g.`type_id` = 76 WHERE t.`id` = 168 AND t.`name` = 'Tempat Bedak Bayi'; -- Tempat Bedak Bayi -> Bak Mandi Bayi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('parfum anak ', g.`name`), g.`type_id` = 175 WHERE t.`id` = 176 AND t.`name` = 'Parfum Anak'; -- Parfum Anak -> Body Spray
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('deo lotion ', g.`name`), g.`type_id` = 178 WHERE t.`id` = 177 AND t.`name` = 'Deo Lotion'; -- Deo Lotion -> Deodoran
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('masker ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 181 AND t.`name` = 'Masker Wajah'; -- Masker Wajah -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sun screen ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 183 AND t.`name` = 'Sun Screen'; -- Sun Screen -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('moisturizer ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 184 AND t.`name` = 'Moisturizer'; -- Moisturizer -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('facial foam ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 185 AND t.`name` = 'Facial Foam'; -- Facial Foam -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('facial lotion ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 186 AND t.`name` = 'Facial Lotion'; -- Facial Lotion -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('bb cream ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 187 AND t.`name` = 'BB Cream'; -- BB Cream -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('serum ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 188 AND t.`name` = 'Serum Wajah'; -- Serum Wajah -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('face tonic ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 189 AND t.`name` = 'Face Tonic'; -- Face Tonic -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('milk cleanser ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 190 AND t.`name` = 'Milk Cleanser'; -- Milk Cleanser -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('day cream ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 193 AND t.`name` = 'Day Cream'; -- Day Cream -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('night cream ', g.`name`), g.`type_id` = 182 WHERE t.`id` = 194 AND t.`name` = 'Night Cream'; -- Night Cream -> Perawatan Wajah
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('botol ', g.`name`), g.`type_id` = 197 WHERE t.`id` = 198 AND t.`name` = 'Pelicin Pakaian Botol'; -- Pelicin Pakaian Botol -> Pelicin Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('refill ', g.`name`), g.`type_id` = 202 WHERE t.`id` = 200 AND t.`name` = 'Pewangi Pakaian Refill'; -- Pewangi Pakaian Refill -> Pewangi Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('botol ', g.`name`), g.`type_id` = 202 WHERE t.`id` = 201 AND t.`name` = 'Pewangi Pakaian Botol'; -- Pewangi Pakaian Botol -> Pewangi Pakaian
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('semprot ', g.`name`), g.`type_id` = 203 WHERE t.`id` = 204 AND t.`name` = 'Pengharum Ruangan Semprot'; -- Pengharum Ruangan Semprot -> Pengharum Ruangan
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kayu ', g.`name`), g.`type_id` = 214 WHERE t.`id` = 215 AND t.`name` = 'Centong Nasi Kayu'; -- Centong Nasi Kayu -> Centong Nasi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('spon kawat ', g.`name`), g.`type_id` = 218 WHERE t.`id` = 224 AND t.`name` = 'Spon Kawat'; -- Spon Kawat -> Spons Cuci Piring
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('anak ', g.`name`), g.`type_id` = 227 WHERE t.`id` = 228 AND t.`name` = 'Sikat Gigi Anak'; -- Sikat Gigi Anak -> Sikat Gigi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('tulis ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 234 AND t.`name` = 'Buku Tulis'; -- Buku Tulis -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('isi binder ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 235 AND t.`name` = 'Isi Binder'; -- Isi Binder -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('permanen ', g.`name`), g.`type_id` = 236 WHERE t.`id` = 237 AND t.`name` = 'Spidol Permanen'; -- Spidol Permanen -> Spidol
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('nota ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 238 AND t.`name` = 'Buku Nota'; -- Buku Nota -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 232 WHERE t.`id` = 240 AND t.`name` = 'Lakban'; -- Lakban -> Lakban & Solasi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('isi ', g.`name`), g.`type_id` = 239 WHERE t.`id` = 241 AND t.`name` = 'Isi Pensil'; -- Isi Pensil -> Pensil
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('pensil ', g.`name`), g.`type_id` = 244 WHERE t.`id` = 243 AND t.`name` = 'Pensil Warna'; -- Pensil Warna -> Alat Warna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cair ', g.`name`), g.`type_id` = 249 WHERE t.`id` = 245 AND t.`name` = 'Tipe-x Cair'; -- Tipe-x Cair -> Penghapus & Tipe-x
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kertas ', g.`name`), g.`type_id` = 249 WHERE t.`id` = 246 AND t.`name` = 'Tipe-x Kertas'; -- Tipe-x Kertas -> Penghapus & Tipe-x
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('cat air ', g.`name`), g.`type_id` = 244 WHERE t.`id` = 248 AND t.`name` = 'Cat Air'; -- Cat Air -> Alat Warna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('besi ', g.`name`), g.`type_id` = 251 WHERE t.`id` = 252 AND t.`name` = 'Penggaris Besi'; -- Penggaris Besi -> Penggaris
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('tinta ', g.`name`), g.`type_id` = 236 WHERE t.`id` = 253 AND t.`name` = 'Tinta Spidol'; -- Tinta Spidol -> Spidol
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kuas lukis ', g.`name`), g.`type_id` = 244 WHERE t.`id` = 254 AND t.`name` = 'Kuas Lukis'; -- Kuas Lukis -> Alat Warna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kapur ', g.`name`), g.`type_id` = 259 WHERE t.`id` = 257 AND t.`name` = 'Kapur Tulis'; -- Kapur Tulis -> Papan Tulis
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('double tape ', g.`name`), g.`type_id` = 232 WHERE t.`id` = 258 AND t.`name` = 'Double Tape'; -- Double Tape -> Lakban & Solasi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 260 WHERE t.`id` = 261 AND t.`name` = 'Lem'; -- Lem -> Lem
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 263 WHERE t.`id` = 262 AND t.`name` = 'Staples'; -- Staples -> Staples
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 255 WHERE t.`id` = 266 AND t.`name` = 'Cutter'; -- Cutter -> Gunting & Cutter
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 264 WHERE t.`id` = 267 AND t.`name` = 'Map'; -- Map -> Map & Amplop
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('paper bag ', g.`name`), g.`type_id` = 269 WHERE t.`id` = 268 AND t.`name` = 'Paper Bag'; -- Paper Bag -> Kertas Kado & Pita
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('hvs ', g.`name`), g.`type_id` = 292 WHERE t.`id` = 270 AND t.`name` = 'Kertas HVS'; -- Kertas HVS -> Kertas
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 289 WHERE t.`id` = 271 AND t.`name` = 'Stiker'; -- Stiker -> Label & Stiker
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('benang ', g.`name`), g.`type_id` = 272 WHERE t.`id` = 273 AND t.`name` = 'Benang'; -- Benang -> Alat Jahit
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('notebook ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 274 AND t.`name` = 'Notebook'; -- Notebook -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('isi ', g.`name`), g.`type_id` = 255 WHERE t.`id` = 275 AND t.`name` = 'Isi Cutter'; -- Isi Cutter -> Gunting & Cutter
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('double tape foam ', g.`name`), g.`type_id` = 232 WHERE t.`id` = 276 AND t.`name` = 'Double Tape Foam'; -- Double Tape Foam -> Lakban & Solasi
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sampul kertas ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 277 AND t.`name` = 'Sampul Buku Kertas'; -- Sampul Buku Kertas -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('stabilo ', g.`name`), g.`type_id` = 236 WHERE t.`id` = 278 AND t.`name` = 'Stabilo'; -- Stabilo -> Spidol
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sticky note ', g.`name`), g.`type_id` = 289 WHERE t.`id` = 279 AND t.`name` = 'Sticky Note'; -- Sticky Note -> Label & Stiker
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('sampul plastik ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 280 AND t.`name` = 'Sampul Buku Plastik'; -- Sampul Buku Plastik -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('karton ', g.`name`), g.`type_id` = 292 WHERE t.`id` = 282 AND t.`name` = 'Kertas Karton'; -- Kertas Karton -> Kertas
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kwitansi ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 283 AND t.`name` = 'Buku Kwitansi'; -- Buku Kwitansi -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('poster ', g.`name`), g.`type_id` = 233 WHERE t.`id` = 284 AND t.`name` = 'Poster'; -- Poster -> Buku
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('spidol ', g.`name`), g.`type_id` = 244 WHERE t.`id` = 285 AND t.`name` = 'Spidol Warna'; -- Spidol Warna -> Alat Warna
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('binder clip ', g.`name`), g.`type_id` = 263 WHERE t.`id` = 286 AND t.`name` = 'Binder Clip'; -- Binder Clip -> Staples
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('kartu ucapan ', g.`name`), g.`type_id` = 269 WHERE t.`id` = 288 AND t.`name` = 'Kartu Ucapan'; -- Kartu Ucapan -> Kertas Kado & Pita
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('folio ', g.`name`), g.`type_id` = 292 WHERE t.`id` = 290 AND t.`name` = 'Kertas Folio'; -- Kertas Folio -> Kertas
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`type_id` = 269 WHERE t.`id` = 291 AND t.`name` = 'Kertas Kado'; -- Kertas Kado -> Kertas Kado & Pita
UPDATE `goods` g JOIN `types` t ON t.`id` = g.`type_id` SET g.`name` = CONCAT('conditioner ', g.`name`), g.`type_id` = 73 WHERE t.`id` = 297 AND t.`name` = 'Sampo & Conditioner'; -- Sampo & Conditioner -> Sampo

-- 6. Jenis basis: ganti nama dan isi kategori induk
UPDATE `types` SET `name` = 'Tempat Sampah', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 1; -- dulu: Tempat Sampah Plastik
UPDATE `types` SET `name` = 'Lemari Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE') WHERE `id` = 2; -- dulu: Lemari Plastik Gantung
UPDATE `types` SET `name` = 'Mangkok Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 3; -- dulu: Mangkok Plastik
UPDATE `types` SET `name` = 'Lemari Besi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE') WHERE `id` = 4; -- dulu: Lemari Besi Gantung
UPDATE `types` SET `name` = 'Gelas Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 5; -- dulu: Gelas Plastik
UPDATE `types` SET `name` = 'Mangkok Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 6; -- dulu: Mangkok Kaca
UPDATE `types` SET `name` = 'Mangkok Keramik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 7; -- dulu: Mangkok Keramik
UPDATE `types` SET `name` = 'Mangkok Melamin', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 8; -- dulu: Mangkok Melamin
UPDATE `types` SET `name` = 'Toples Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 10; -- dulu: Toples Plastik
UPDATE `types` SET `name` = 'Toples Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 11; -- dulu: Toples Kaca
UPDATE `types` SET `name` = 'Piring Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 13; -- dulu: Piring Plastik
UPDATE `types` SET `name` = 'Piring Melamin', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 14; -- dulu: Piring Melamin
UPDATE `types` SET `name` = 'Piring Stainless', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 15; -- dulu: Piring Stainless Steel
UPDATE `types` SET `name` = 'Piring Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 18; -- dulu: Piring Kaca Saji
UPDATE `types` SET `name` = 'Piring Keramik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 20; -- dulu: Piring Keramik
UPDATE `types` SET `name` = 'Termos', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 24; -- dulu: Termos Plastik
UPDATE `types` SET `name` = 'Panci', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 27; -- dulu: Panci Aluminium
UPDATE `types` SET `name` = 'Selimut', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 28; -- dulu: Selimut Dewasa
UPDATE `types` SET `name` = 'Kasur & Selimut Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 29; -- dulu: Selimut Bayi
UPDATE `types` SET `name` = 'Laci Plastik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE') WHERE `id` = 30; -- dulu: Lemari Plastik Laci
UPDATE `types` SET `name` = 'Makroni & Stik Kiloan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'KiFOOD') WHERE `id` = 31; -- dulu: Snack Kiloan Pedas
UPDATE `types` SET `name` = 'Kue Kering Kiloan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'KiFOOD') WHERE `id` = 32; -- dulu: Snack Kiloan Manis
UPDATE `types` SET `name` = 'Keripik & Kerupuk Kiloan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'KiFOOD') WHERE `id` = 33; -- dulu: Snack Kiloan Asin
UPDATE `types` SET `name` = 'Deterjen Bubuk', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 34; -- dulu: Deterjen Bubuk
UPDATE `types` SET `name` = 'Deterjen Cair', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 35; -- dulu: Deterjen Cair
UPDATE `types` SET `name` = 'Sabun Mandi Batang', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 36; -- dulu: Sabun Mandi Batang
UPDATE `types` SET `name` = 'Sabun Mandi Cair', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 37; -- dulu: Sabun Mandi Cair
UPDATE `types` SET `name` = 'Sabun Cuci Piring', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 39; -- dulu: Sabun Cuci Piring Cream
UPDATE `types` SET `name` = 'Gelas Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 40; -- dulu: Gelas Kaca Kuping
UPDATE `types` SET `name` = 'Tampah', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 41; -- dulu: Tampah Plastik
UPDATE `types` SET `name` = 'Teko Listrik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 42; -- dulu: Teko Plastik
UPDATE `types` SET `name` = 'Teko Aluminium', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 43; -- dulu: Teko Alumunium
UPDATE `types` SET `name` = 'Wajan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 44; -- dulu: Wajan Alumunium
UPDATE `types` SET `name` = 'Rantang', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 46; -- dulu: Rantang Plastik
UPDATE `types` SET `name` = 'Magic Com', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 48; -- dulu: Magic Com
UPDATE `types` SET `name` = 'Tikar', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FLOORING') WHERE `id` = 49; -- dulu: Tikar Plastik
UPDATE `types` SET `name` = 'Bak & Baskom', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BUCKET') WHERE `id` = 50; -- dulu: Bak Karet
UPDATE `types` SET `name` = 'Ember', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BUCKET') WHERE `id` = 51; -- dulu: Ember Tutup Plastik
UPDATE `types` SET `name` = 'Teh', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 54; -- dulu: Teh Bubuk
UPDATE `types` SET `name` = 'Bed Cover', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 56; -- dulu: Bed Cover + Sprei
UPDATE `types` SET `name` = 'Sprei', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 57; -- dulu: Sprei
UPDATE `types` SET `name` = 'Bantal', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 58; -- dulu: Bantal
UPDATE `types` SET `name` = 'Sarung Bantal', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 59; -- dulu: Sarung Bantal
UPDATE `types` SET `name` = 'Blender', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 60; -- dulu: Blender
UPDATE `types` SET `name` = 'Chopper', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 61; -- dulu: Chopper
UPDATE `types` SET `name` = 'Slow Cooker', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 62; -- dulu: Slow Cooker
UPDATE `types` SET `name` = 'Setrika', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 63; -- dulu: Setrika
UPDATE `types` SET `name` = 'Dispenser', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 64; -- dulu: Dispenser
UPDATE `types` SET `name` = 'Mixer', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 65; -- dulu: Mixer
UPDATE `types` SET `name` = 'Lampu', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 66; -- dulu: Lampu
UPDATE `types` SET `name` = 'Sendok & Garpu', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 68; -- dulu: Garpu Stainless Steel
UPDATE `types` SET `name` = 'Sabun & Sampo Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 72; -- dulu: Sampo Bayi
UPDATE `types` SET `name` = 'Sampo', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 73; -- dulu: Sampo
UPDATE `types` SET `name` = 'Kompor 1 Tungku', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STOVE') WHERE `id` = 74; -- dulu: Kompor Cor 1 Tungku
UPDATE `types` SET `name` = 'Dandang', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 75; -- dulu: Dandang Alumunium
UPDATE `types` SET `name` = 'Bak Mandi Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 76; -- dulu: Bak Mandi Bayi Plastik
UPDATE `types` SET `name` = 'Dingklik', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'HOMEGOODS') WHERE `id` = 77; -- dulu: Dingklik Plastik
UPDATE `types` SET `name` = 'Sapu', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 79; -- dulu: Sapu Ijuk
UPDATE `types` SET `name` = 'Pengki', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 83; -- dulu: Pengki Plastik
UPDATE `types` SET `name` = 'Gayung', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BUCKET') WHERE `id` = 84; -- dulu: Gayung Plastik
UPDATE `types` SET `name` = 'Kontainer', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STORAGEBOX') WHERE `id` = 85; -- dulu: Kontainer Plastik
UPDATE `types` SET `name` = 'Permen & Cokelat', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 86; -- dulu: Permen
UPDATE `types` SET `name` = 'Gendongan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 87; -- dulu: Gendongan Bayi Samping
UPDATE `types` SET `name` = 'Kopi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 91; -- dulu: Kopi Bubuk
UPDATE `types` SET `name` = 'Susu & Yogurt', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 94; -- dulu: Susu Bubuk
UPDATE `types` SET `name` = 'Kompor 2 Tungku', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STOVE') WHERE `id` = 95; -- dulu: Kompor Stainless Steel 2 Tungku
UPDATE `types` SET `name` = 'Kipas Angin', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 97; -- dulu: Kipas Angin Listrik
UPDATE `types` SET `name` = 'Timbangan Digital', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 98; -- dulu: Timbangan Digital
UPDATE `types` SET `name` = 'Kabel', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 99; -- dulu: Kabel Charger
UPDATE `types` SET `name` = 'Headset', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 100; -- dulu: Headset
UPDATE `types` SET `name` = 'Kue Toples', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 101; -- dulu: Kue toples
UPDATE `types` SET `name` = 'Baterai', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 102; -- dulu: Baterai
UPDATE `types` SET `name` = 'Air Fryer', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 104; -- dulu: Air Fryer
UPDATE `types` SET `name` = 'Pompa Galon', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'ELECTRONIC') WHERE `id` = 105; -- dulu: Pompa Galon Elektrik
UPDATE `types` SET `name` = 'Guling', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 106; -- dulu: Guling
UPDATE `types` SET `name` = 'Kasur Busa', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 107; -- dulu: Kasur Busa
UPDATE `types` SET `name` = 'Gelas Keramik/Mug', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 110; -- dulu: Gelas Keramik/Mug
UPDATE `types` SET `name` = 'Cangkir + Lepek', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 111; -- dulu: Cangkir + Lepek Keramik
UPDATE `types` SET `name` = 'Teko Set Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'GLASSWARE') WHERE `id` = 113; -- dulu: Teko Set Gelas Kaca
UPDATE `types` SET `name` = 'Cotton Bud Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 117; -- dulu: Cutton Bud Bayi
UPDATE `types` SET `name` = 'Cotton Bud', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 118; -- dulu: Cutton Bud Dewasa
UPDATE `types` SET `name` = 'Popok Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 119; -- dulu: Popok Bayi
UPDATE `types` SET `name` = 'Pakaian Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 120; -- dulu: Setelan Anak Panjang
UPDATE `types` SET `name` = 'Handuk', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEDDING') WHERE `id` = 126; -- dulu: Handuk Dewasa
UPDATE `types` SET `name` = 'Parfum Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 127; -- dulu: Parfum Bayi
UPDATE `types` SET `name` = 'Bedak & Lotion Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 130; -- dulu: Baby Hair Lotion
UPDATE `types` SET `name` = 'Minyak Telon', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE') WHERE `id` = 134; -- dulu: Minyak Telon
UPDATE `types` SET `name` = 'Perlak', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 135; -- dulu: Perlak Bayi
UPDATE `types` SET `name` = 'Set Makan Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 136; -- dulu: Set Makan Bayi
UPDATE `types` SET `name` = 'Kaos Kaki', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 141; -- dulu: Kaos Kaki Pria
UPDATE `types` SET `name` = 'Topi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 148; -- dulu: Topi Anak
UPDATE `types` SET `name` = 'Popok Dewasa', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 149; -- dulu: Popok Dewasa
UPDATE `types` SET `name` = 'Botol & Dot', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 150; -- dulu: Dot Bayi
UPDATE `types` SET `name` = 'Tas Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 153; -- dulu: Tas Bayi
UPDATE `types` SET `name` = 'Tas Sekolah', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'SCHOOL') WHERE `id` = 154; -- dulu: Tas Sekolah
UPDATE `types` SET `name` = 'Tas', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FASHION') WHERE `id` = 155; -- dulu: Tas Wanita Sling
UPDATE `types` SET `name` = 'Kaos Tangan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 156; -- dulu: Kaos Tangan Dewasa
UPDATE `types` SET `name` = 'Hampers Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 163; -- dulu: Hampers Bayi
UPDATE `types` SET `name` = 'Mainan Bayi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BABYGEAR') WHERE `id` = 165; -- dulu: Mainan Bayi
UPDATE `types` SET `name` = 'Tisu', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 169; -- dulu: Tisu
UPDATE `types` SET `name` = 'Tisu Basah', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 170; -- dulu: Tisu Basah
UPDATE `types` SET `name` = 'Pembalut', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 171; -- dulu: Pembalut
UPDATE `types` SET `name` = 'Pantyliner', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 172; -- dulu: Pantyliner
UPDATE `types` SET `name` = 'Handbody Lotion', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 173; -- dulu: Handbody Lotion
UPDATE `types` SET `name` = 'Kapas', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 174; -- dulu: Kapas
UPDATE `types` SET `name` = 'Body Spray', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 175; -- dulu: Body Spray
UPDATE `types` SET `name` = 'Deodoran', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 178; -- dulu: Deo Roll
UPDATE `types` SET `name` = 'Vitamin Rambut', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 179; -- dulu: Vitamin Rambut
UPDATE `types` SET `name` = 'Perawatan Wajah', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 182; -- dulu: Face Wash
UPDATE `types` SET `name` = 'Conditioner', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 191; -- dulu: Hair Conditioner
UPDATE `types` SET `name` = 'Alat Cukur', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 192; -- dulu: Alat Cukur
UPDATE `types` SET `name` = 'Pelicin Pakaian', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 197; -- dulu: Pelicin Pakaian Refill
UPDATE `types` SET `name` = 'Pewangi Pakaian', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 202; -- dulu: Pewangi Pakaian Renceng
UPDATE `types` SET `name` = 'Pengharum Ruangan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 203; -- dulu: Pengharum Ruangan Gantung
UPDATE `types` SET `name` = 'Kamper', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 205; -- dulu: Kamper
UPDATE `types` SET `name` = 'Pembersih Kaca', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 206; -- dulu: Pembersih Kaca
UPDATE `types` SET `name` = 'Jerigen', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BUCKET') WHERE `id` = 207; -- dulu: Jerigen Putih
UPDATE `types` SET `name` = 'Mie Instan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'FOOD') WHERE `id` = 208; -- dulu: Mie Cup
UPDATE `types` SET `name` = 'Pengocok Telur', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 209; -- dulu: Pengocok Telur
UPDATE `types` SET `name` = 'Keranjang Pakaian', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BUCKET') WHERE `id` = 211; -- dulu: Keranjang Pakaian Plastik
UPDATE `types` SET `name` = 'Keranjang Serbaguna', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STORAGEBOX') WHERE `id` = 212; -- dulu: Keranjang Plastik
UPDATE `types` SET `name` = 'Sikat', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 213; -- dulu: Sikat
UPDATE `types` SET `name` = 'Centong Nasi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'COOKWARE') WHERE `id` = 214; -- dulu: Centong Nasi Plastik
UPDATE `types` SET `name` = 'Tempat Tisu', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE') WHERE `id` = 216; -- dulu: Tempat Tisu Plastik
UPDATE `types` SET `name` = 'Serbet', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 217; -- dulu: Serbet
UPDATE `types` SET `name` = 'Spons Cuci Piring', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 218; -- dulu: Spons Cuci Piring
UPDATE `types` SET `name` = 'Alat Pel', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 219; -- dulu: Alat Pel
UPDATE `types` SET `name` = 'Pel Refill', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 220; -- dulu: Alat Pel Refill
UPDATE `types` SET `name` = 'Pembersih Lantai', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 221; -- dulu: Pembersih Lantai
UPDATE `types` SET `name` = 'Kapur Barus', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 222; -- dulu: Kapur Barus
UPDATE `types` SET `name` = 'Kanebo', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANTOOL') WHERE `id` = 223; -- dulu: Kanebo
UPDATE `types` SET `name` = 'Karbol', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLEANING') WHERE `id` = 225; -- dulu: Karbol
UPDATE `types` SET `name` = 'Pasta Gigi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 226; -- dulu: Pasta Gigi
UPDATE `types` SET `name` = 'Sikat Gigi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 227; -- dulu: Sikat Gigi
UPDATE `types` SET `name` = 'Spons Mandi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BATH') WHERE `id` = 229; -- dulu: Spons Mandi
UPDATE `types` SET `name` = 'Lakban & Solasi', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 232; -- dulu: Solasi
UPDATE `types` SET `name` = 'Buku', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 233; -- dulu: Buku
UPDATE `types` SET `name` = 'Spidol', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 236; -- dulu: Spidol
UPDATE `types` SET `name` = 'Pensil', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 239; -- dulu: Pensil
UPDATE `types` SET `name` = 'Gembok', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'HOMEGOODS') WHERE `id` = 242; -- dulu: Gembok
UPDATE `types` SET `name` = 'Alat Warna', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 244; -- dulu: Crayon
UPDATE `types` SET `name` = 'Tempat Pensil', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 247; -- dulu: Tempat Pensil
UPDATE `types` SET `name` = 'Penghapus & Tipe-x', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 249; -- dulu: Penghapus
UPDATE `types` SET `name` = 'Rautan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 250; -- dulu: Rautan
UPDATE `types` SET `name` = 'Penggaris', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 251; -- dulu: Penggaris Plastik
UPDATE `types` SET `name` = 'Gunting & Cutter', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 255; -- dulu: Gunting
UPDATE `types` SET `name` = 'Gunting Kuku', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'BEAUTY') WHERE `id` = 256; -- dulu: Gunting Kuku
UPDATE `types` SET `name` = 'Papan Tulis', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'SCHOOL') WHERE `id` = 259; -- dulu: Papan Tulis
UPDATE `types` SET `name` = 'Lem', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 260; -- dulu: Lem Cair
UPDATE `types` SET `name` = 'Staples', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 263; -- dulu: Isi Staples
UPDATE `types` SET `name` = 'Map & Amplop', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 264; -- dulu: Amplop
UPDATE `types` SET `name` = 'Bolpen', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 265; -- dulu: Bolpen
UPDATE `types` SET `name` = 'Kertas Kado & Pita', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 269; -- dulu: Pita
UPDATE `types` SET `name` = 'Alat Jahit', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 272; -- dulu: Jarum Jahit
UPDATE `types` SET `name` = 'Bendera', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 281; -- dulu: Bendera
UPDATE `types` SET `name` = 'Label & Stiker', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 289; -- dulu: Label Harga
UPDATE `types` SET `name` = 'Kertas', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'STATIONERY') WHERE `id` = 292; -- dulu: Kertas Lipat
UPDATE `types` SET `name` = 'Gamis', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 293; -- dulu: Gamis Dewasa
UPDATE `types` SET `name` = 'Setelan', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 294; -- dulu: Setelan Dewasa Panjang
UPDATE `types` SET `name` = 'Daster', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING') WHERE `id` = 295; -- dulu: Daster
UPDATE `types` SET `name` = 'Mukena', `category_id` = (SELECT `id` FROM `categories` WHERE `code` = 'PRAY') WHERE `id` = 296; -- dulu: Mukena

-- 7. Jenis lama yang sudah digabung (sudah tidak punya barang): dinonaktifkan
UPDATE `types` SET `deleted_at` = NOW() WHERE `deleted_at` IS NULL AND `id` IN (9, 12, 16, 17, 19, 21, 22, 23, 25, 26, 38, 45, 47, 52, 53, 55, 67, 69, 70, 71, 78, 80, 81, 82, 88, 89, 90, 92, 93, 96, 103, 108, 109, 112, 114, 115, 116, 121, 122, 123, 124, 125, 128, 129, 131, 132, 133, 137, 138, 139, 140, 142, 143, 144, 145, 146, 147, 151, 152, 157, 158, 159, 160, 161, 162, 164, 166, 167, 168, 176, 177, 180, 181, 183, 184, 185, 186, 187, 188, 189, 190, 193, 194, 198, 199, 200, 201, 204, 210, 215, 224, 228, 231, 234, 235, 237, 238, 240, 241, 243, 245, 246, 248, 252, 253, 254, 257, 258, 261, 262, 266, 267, 268, 270, 271, 273, 274, 275, 276, 277, 278, 279, 280, 282, 283, 284, 285, 286, 287, 288, 290, 291, 297) AND NOT EXISTS (SELECT 1 FROM `goods` g WHERE g.`type_id` = `types`.`id`);

-- 8. Jenis baru (masih kosong; barangnya diisi di Tahap 3)
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-MAKANAN-BAYI', 'Makanan Bayi', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MAKANAN-BAYI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-AIR-MINERAL-GALON', 'Air Mineral & Galon', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-AIR-MINERAL-GALON');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-MINUMAN-KEMASAN', 'Minuman Kemasan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MINUMAN-KEMASAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-BISKUIT-WAFER', 'Biskuit & Wafer', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BISKUIT-WAFER');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-ROTI', 'Roti', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-ROTI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-SNACK-KEMASAN', 'Snack Kemasan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SNACK-KEMASAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-JELLY-AGAR', 'Jelly & Agar', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-JELLY-AGAR');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-KECAP-SAUS-SAMBAL', 'Kecap, Saus & Sambal', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KECAP-SAUS-SAMBAL');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOD'), 'K2-BUMBU-DAPUR', 'Bumbu Dapur', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BUMBU-DAPUR');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SEMBAKO'), 'K2-MINYAK-GORENG', 'Minyak Goreng', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MINYAK-GORENG');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SEMBAKO'), 'K2-GULA', 'Gula', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-GULA');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SEMBAKO'), 'K2-TEPUNG', 'Tepung', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TEPUNG');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SEMBAKO'), 'K2-GARAM', 'Garam', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-GARAM');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SEMBAKO'), 'K2-BAHAN-KUE', 'Bahan Kue', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BAHAN-KUE');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'KiFOOD'), 'K2-KACANG-KILOAN', 'Kacang Kiloan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KACANG-KILOAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'KiRICE'), 'K2-BERAS', 'Beras', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BERAS');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'MEDICINE'), 'K2-MINYAK-ANGIN-AROMATERAPI', 'Minyak Angin & Aromaterapi', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MINYAK-ANGIN-AROMATERAPI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'MEDICINE'), 'K2-PLESTER-KOYO', 'Plester & Koyo', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-PLESTER-KOYO');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'MEDICINE'), 'K2-OBAT', 'Obat', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-OBAT');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'MEDICINE'), 'K2-ALAT-KESEHATAN', 'Alat Kesehatan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-ALAT-KESEHATAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'BABYCARE'), 'K2-PASTA-GIGI-BAYI', 'Pasta Gigi Bayi', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-PASTA-GIGI-BAYI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'STORAGEBOX'), 'K2-BOX', 'Box', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BOX');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'STORAGEBOX'), 'K2-KRAT-INDUSTRI', 'Krat Industri', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KRAT-INDUSTRI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'HOMEGOODS'), 'K2-KURSI-PLASTIK', 'Kursi Plastik', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KURSI-PLASTIK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-KANTONG-PLASTIK', 'Kantong Plastik', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KANTONG-PLASTIK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-PLASTIK-PP-KLIP', 'Plastik PP & Klip', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-PLASTIK-PP-KLIP');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-TAS-HAJATAN', 'Tas Hajatan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TAS-HAJATAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-CUP-WADAH-SEKALI-PAKAI', 'Cup & Wadah Sekali Pakai', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-CUP-WADAH-SEKALI-PAKAI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-KERTAS-NASI-KERTAS-MINYAK', 'Kertas Nasi & Kertas Minyak', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KERTAS-NASI-KERTAS-MINYAK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-SARUNG-TANGAN-PLASTIK', 'Sarung Tangan Plastik', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SARUNG-TANGAN-PLASTIK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PLASTIC'), 'K2-KARUNG-TALI', 'Karung & Tali', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KARUNG-TALI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE'), 'K2-BOTOL-MINUM', 'Botol Minum', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BOTOL-MINUM');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE'), 'K2-TEMPAT-MAKAN', 'Tempat Makan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TEMPAT-MAKAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'TABLEWARE'), 'K2-TEKO-PLASTIK', 'Teko Plastik', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TEKO-PLASTIK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE'), 'K2-MEJA-KURSI-SET', 'Meja Kursi Set', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MEJA-KURSI-SET');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE'), 'K2-RAK-PIRING-BESI', 'Rak Piring Besi', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-RAK-PIRING-BESI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'STORAGE'), 'K2-JEMURAN-BESI', 'Jemuran Besi', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-JEMURAN-BESI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FLOORING'), 'K2-KARPET', 'Karpet', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KARPET');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FLOORING'), 'K2-KARPET-PUZZLE', 'Karpet Puzzle', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KARPET-PUZZLE');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FLOORING'), 'K2-KESET', 'Keset', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KESET');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING'), 'K2-BAJU-ANAK', 'Baju Anak', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BAJU-ANAK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING'), 'K2-CELANA-ANAK', 'Celana Anak', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-CELANA-ANAK');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'CLOTHING'), 'K2-KAOS-DALAM', 'Kaos Dalam', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-KAOS-DALAM');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOTWEAR'), 'K2-SANDAL', 'Sandal', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SANDAL');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FOOTWEAR'), 'K2-SEPATU', 'Sepatu', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SEPATU');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FASHION'), 'K2-DOMPET', 'Dompet', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-DOMPET');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FASHION'), 'K2-PAYUNG-JAS-HUJAN', 'Payung & Jas Hujan', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-PAYUNG-JAS-HUJAN');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FASHION'), 'K2-AKSESORI-RAMBUT', 'Aksesori Rambut', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-AKSESORI-RAMBUT');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FASHION'), 'K2-AKSESORI-HIJAB', 'Aksesori Hijab', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-AKSESORI-HIJAB');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'FASHION'), 'K2-MASKER', 'Masker', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-MASKER');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PRAY'), 'K2-SARUNG', 'Sarung', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SARUNG');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PRAY'), 'K2-SAJADAH', 'Sajadah', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-SAJADAH');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PRAY'), 'K2-PECI', 'Peci', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-PECI');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'PRAY'), 'K2-TASBIH', 'Tasbih', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TASBIH');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'GARDENING'), 'K2-POT', 'Pot', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-POT');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'GARDENING'), 'K2-TATAKAN-POT', 'Tatakan Pot', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-TATAKAN-POT');
INSERT INTO `types` (`category_id`, `code`, `name`, `created_at`) SELECT (SELECT `id` FROM `categories` WHERE `code` = 'SPORT'), 'K2-BULU-TANGKIS', 'Bulu Tangkis', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `types` t2 WHERE t2.`code` = 'K2-BULU-TANGKIS');

COMMIT;
