<?php

namespace Tests\Feature\Stock;

/**
 * Tabel cash_closings (Tutup Kasir), reconciliation_checks (Cek Kecocokan),
 * stock_minus_logs & action_logs (penyebab stok minus, riwayat edit/hapus)
 * dibuat sekali dari file SQL di docs/sql lewat koneksi PDO terpisah, sebelum
 * transaksi tes dimulai. DDL di koneksi tes akan meng-commit transaksi tes,
 * sehingga data tes tertinggal di database.
 */
trait TabelTutupKasir
{
    private static bool $tabelTutupKasirSiap = false;

    protected function siapkanTabelTutupKasir(): void
    {
        if (self::$tabelTutupKasirSiap) {
            return;
        }
        self::$tabelTutupKasirSiap = true;

        try {
            $pdo = new \PDO(
                'mysql:host=' . (getenv('TEST_DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('TEST_DB_PORT') ?: '3306') . ';dbname=' . (getenv('TEST_DB_DATABASE') ?: 'kuncen_test'),
                getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: ''
            );
        } catch (\PDOException $e) {
            return; // Database tes tidak ada: StockTestCase::setUp() akan melewati tes.
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if (! in_array('accounts', $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN))) {
            $pdo->exec(file_get_contents(dirname(__DIR__, 3) . '/database/schema/kuncen-schema.sql'));
        }

        // Selalu buat ulang dari file SQL terbaru (database tes saja).
        foreach (['cash_closings', 'reconciliation_checks', 'stock_minus_logs', 'action_logs'] as $tabel) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $tabel . '`');
        }
        $punyaPembuat = $pdo->query("show columns from journals like 'created_by_role'")->fetch() !== false;
        foreach (['2026-11-24-tutup-kasir.sql', '2026-11-26-cek-kecocokan.sql', '2026-11-26-catat-stok-minus.sql'] as $file) {
            $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__, 3) . '/docs/sql/' . $file));
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if (str_contains($statement, '`migrations`') || ($punyaPembuat && str_starts_with($statement, 'ALTER TABLE `journals`'))) {
                    continue;
                }
                $pdo->exec($statement);
            }
        }
        // Laci asal uang loading cash (docs/sql/2026-11-28-laci-loading-kas.sql); kolomnya tetap ada di database tes.
        if ($pdo->query("show columns from good_loadings like 'cash_drawer'")->fetch() === false) {
            $pdo->exec('ALTER TABLE `good_loadings` ADD COLUMN `cash_drawer` varchar(40) DEFAULT NULL AFTER `payment`');
        }
        \App\Models\StockMinusLog::lupakanTabel(); // tes sebelumnya mungkin sudah mengingat "tabel tidak ada"
        \App\Models\GoodLoading::lupakanKolomLaci();
    }
}
