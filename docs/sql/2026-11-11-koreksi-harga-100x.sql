-- =====================================================================
-- Koreksi harga beli yang tersimpan 100x (bug titik/koma, 31 Oktober 2026)
--
-- 1. Mie goreng sukses isi 2 rasa aceh  (loading 8342)
--    harga pcs tersimpan 350.458,4  -> seharusnya 3.504,58
--    harga master barang sudah dibetulkan 4 Nov (3.600), tapi jurnal
--    "Laba kenaikan harga" 12.144.783,70 hanya dibalik 3.815.442,40.
--    Sisa laba palsu di Persediaan: 8.326.891,58
-- 2. GM-909 KAEMA (HS-09)  (loading 8350)
--    harga Gross tersimpan 18.999.600 -> seharusnya 189.996 (harga master
--    barang MASIH salah). Jurnal laba palsu: 3.134.934
--
-- Aman dijalankan di lokal dan di server, dan aman dijalankan dua kali:
-- setiap UPDATE hanya mengubah baris yang nilainya masih salah, jurnal
-- koreksi tidak ditambah kalau sudah ada.
-- Jalankan semuanya sekaligus di phpMyAdmin (tab SQL) pada database kuncen.
-- Backup dulu sebelum menjalankan di server.
-- =====================================================================

START TRANSACTION;

-- ---------- 1. Mie goreng ----------
-- baris loading (jumlahnya 0, hanya harga; tidak mengubah stok)
UPDATE good_loading_details SET price = '3504.58'
 WHERE good_loading_id = 8342 AND good_unit_id = 7907 AND price + 0 = 350458.4;
UPDATE good_loading_details SET price = '84110.02'
 WHERE good_loading_id = 8342 AND good_unit_id = 8402 AND price + 0 > 8411000;

-- jurnal koreksi: buang sisa laba palsu dari Persediaan
INSERT INTO journals (type, type_id, journal_date, name, debit_account_id, debit, credit_account_id, credit, created_at, updated_at)
SELECT 'other_payment', NULL, CURDATE(),
       'Koreksi harga salah ketik 100x Mie goreng sukses isi 2 rasa aceh id good unit 7907 (loading 8342, 31 Oktober 2026)',
       (SELECT id FROM accounts WHERE code = '5215' AND deleted_at IS NULL LIMIT 1), 8326891.58,
       (SELECT id FROM accounts WHERE code = '1141' AND deleted_at IS NULL LIMIT 1), 8326891.58,
       NOW(), NOW()
  FROM DUAL
 WHERE EXISTS (SELECT 1 FROM journals WHERE deleted_at IS NULL AND debit + 0 = 12144783.70
                  AND name LIKE 'Laba kenaikan harga barang Mie goreng sukses isi 2 rasa aceh id good unit 7907%')
   AND NOT EXISTS (SELECT 1 FROM journals WHERE deleted_at IS NULL
                  AND name LIKE 'Koreksi harga salah ketik 100x Mie goreng%');

-- ---------- 2. GM-909 KAEMA ----------
-- harga master barang satuan Gross (144 pcs): sama dengan 12 x harga Lusin 15.833
UPDATE good_units SET buy_price = 189996, updated_at = NOW()
 WHERE id = 8373 AND buy_price + 0 = 18999600;
UPDATE good_loading_details SET price = '189996'
 WHERE good_loading_id = 8350 AND good_unit_id = 8373 AND price + 0 = 18999600;

-- jurnal koreksi: harga sebenarnya tidak naik, jadi seluruh laba palsu dibalik
INSERT INTO journals (type, type_id, journal_date, name, debit_account_id, debit, credit_account_id, credit, created_at, updated_at)
SELECT 'other_payment', NULL, CURDATE(),
       'Koreksi harga salah ketik 100x GM-909 KAEMA (HS-09) id good unit 8373 (loading 8350, 31 Oktober 2026)',
       (SELECT id FROM accounts WHERE code = '5215' AND deleted_at IS NULL LIMIT 1), 3134934.00,
       (SELECT id FROM accounts WHERE code = '1141' AND deleted_at IS NULL LIMIT 1), 3134934.00,
       NOW(), NOW()
  FROM DUAL
 WHERE EXISTS (SELECT 1 FROM journals WHERE deleted_at IS NULL AND debit + 0 = 3134934.00
                  AND name LIKE 'Laba kenaikan harga barang GM-909 KAEMA (HS-09) id good unit 8373%')
   AND NOT EXISTS (SELECT 1 FROM journals WHERE deleted_at IS NULL
                  AND name LIKE 'Koreksi harga salah ketik 100x GM-909%');

COMMIT;

-- ---------- Cek hasil (boleh dijalankan terpisah) ----------
SELECT id, journal_date, name, debit FROM journals
 WHERE name LIKE 'Koreksi harga salah ketik 100x%' AND deleted_at IS NULL;
SELECT gu.id, u.name AS satuan, gu.buy_price, gu.selling_price
  FROM good_units gu JOIN units u ON u.id = gu.unit_id WHERE gu.id IN (7907, 8402, 8373);
