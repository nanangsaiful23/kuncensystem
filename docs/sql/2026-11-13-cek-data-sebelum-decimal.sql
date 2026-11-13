-- =====================================================================
-- CEK DATA sebelum kolom angka (varchar) diubah menjadi DECIMAL
--
-- HANYA SELECT: tidak mengubah data apa pun. Aman dijalankan di server.
-- Jalankan per bagian di phpMyAdmin (tab SQL) pada database kuncen.
--
-- Angka "murni" = hanya digit, boleh didahului minus, boleh satu titik
-- desimal (contoh 12500, 12500.50, -3.5). Selain itu dianggap kotor:
-- koma ribuan ("12,500"), titik ribuan ("12.500,50"), spasi, "Rp", dst.
-- =====================================================================


-- ---------- BAGIAN 1. Tipe kolom di database yang sedang dipakai ----------
-- Pastikan dulu mana yang benar-benar masih varchar/text.
SELECT table_name, column_name, data_type, column_type
  FROM information_schema.columns
 WHERE table_schema = DATABASE()
   AND (table_name, column_name) IN (
       ('accounts','balance'),
       ('delivery_fees','fee'),
       ('good_loading_details','price'),
       ('good_loading_details','selling_price'),
       ('good_loading_details','last_stock'),
       ('good_loadings','total_item_price'),
       ('good_prices','old_price'),
       ('good_prices','recent_price'),
       ('good_prices','old_buy_price'),
       ('good_prices','recent_buy_price'),
       ('piutang_payments','money'),
       ('stock_opname_details','old_stock'),
       ('stock_opname_details','new_stock'),
       ('stock_opname_details','total'),
       ('stock_opnames','total'),
       ('transactions','voucher_nominal'),
       ('units','quantity'),
       ('vouchers','nominal'),
       ('good_units','buy_price'),
       ('good_units','selling_price')
   )
 ORDER BY table_name, column_name;


