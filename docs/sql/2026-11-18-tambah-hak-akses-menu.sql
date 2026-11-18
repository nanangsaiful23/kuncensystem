-- Pengganti:
--   php artisan migrate --path=database/migrations/2026_11_18_000002_create_menu_permissions_table.php
--
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database toko.
-- Menambah 1 tabel untuk halaman Pengaturan > Hak Akses Menu.
-- Tidak ada data lama yang diubah. Selama tabel ini kosong, akses menu
-- sama persis dengan aturan lama.

CREATE TABLE IF NOT EXISTS `menu_permissions` (
  `role` varchar(20) NOT NULL,
  `menu_key` varchar(60) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`role`, `menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catat migrasi sebagai sudah dijalankan, supaya `php artisan migrate`
-- di kemudian hari tidak mencoba menjalankannya lagi.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT x.`migration`, x.`batch`
FROM (
    SELECT '2026_11_18_000002_create_menu_permissions_table' AS `migration`,
           COALESCE(MAX(`batch`), 0) + 1 AS `batch`
    FROM `migrations`
) x
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` m2 WHERE m2.`migration` = x.`migration`
);

-- Untuk membatalkan:
--   DROP TABLE `menu_permissions`;
--   DELETE FROM `migrations` WHERE `migration` = '2026_11_18_000002_create_menu_permissions_table';
