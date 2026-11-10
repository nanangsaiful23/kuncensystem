<?php

namespace Tests\Feature\Stock;

use App\Cashier;
use App\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for stock and journals. They need MySQL/MariaDB (the queries use
 * MySQL syntax) and an EMPTY database, by default `kuncen_test`:
 *
 *   CREATE DATABASE kuncen_test;
 *
 * The table structure is loaded from database/schema/kuncen-schema.sql the
 * first time. Every test runs inside a transaction that is rolled back.
 * Set TEST_DB_DATABASE / TEST_DB_USERNAME / TEST_DB_PASSWORD to use another
 * database. When the database is not reachable the tests are skipped.
 */
abstract class StockTestCase extends TestCase
{
    use DatabaseTransactions;

    private static bool $schemaLoaded = false;
    private static ?string $unavailable = null;
    private array $previousEnv = [];

    protected array $acc = [];

    protected function setUp(): void
    {
        $this->useEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_URL'        => '',
            'DB_HOST'       => getenv('TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT'       => getenv('TEST_DB_PORT') ?: '3306',
            'DB_DATABASE'   => getenv('TEST_DB_DATABASE') ?: 'kuncen_test',
            'DB_USERNAME'   => getenv('TEST_DB_USERNAME') ?: 'root',
            'DB_PASSWORD'   => getenv('TEST_DB_PASSWORD') ?: '',
        ]);

        if (self::$unavailable === null) {
            try {
                $c = 'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'];
                new \PDO($c, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
                self::$unavailable = '';
            } catch (\Throwable $e) {
                self::$unavailable = 'Database test MySQL tidak tersedia: ' . $e->getMessage();
            }
        }

        if (self::$unavailable !== '') {
            $this->restoreEnv();
            $this->markTestSkipped(self::$unavailable);
        }

        if (! self::$schemaLoaded) {
            // load the structure before the test transaction starts
            $pdo = new \PDO('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            $tables = $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN);
            if (! in_array('goods', $tables)) {
                $pdo->exec(file_get_contents(dirname(__DIR__, 3) . '/database/schema/kuncen-schema.sql'));
            } elseif ((int) $pdo->query('select count(*) from goods')->fetchColumn() > 0) {
                self::$unavailable = 'Database test ' . $_ENV['DB_DATABASE'] . ' tidak kosong; pakai database kosong khusus test.';
                $this->restoreEnv();
                $this->markTestSkipped(self::$unavailable);
            }
            self::$schemaLoaded = true;
        }

        parent::setUp();

        $this->seedBasics();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreEnv();
    }

    private function useEnv(array $values): void
    {
        foreach ($values as $k => $v) {
            $this->previousEnv[$k] = [$_ENV[$k] ?? null, $_SERVER[$k] ?? null, getenv($k)];
            $_ENV[$k] = $_SERVER[$k] = $v;
            putenv("$k=$v");
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->previousEnv as $k => [$env, $server, $put]) {
            if ($env === null) unset($_ENV[$k]); else $_ENV[$k] = $env;
            if ($server === null) unset($_SERVER[$k]); else $_SERVER[$k] = $server;
            putenv($put === false ? $k : "$k=$put");
        }
        $this->previousEnv = [];
    }

