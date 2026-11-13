-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_13_000001_convert_numeric_columns_to_decimal.php
--
-- Mengubah kolom angka yang tersimpan sebagai varchar menjadi DECIMAL.
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
--
-- WAJIB sebelum menjalankan:
--   1. Backup database.
--   2. Jalankan docs/sql/2026-11-13-cek-data-sebelum-decimal.sql (Bagian 2):
--      kolom bukan_angka_murni harus 0 semua. Kalau tidak, bersihkan dulu.
--
-- Mode STRICT di bawah membuat ALTER gagal dengan error bila ada nilai
-- yang bukan angka (tanpa itu MySQL bisa diam-diam menjadikannya 0).
-- Kalau ALTER tertentu gagal, kolom itu tetap varchar; perbaiki datanya lalu
-- ulangi dari query yang gagal. Query sebelumnya aman diulang.
-- Setelah semua sukses, jalankan query terakhir (catat migrasi).

SET SESSION sql_mode = CONCAT(@@sql_mode, ',STRICT_ALL_TABLES');

-- ---------- Uang: DECIMAL(14,2) ----------
UPDATE `accounts` SET `balance` = NULL WHERE TRIM(`balance`) IN ('', 'NULL', 'null');
UPDATE `accounts` SET `balance` = TRIM(`balance`) WHERE `balance` <> TRIM(`balance`);
ALTER TABLE `accounts` MODIFY `balance` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `delivery_fees` SET `fee` = NULL WHERE TRIM(`fee`) IN ('', 'NULL', 'null');
UPDATE `delivery_fees` SET `fee` = TRIM(`fee`) WHERE `fee` <> TRIM(`fee`);
ALTER TABLE `delivery_fees` MODIFY `fee` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_loading_details` SET `price` = NULL WHERE TRIM(`price`) IN ('', 'NULL', 'null');
UPDATE `good_loading_details` SET `price` = TRIM(`price`) WHERE `price` <> TRIM(`price`);
ALTER TABLE `good_loading_details` MODIFY `price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_loading_details` SET `selling_price` = NULL WHERE TRIM(`selling_price`) IN ('', 'NULL', 'null');
UPDATE `good_loading_details` SET `selling_price` = TRIM(`selling_price`) WHERE `selling_price` <> TRIM(`selling_price`);
ALTER TABLE `good_loading_details` MODIFY `selling_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_loadings` SET `total_item_price` = NULL WHERE TRIM(`total_item_price`) IN ('', 'NULL', 'null');
UPDATE `good_loadings` SET `total_item_price` = TRIM(`total_item_price`) WHERE `total_item_price` <> TRIM(`total_item_price`);
ALTER TABLE `good_loadings` MODIFY `total_item_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_prices` SET `old_price` = NULL WHERE TRIM(`old_price`) IN ('', 'NULL', 'null');
UPDATE `good_prices` SET `old_price` = TRIM(`old_price`) WHERE `old_price` <> TRIM(`old_price`);
ALTER TABLE `good_prices` MODIFY `old_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_prices` SET `recent_price` = NULL WHERE TRIM(`recent_price`) IN ('', 'NULL', 'null');
UPDATE `good_prices` SET `recent_price` = TRIM(`recent_price`) WHERE `recent_price` <> TRIM(`recent_price`);
ALTER TABLE `good_prices` MODIFY `recent_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_prices` SET `old_buy_price` = NULL WHERE TRIM(`old_buy_price`) IN ('', 'NULL', 'null');
UPDATE `good_prices` SET `old_buy_price` = TRIM(`old_buy_price`) WHERE `old_buy_price` <> TRIM(`old_buy_price`);
ALTER TABLE `good_prices` MODIFY `old_buy_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `good_prices` SET `recent_buy_price` = NULL WHERE TRIM(`recent_buy_price`) IN ('', 'NULL', 'null');
UPDATE `good_prices` SET `recent_buy_price` = TRIM(`recent_buy_price`) WHERE `recent_buy_price` <> TRIM(`recent_buy_price`);
ALTER TABLE `good_prices` MODIFY `recent_buy_price` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `piutang_payments` SET `money` = NULL WHERE TRIM(`money`) IN ('', 'NULL', 'null');
UPDATE `piutang_payments` SET `money` = TRIM(`money`) WHERE `money` <> TRIM(`money`);
ALTER TABLE `piutang_payments` MODIFY `money` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `transactions` SET `voucher_nominal` = NULL WHERE TRIM(`voucher_nominal`) IN ('', 'NULL', 'null');
UPDATE `transactions` SET `voucher_nominal` = TRIM(`voucher_nominal`) WHERE `voucher_nominal` <> TRIM(`voucher_nominal`);
ALTER TABLE `transactions` MODIFY `voucher_nominal` DECIMAL(14,2) NULL DEFAULT NULL;

