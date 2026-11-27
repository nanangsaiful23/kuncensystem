<?php

namespace Tests\Feature\Stock;

/**
 * Kolom obral/musim di goods & types serta tabel seasons/season_periods
 * dibuat sekali dari docs/sql/2026-11-27-obral-dan-musim.sql lewat koneksi
 * PDO terpisah, sebelum transaksi tes dimulai (DDL di koneksi tes akan
 * meng-commit transaksi tes).
 */
trait TabelObralMusim
{
    private static bool $tabelObralMusimSiap = false;

    protected function siapkanTabelObralMusim(): void
    {
        if (! self::$tabelObralMusimSiap) {
            self::$tabelObralMusimSiap = true;

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
            $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__, 3) . '/docs/sql/2026-11-27-obral-dan-musim.sql'));
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if (str_contains($statement, '`migrations`')
                    || (str_starts_with($statement, 'ALTER TABLE `goods`') && $punya('goods', 'is_clearance'))
                    || (str_starts_with($statement, 'ALTER TABLE `types`') && $punya('types', 'season_id'))) {
                    continue;
                }
                $pdo->exec($statement);
            }
        }

        \App\Models\Season::lupakanTabel(); // tes sebelumnya mungkin sudah mengingat "tabel tidak ada"
    }
}