    private function seedBasics(): void
    {
        foreach (['1111' => 'Kas di Tangan', '1112' => 'Kas di Bank', '1131' => 'Piutang Dagang', '1141' => 'Persediaan Barang',
                  '2101' => 'Utang Dagang', '3001' => 'Modal Pemilik', '4101' => 'Penjualan', '5101' => 'Harga Pokok Penjualan',
                  '5215' => 'Biaya Penyusutan Barang', '5220' => 'Biaya Operasional Toko', '5223' => 'Biaya Lain-lain',
                  '6101' => 'Pendapatan Lain-lain'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }

        DB::table('members')->insert(['id' => 1, 'name' => 'Umum']);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Umum']);
        DB::table('distributors')->insert(['id' => 1, 'name' => 'Distributor A']);
    }

    /** A good with a pcs unit (and optionally a box unit). Returns [good_id, pcs_unit_id, box_unit_id]. */
    protected function makeGood(string $name, float $buy = 1000, float $sell = 1500, int $boxQty = 0): array
    {
        $pcs = DB::table('units')->where('code', 'PCS')->value('id')
            ?? DB::table('units')->insertGetId(['code' => 'PCS', 'name' => 'pcs', 'quantity' => '1', 'base' => 'pcs']);

        $good = DB::table('goods')->insertGetId([
            'category_id' => 1, 'name' => $name, 'code' => 'T-' . $name, 'last_distributor_id' => 1,
            'total_loading' => 0, 'total_transaction' => 0, 'last_stock' => 0,
        ]);
        $pcsUnit = DB::table('good_units')->insertGetId(['good_id' => $good, 'unit_id' => $pcs, 'buy_price' => $buy, 'selling_price' => $sell]);
        DB::table('goods')->where('id', $good)->update(['base_unit_id' => $pcsUnit]);

        $boxUnit = null;
        if ($boxQty) {
            $box = DB::table('units')->where('code', 'BOX' . $boxQty)->value('id')
                ?? DB::table('units')->insertGetId(['code' => 'BOX' . $boxQty, 'name' => $boxQty . ' pcs', 'quantity' => (string) $boxQty, 'base' => 'pcs']);
            $boxUnit = DB::table('good_units')->insertGetId(['good_id' => $good, 'unit_id' => $box, 'buy_price' => $buy * $boxQty, 'selling_price' => $sell * $boxQty]);
        }

        return [$good, $pcsUnit, $boxUnit];
    }

    /** Put stock on a good through a real loading line. */
    protected function load(int $goodUnitId, float $qty, float $price): int
    {
        $loading = DB::table('good_loadings')->insertGetId([
            'role' => 'admin', 'role_id' => 1, 'checker' => 'test', 'loading_date' => date('Y-m-d'),
            'distributor_id' => 1, 'total_item_price' => $qty * $price, 'payment' => '1111',
        ]);
        $unitQty = (float) DB::table('good_units')->join('units', 'units.id', '=', 'good_units.unit_id')->where('good_units.id', $goodUnitId)->value('units.quantity');
        \App\Models\GoodLoadingDetail::create([
            'good_loading_id' => $loading, 'good_unit_id' => $goodUnitId, 'last_stock' => 0,
            'quantity' => $qty, 'real_quantity' => $qty * $unitQty, 'price' => $price, 'selling_price' => $price,
        ]);

        return $loading;
    }

    protected function cashier(): Cashier
    {
        $id = DB::table('cashiers')->insertGetId(['name' => 'Kasir Test', 'email' => 'kasir_test_' . uniqid(), 'password' => 'x', 'is_active' => 1]);

        return Cashier::find($id);
    }

    protected function admin(): Admin
    {
        $id = DB::table('admins')->insertGetId(['name' => 'Admin Test', 'email' => 'admin_test_' . uniqid(), 'password' => 'x']);

        return Admin::find($id);
    }

    protected function stock(int $goodId): float
    {
        return (float) DB::table('goods')->where('id', $goodId)->value('last_stock');
    }

    /** Debit - credit of an account over all journals. */
    protected function balance(string $code): float
    {
        $id = $this->acc[$code];

        return (float) DB::table('journals')->whereNull('deleted_at')->where('debit_account_id', $id)->sum('debit')
             - (float) DB::table('journals')->whereNull('deleted_at')->where('credit_account_id', $id)->sum('credit');
    }

    /** Fields the cashier form posts. $items/$returs: [good_unit_id, qty, price, condition]. */
    protected function saleForm(array $items, array $returs = [], string $payment = 'cash', ?string $token = null): array
    {
        $f = ['member_id' => 1, 'type' => 'normal', 'payment' => $payment, 'note' => null, 'voucher' => null, 'voucher_nominal' => 0,
              'total_discount_price' => 0, 'is_promo' => 0, 'submit_token' => $token];
        $total = 0;
        foreach ($items as [$gu, $qty, $price]) {
            $f['barcodes'][] = $gu; $f['quantities'][] = $qty; $f['prices'][] = $price; $f['buy_prices'][] = 1;
            $f['discounts'][] = 0; $f['sums'][] = $qty * $price; $total += $qty * $price;
        }
        $f['barcodes'][] = null; // the form always has one empty row
        foreach ($returs as $r) {
            [$gu, $qty, $price] = $r;
            $f['barcodesretur_s'][] = $gu; $f['quantitiesretur_s'][] = $qty; $f['pricesretur_s'][] = $price;
            $f['buy_pricesretur_s'][] = 1; $f['discountsretur_s'][] = 0; $f['sumsretur_s'][] = $qty * $price;
            $f['conditionsretur_s'][] = $r[3] ?? 'not';
            $f['namesretur_s'][] = DB::table('good_units')->where('id', $gu)->value('good_id');
            $total -= $qty * $price;
        }
        $f['barcodesretur_s'][] = null;
        $f['total_item_price'] = $f['total_sum_price'] = $total;
        $f['money_paid'] = max($total, 0);
        $f['money_returned'] = 0;

        return $f;
    }
}
