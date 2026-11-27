<?php

namespace Tests\Feature\Stock;

/**
 * Tabel Sidak Stok (stock_inspections & detailnya) serta tabel/kolom tugas
 * cek stok & harga obral (docs/sql/2026-11-28-...) dibuat sekali lewat
 * koneksi PDO terpisah, sebelum transaksi tes dimulai (DDL di koneksi tes
 * akan meng-commit transaksi tes). Halaman Perputaran Barang & Sidak membuat
 * tabel ini otomatis kalau belum ada, jadi harus sudah ada sebelum tesnya.
 */
trait TabelSidakObral
{
    private static bool $tabelSidakObralSiap = false;

    protected function siapkanTabelSidakObral(): void
    {
        if (! self::$tabelSidakObralSiap) {
            self::$tabelSidakObralSiap = true;

            try {
                $pdo = new \PDO(
                    'mysql:host=' . (getenv('TEST_DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('TEST_DB_PORT') ?: '3306') . ';dbname=' . (getenv('TEST_DB_DATABASE') ?: 'kuncen_test'),
                    getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: ''
                );
            } catch (\PDOException $e) {
                return; // Database tes tidak ada: StockTestCase::setUp() akan melewati tes.
            }
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if (! in_array('goods', $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN))) {
                $pdo->exec(file_get_contents(dirname(__DIR__, 3) . '/database/schema/kuncen-schema.sql'));
            }

            $punya = fn ($tabel, $kolom) => $pdo->query("show columns from `$tabel` like '$kolom'")->fetch() !== false;
            foreach (['2026-11-13-tambah-sidak-stok.sql', '2026-11-13-sidak-jadi-stock-opname.sql', '2026-11-28-tugas-cek-stok-dan-harga-obral.sql'] as $file) {
                $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__, 3) . '/docs/sql/' . $file));
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                    if (str_contains($statement, '`migrations`')
                        || (str_contains($statement, 'ADD COLUMN `stock_opname_id`') && $punya('stock_inspections', 'stock_opname_id'))
                        || (str_contains($statement, 'ADD COLUMN `counted_by`') && $punya('stock_inspections', 'counted_by'))
                        || (str_contains($statement, 'ADD COLUMN `clearance_normal_price`') && $punya('good_units', 'clearance_normal_price'))) {
                        continue;
                    }
                    $pdo->exec($statement);
                }
            }
        }

        \App\Services\SkemaCekObral::lupakan(); // tes sebelumnya mungkin sudah mengingat "tabel tidak ada"
    }
}