-- ---------- BAGIAN 2. Ringkasan kekotoran per kolom ----------
-- bukan_angka_murni harus 0 sebelum ALTER TABLE.
-- teks_kosong_atau_NULL (''/'NULL') tidak berbahaya tapi harus diubah
-- jadi NULL dulu. nilai_terbesar/nilai_terkecil membantu melihat salah 100x.
SELECT 'accounts' AS tabel, 'balance' AS kolom, COUNT(*) AS total_baris,
       SUM(`balance` IS NULL) AS null_asli,
       SUM(TRIM(`balance`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`balance`) NOT IN ('', 'NULL', 'null') AND TRIM(`balance`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`balance` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `balance` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`balance` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `balance` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`balance` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `accounts`

UNION ALL
SELECT 'delivery_fees' AS tabel, 'fee' AS kolom, COUNT(*) AS total_baris,
       SUM(`fee` IS NULL) AS null_asli,
       SUM(TRIM(`fee`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`fee`) NOT IN ('', 'NULL', 'null') AND TRIM(`fee`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`fee` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `fee` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`fee` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `fee` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`fee` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `delivery_fees`

UNION ALL
SELECT 'good_loading_details' AS tabel, 'price' AS kolom, COUNT(*) AS total_baris,
       SUM(`price` IS NULL) AS null_asli,
       SUM(TRIM(`price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`price`) NOT IN ('', 'NULL', 'null') AND TRIM(`price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_loading_details`

UNION ALL
SELECT 'good_loading_details' AS tabel, 'selling_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`selling_price` IS NULL) AS null_asli,
       SUM(TRIM(`selling_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`selling_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`selling_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`selling_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `selling_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`selling_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `selling_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`selling_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_loading_details`

UNION ALL
SELECT 'good_loading_details' AS tabel, 'last_stock' AS kolom, COUNT(*) AS total_baris,
       SUM(`last_stock` IS NULL) AS null_asli,
       SUM(TRIM(`last_stock`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`last_stock`) NOT IN ('', 'NULL', 'null') AND TRIM(`last_stock`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`last_stock` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `last_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`last_stock` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `last_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`last_stock` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_loading_details`

UNION ALL
SELECT 'good_loadings' AS tabel, 'total_item_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`total_item_price` IS NULL) AS null_asli,
       SUM(TRIM(`total_item_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`total_item_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`total_item_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`total_item_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `total_item_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total_item_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `total_item_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total_item_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_loadings`

UNION ALL
SELECT 'good_prices' AS tabel, 'old_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`old_price` IS NULL) AS null_asli,
       SUM(TRIM(`old_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`old_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`old_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`old_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `old_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `old_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_prices`

UNION ALL
SELECT 'good_prices' AS tabel, 'recent_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`recent_price` IS NULL) AS null_asli,
       SUM(TRIM(`recent_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`recent_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`recent_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`recent_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `recent_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`recent_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `recent_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`recent_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_prices`

UNION ALL
SELECT 'good_prices' AS tabel, 'old_buy_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`old_buy_price` IS NULL) AS null_asli,
       SUM(TRIM(`old_buy_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`old_buy_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`old_buy_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`old_buy_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `old_buy_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_buy_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `old_buy_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_buy_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_prices`

UNION ALL
SELECT 'good_prices' AS tabel, 'recent_buy_price' AS kolom, COUNT(*) AS total_baris,
       SUM(`recent_buy_price` IS NULL) AS null_asli,
       SUM(TRIM(`recent_buy_price`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`recent_buy_price`) NOT IN ('', 'NULL', 'null') AND TRIM(`recent_buy_price`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`recent_buy_price` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `recent_buy_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`recent_buy_price` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `recent_buy_price` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`recent_buy_price` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `good_prices`

UNION ALL
SELECT 'piutang_payments' AS tabel, 'money' AS kolom, COUNT(*) AS total_baris,
       SUM(`money` IS NULL) AS null_asli,
       SUM(TRIM(`money`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`money`) NOT IN ('', 'NULL', 'null') AND TRIM(`money`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`money` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `money` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`money` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `money` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`money` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `piutang_payments`

UNION ALL
SELECT 'stock_opname_details' AS tabel, 'old_stock' AS kolom, COUNT(*) AS total_baris,
       SUM(`old_stock` IS NULL) AS null_asli,
       SUM(TRIM(`old_stock`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`old_stock`) NOT IN ('', 'NULL', 'null') AND TRIM(`old_stock`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`old_stock` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `old_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_stock` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `old_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`old_stock` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `stock_opname_details`

UNION ALL
SELECT 'stock_opname_details' AS tabel, 'new_stock' AS kolom, COUNT(*) AS total_baris,
       SUM(`new_stock` IS NULL) AS null_asli,
       SUM(TRIM(`new_stock`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`new_stock`) NOT IN ('', 'NULL', 'null') AND TRIM(`new_stock`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`new_stock` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `new_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`new_stock` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `new_stock` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`new_stock` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `stock_opname_details`

UNION ALL
SELECT 'stock_opname_details' AS tabel, 'total' AS kolom, COUNT(*) AS total_baris,
       SUM(`total` IS NULL) AS null_asli,
       SUM(TRIM(`total`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`total`) NOT IN ('', 'NULL', 'null') AND TRIM(`total`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`total` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `total` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `total` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `stock_opname_details`

UNION ALL
SELECT 'stock_opnames' AS tabel, 'total' AS kolom, COUNT(*) AS total_baris,
       SUM(`total` IS NULL) AS null_asli,
       SUM(TRIM(`total`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`total`) NOT IN ('', 'NULL', 'null') AND TRIM(`total`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`total` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `total` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `total` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`total` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `stock_opnames`

UNION ALL
SELECT 'transactions' AS tabel, 'voucher_nominal' AS kolom, COUNT(*) AS total_baris,
       SUM(`voucher_nominal` IS NULL) AS null_asli,
       SUM(TRIM(`voucher_nominal`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`voucher_nominal`) NOT IN ('', 'NULL', 'null') AND TRIM(`voucher_nominal`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`voucher_nominal` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `voucher_nominal` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`voucher_nominal` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `voucher_nominal` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`voucher_nominal` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `transactions`

UNION ALL
SELECT 'units' AS tabel, 'quantity' AS kolom, COUNT(*) AS total_baris,
       SUM(`quantity` IS NULL) AS null_asli,
       SUM(TRIM(`quantity`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`quantity`) NOT IN ('', 'NULL', 'null') AND TRIM(`quantity`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`quantity` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `quantity` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`quantity` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `quantity` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`quantity` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `units`

UNION ALL
SELECT 'vouchers' AS tabel, 'nominal' AS kolom, COUNT(*) AS total_baris,
       SUM(`nominal` IS NULL) AS null_asli,
       SUM(TRIM(`nominal`) IN ('', 'NULL', 'null')) AS teks_kosong_atau_NULL,
       SUM(TRIM(`nominal`) NOT IN ('', 'NULL', 'null') AND TRIM(`nominal`) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$') AS bukan_angka_murni,
       SUM(`nominal` LIKE '%,%') AS ada_koma,
       MAX(CASE WHEN `nominal` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`nominal` AS DECIMAL(24,4)) END) AS nilai_terbesar,
       MIN(CASE WHEN `nominal` REGEXP '^-?[0-9]+([.][0-9]+)?$' THEN CAST(`nominal` AS DECIMAL(24,4)) END) AS nilai_terkecil
  FROM `vouchers`
;


-- ---------- BAGIAN 3. Baris kotor (contoh, maks 200) ----------
-- Salin blok untuk kolom lain yang bukan_angka_murni-nya > 0 di Bagian 2,
-- lalu ganti nama tabel dan kolomnya. Dua blok di bawah untuk yang paling
-- rawan (sumber bug 100x).

SELECT id, good_loading_id, good_unit_id, price, selling_price, created_at
  FROM good_loading_details
 WHERE TRIM(price) NOT IN ('', 'NULL', 'null')
   AND TRIM(price) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
 ORDER BY id DESC LIMIT 200;

SELECT id, good_unit_id, old_price, recent_price, old_buy_price, recent_buy_price, created_at
  FROM good_prices
 WHERE TRIM(recent_price) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
    OR TRIM(old_price) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
    OR TRIM(recent_buy_price) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
    OR TRIM(old_buy_price) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
 ORDER BY id DESC LIMIT 200;


-- ---------- BAGIAN 4. Harga yang kemungkinan salah 100x ----------
-- Harga beli di loading yang lebih dari 20x harga master satuannya.
SELECT d.id, d.good_loading_id, d.good_unit_id, d.price AS harga_loading,
       gu.buy_price AS harga_master, g.name
  FROM good_loading_details d
  JOIN good_units gu ON gu.id = d.good_unit_id
  JOIN goods g ON g.id = gu.good_id
 WHERE d.price REGEXP '^-?[0-9]+([.][0-9]+)?$'
   AND gu.buy_price > 0
   AND CAST(d.price AS DECIMAL(24,4)) > gu.buy_price * 20
 ORDER BY d.id DESC LIMIT 200;


-- ---------- BAGIAN 5. Duplikat barang + satuan di good_units ----------
-- Harus kosong sebelum menambah unique index (good_id, unit_id).
-- Baris soft-deleted ikut dihitung karena index tidak membedakannya.
SELECT good_id, unit_id, COUNT(*) AS jumlah,
       SUM(deleted_at IS NULL) AS aktif,
       GROUP_CONCAT(id ORDER BY id) AS id_good_units
  FROM good_units
 GROUP BY good_id, unit_id
HAVING COUNT(*) > 1
 ORDER BY jumlah DESC
 LIMIT 200;


-- ---------- BAGIAN 6. Satuan: isi (units.quantity) ----------
-- 6a. Isi yang bukan angka murni atau <= 0.
SELECT id, code, name, quantity
  FROM units
 WHERE TRIM(quantity) NOT REGEXP '^-?[0-9]+([.][0-9]+)?$'
    OR (TRIM(quantity) REGEXP '^-?[0-9]+([.][0-9]+)?$' AND CAST(quantity AS DECIMAL(24,4)) <= 0);

-- 6b. Isi dengan lebih dari 4 angka desimal (terpotong di DECIMAL(12,4)).
SELECT id, code, name, quantity
  FROM units
 WHERE quantity REGEXP '[.][0-9]{5,}';

-- 6c. Barang tanpa satuan berisi 1 (memicu jalur fallback
--     getPcsSellingPrice, termasuk dd() di kode).
SELECT g.id, g.code, g.name
  FROM goods g
 WHERE g.deleted_at IS NULL
   AND NOT EXISTS (
       SELECT 1 FROM good_units gu
         JOIN units u ON u.id = gu.unit_id
        WHERE gu.good_id = g.id AND gu.deleted_at IS NULL
          AND u.quantity REGEXP '^-?[0-9]+([.][0-9]+)?$'
          AND CAST(u.quantity AS DECIMAL(24,4)) = 1)
 LIMIT 200;