UPDATE `vouchers` SET `nominal` = NULL WHERE TRIM(`nominal`) IN ('', 'NULL', 'null');
UPDATE `vouchers` SET `nominal` = TRIM(`nominal`) WHERE `nominal` <> TRIM(`nominal`);
ALTER TABLE `vouchers` MODIFY `nominal` DECIMAL(14,2) NULL DEFAULT NULL;

-- ---------- Jumlah / stok / isi satuan: DECIMAL(14,4) ----------
UPDATE `good_loading_details` SET `last_stock` = NULL WHERE TRIM(`last_stock`) IN ('', 'NULL', 'null');
UPDATE `good_loading_details` SET `last_stock` = TRIM(`last_stock`) WHERE `last_stock` <> TRIM(`last_stock`);
ALTER TABLE `good_loading_details` MODIFY `last_stock` DECIMAL(14,4) NULL DEFAULT NULL;

UPDATE `stock_opname_details` SET `old_stock` = NULL WHERE TRIM(`old_stock`) IN ('', 'NULL', 'null');
UPDATE `stock_opname_details` SET `old_stock` = TRIM(`old_stock`) WHERE `old_stock` <> TRIM(`old_stock`);
ALTER TABLE `stock_opname_details` MODIFY `old_stock` DECIMAL(14,4) NULL DEFAULT NULL;

UPDATE `stock_opname_details` SET `new_stock` = NULL WHERE TRIM(`new_stock`) IN ('', 'NULL', 'null');
UPDATE `stock_opname_details` SET `new_stock` = TRIM(`new_stock`) WHERE `new_stock` <> TRIM(`new_stock`);
ALTER TABLE `stock_opname_details` MODIFY `new_stock` DECIMAL(14,4) NULL DEFAULT NULL;

UPDATE `stock_opname_details` SET `total` = NULL WHERE TRIM(`total`) IN ('', 'NULL', 'null');
UPDATE `stock_opname_details` SET `total` = TRIM(`total`) WHERE `total` <> TRIM(`total`);
ALTER TABLE `stock_opname_details` MODIFY `total` DECIMAL(14,4) NULL DEFAULT NULL;

UPDATE `stock_opnames` SET `total` = NULL WHERE TRIM(`total`) IN ('', 'NULL', 'null');
UPDATE `stock_opnames` SET `total` = TRIM(`total`) WHERE `total` <> TRIM(`total`);
ALTER TABLE `stock_opnames` MODIFY `total` DECIMAL(14,4) NULL DEFAULT NULL;

UPDATE `units` SET `quantity` = NULL WHERE TRIM(`quantity`) IN ('', 'NULL', 'null');
UPDATE `units` SET `quantity` = TRIM(`quantity`) WHERE `quantity` <> TRIM(`quantity`);
ALTER TABLE `units` MODIFY `quantity` DECIMAL(14,4) NULL DEFAULT NULL;

-- ---------- Catat migrasi sebagai sudah dijalankan ----------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_13_000001_convert_numeric_columns_to_decimal' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan (nilai kembali jadi teks, mis. '1500.00'):
--   ALTER TABLE <tabel> MODIFY <kolom> VARCHAR(255) NULL DEFAULT NULL;   (per kolom di atas)
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_13_000001_convert_numeric_columns_to_decimal';
