-- Mempercepat simpan transaksi (tanpa terminal): jalankan di phpMyAdmin, tab SQL, pada database aplikasi.
-- Hanya menambah indeks, tidak mengubah data. Aman dijalankan ulang (IF NOT EXISTS, MariaDB 10.1.4+).
-- Untuk MySQL biasa (bukan MariaDB): hapus tulisan "IF NOT EXISTS" dan jalankan satu per satu.

-- 1. Pencarian jurnal harian saat simpan (type + tanggal)
ALTER TABLE `journals` ADD INDEX IF NOT EXISTS `idx_journals_type_date` (`type`, `journal_date`);
ALTER TABLE `journals` ADD INDEX IF NOT EXISTS `idx_journals_date` (`journal_date`);

-- 2. Perhitungan stok dari riwayat (penjualan & loading)
ALTER TABLE `transaction_details` ADD INDEX IF NOT EXISTS `idx_td_stock` (`good_unit_id`, `deleted_at`, `type`, `real_quantity`, `transaction_id`);
ALTER TABLE `good_loading_details` ADD INDEX IF NOT EXISTS `idx_gld_stock` (`good_unit_id`, `deleted_at`, `real_quantity`, `good_loading_id`);
ALTER TABLE `transactions` ADD INDEX IF NOT EXISTS `idx_trx_deleted_at` (`deleted_at`);
ALTER TABLE `good_loadings` ADD INDEX IF NOT EXISTS `idx_gl_deleted_at` (`deleted_at`);

-- 3. Catat di tabel migrations supaya nanti "php artisan migrate" tidak mengulanginya
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.name, (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` x)
FROM (SELECT '2026_11_12_000002_add_journal_date_indexes' AS name
      UNION ALL SELECT '2026_11_12_000003_add_stock_history_indexes') m
WHERE m.name NOT IN (SELECT `migration` FROM `migrations`);
