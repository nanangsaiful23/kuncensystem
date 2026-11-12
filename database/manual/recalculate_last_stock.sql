-- Hitung ulang stok semua barang dari riwayat (total loading - total transaksi).
-- Sama dengan StockService / perintah stock:sync. Jalankan di phpMyAdmin > tab SQL.
-- Retur bertipe 'retur' tidak dihitung, loading/transaksi yang sudah dihapus tidak dihitung.

-- ========== LANGKAH 1: LIHAT DULU (tidak mengubah data) ==========
-- Barang yang stok tersimpannya berbeda dari hasil hitung.
SELECT * FROM (
SELECT g.id, g.code, g.name,
       g.last_stock AS stok_tersimpan,
       ROUND(ROUND(COALESCE(l.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2)
           - ROUND(COALESCE(s.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2),2) AS stok_hitung
FROM goods g
LEFT JOIN (SELECT gu.good_id, SUM(d.real_quantity) q
           FROM good_loading_details d
           JOIN good_units gu ON gu.id = d.good_unit_id
           JOIN good_loadings gl ON gl.id = d.good_loading_id
           WHERE d.deleted_at IS NULL AND gl.deleted_at IS NULL
           GROUP BY gu.good_id) l ON l.good_id = g.id
LEFT JOIN (SELECT gu.good_id, SUM(d.real_quantity) q
           FROM transaction_details d
           JOIN good_units gu ON gu.id = d.good_unit_id
           JOIN transactions t ON t.id = d.transaction_id
           WHERE d.deleted_at IS NULL AND t.deleted_at IS NULL AND COALESCE(d.type,'') <> 'retur'
           GROUP BY gu.good_id) s ON s.good_id = g.id
LEFT JOIN good_units bu ON bu.id = g.base_unit_id AND bu.deleted_at IS NULL
LEFT JOIN units bun ON bun.id = bu.unit_id
) h
WHERE ABS(h.stok_tersimpan - h.stok_hitung) > 0.001
ORDER BY h.name;


-- ========== LANGKAH 2: SIMPAN HASIL HITUNG (mengubah goods.last_stock) ==========
-- Jalankan hanya setelah langkah 1 terlihat wajar. Mengisi total_loading, total_transaction, last_stock.
UPDATE goods g
LEFT JOIN (SELECT gu.good_id, SUM(d.real_quantity) q
           FROM good_loading_details d
           JOIN good_units gu ON gu.id = d.good_unit_id
           JOIN good_loadings gl ON gl.id = d.good_loading_id
           WHERE d.deleted_at IS NULL AND gl.deleted_at IS NULL
           GROUP BY gu.good_id) l ON l.good_id = g.id
LEFT JOIN (SELECT gu.good_id, SUM(d.real_quantity) q
           FROM transaction_details d
           JOIN good_units gu ON gu.id = d.good_unit_id
           JOIN transactions t ON t.id = d.transaction_id
           WHERE d.deleted_at IS NULL AND t.deleted_at IS NULL AND COALESCE(d.type,'') <> 'retur'
           GROUP BY gu.good_id) s ON s.good_id = g.id
LEFT JOIN good_units bu ON bu.id = g.base_unit_id AND bu.deleted_at IS NULL
LEFT JOIN units bun ON bun.id = bu.unit_id
SET g.total_loading     = ROUND(COALESCE(l.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2),
    g.total_transaction = ROUND(COALESCE(s.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2),
    g.last_stock        = ROUND(COALESCE(l.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2)
                        - ROUND(COALESCE(s.q,0)/GREATEST(COALESCE(CAST(bun.quantity AS DECIMAL(18,6)),1),0.000001),2);
