<?php

namespace Tests\Feature\Stock;

use App\Models\CashClosing;
use App\Services\MenuAkses;
use Illuminate\Support\Facades\DB;

/**
 * Tutup Kasir (App\Services\TutupKasir). Memakai database test MySQL seperti
 * tes stok lain; setiap tes di-rollback.
 *
 * Tabel cash_closings dibuat sekali dari docs/sql/2026-11-24-tutup-kasir.sql
 * lewat koneksi PDO terpisah (DDL di koneksi tes akan meng-commit transaksi tes).
 */
class CashClosingTest extends StockTestCase
{
    private static bool $tableReady = false;

    protected function setUp(): void
    {
        if (! self::$tableReady) {
            self::$tableReady = true;
            try {
                $this->createTable();
            } catch (\PDOException $e) {
                // Database tes tidak ada: parent::setUp() akan melewati tes.
            }
        }

        parent::setUp();

        foreach (['1113' => 'Kas di Nanang', '5219' => 'Biaya Uang Hilang'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }

        // Kas di Tangan berisi 500.000 (modal pemilik).
        DB::table('journals')->insert([
            'type' => 'test', 'journal_date' => date('Y-m-d'), 'name' => 'modal',
            'debit_account_id' => $this->acc['1111'], 'debit' => 500000,
            'credit_account_id' => $this->acc['3001'], 'credit' => 500000,
        ]);
    }

    private function createTable(): void
    {
        $pdo = new \PDO(
            'mysql:host=' . (getenv('TEST_DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('TEST_DB_PORT') ?: '3306') . ';dbname=' . (getenv('TEST_DB_DATABASE') ?: 'kuncen_test'),
            getenv('TEST_DB_USERNAME') ?: 'root', getenv('TEST_DB_PASSWORD') ?: ''
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if (! in_array('accounts', $pdo->query('show tables')->fetchAll(\PDO::FETCH_COLUMN))) {
            $pdo->exec(file_get_contents(dirname(__DIR__, 3) . '/database/schema/kuncen-schema.sql'));
        }

        $sql = preg_replace('/^--.*$/m', '', file_get_contents(dirname(__DIR__, 3) . '/docs/sql/2026-11-24-tutup-kasir.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if (! str_contains($statement, '`migrations`')) {
                $pdo->exec($statement);
            }
        }
    }

    private function tutup(array $form, string $guard = 'admin', $user = null)
    {
        $user ??= $guard === 'cashier' ? $this->cashier() : $this->admin();

        return $this->actingAs($user, $guard)->post('/' . $guard . '/cash-closing', $form + ['system_balance_seen' => '500000.00']);
    }

    private function journals(string $type)
    {
        return DB::table('journals')->whereNull('deleted_at')->where('type', $type)->get();
    }

    public function test_halaman_menampilkan_saldo_kas_di_tangan(): void
    {
        $this->actingAs($this->admin(), 'admin')
             ->get('/admin/cash-closing')
             ->assertOk()
             ->assertSee('Rp500,000.00')
             ->assertSee('name="physical_cash"', false);
    }

    public function test_uang_kurang_dijurnal_ke_biaya_uang_hilang(): void
    {
        $this->tutup(['physical_cash' => '480,000', 'draw_amount' => '400.000', 'note' => 'salah kembalian'])
             ->assertRedirect('/admin/cash-closing')
             ->assertSessionHas('tutup_kasir_ok');

        $closing = CashClosing::firstOrFail();
        $this->assertEquals(500000, $closing->system_balance);
        $this->assertEquals(480000, $closing->physical_cash);
        $this->assertEquals(-20000, $closing->difference);
        $this->assertEquals(400000, $closing->draw_amount);
        $this->assertSame('salah kembalian', $closing->note);
        $this->assertSame('admin', $closing->role);

        $this->assertEquals(80000, $this->balance('1111'), 'sisa di laci = uang fisik - diserahkan');
        $this->assertEquals(20000, $this->balance('5219'));
        $this->assertEquals(400000, $this->balance('1113'));

        $adjustment = $this->journals('cash_adjustment')->sole();
        $this->assertSame($closing->adjustment_journal_id, (int) $adjustment->id);
        $this->assertSame($closing->id, (int) $adjustment->type_id);
        $draw = $this->journals('cash_draw')->sole();
        $this->assertSame($closing->draw_journal_id, (int) $draw->id);
        $this->assertSame('Pengambilan uang tanggal ' . date('Y-m-d'), $draw->name);
    }

    public function test_uang_lebih_dijurnal_ke_pendapatan_lain(): void
    {
        $this->tutup(['physical_cash' => '510000'])->assertRedirect('/admin/cash-closing');

        $this->assertEquals(10000, CashClosing::firstOrFail()->difference);
        $this->assertEquals(510000, $this->balance('1111'));
        $this->assertEquals(-10000, $this->balance('6101'), 'pendapatan lain di sisi kredit');
        $this->assertCount(0, $this->journals('cash_draw'), 'tidak ada uang yang diserahkan');
    }

    public function test_uang_pas_tanpa_jurnal_selisih_dan_hanya_sekali_sehari(): void
    {
        $this->tutup(['physical_cash' => '500000', 'draw_amount' => '450000'])->assertRedirect('/admin/cash-closing');

        $this->assertCount(0, $this->journals('cash_adjustment'));
        $this->assertEquals(50000, $this->balance('1111'));

        $this->tutup(['physical_cash' => '50000'] + ['system_balance_seen' => '50000.00'])
             ->assertSessionHasErrors('physical_cash');
        $this->assertSame(1, CashClosing::count());

        $this->actingAs($this->admin(), 'admin')->get('/admin/cash-closing')
             ->assertOk()->assertSee('Kasir hari ini sudah ditutup')->assertDontSee('name="physical_cash"', false);
    }

    public function test_ditolak_kalau_saldo_berubah_sejak_halaman_dibuka(): void
    {
        $this->tutup(['physical_cash' => '500000', 'system_balance_seen' => '450000.00'])
             ->assertSessionHasErrors('physical_cash');

        $this->assertSame(0, CashClosing::count());
        $this->assertCount(0, $this->journals('cash_adjustment'));
    }

    public function test_diserahkan_tidak_boleh_melebihi_uang_fisik(): void
    {
        $this->tutup(['physical_cash' => '300000', 'draw_amount' => '300001'])->assertSessionHasErrors('draw_amount');
        $this->tutup(['physical_cash' => ''])->assertSessionHasErrors('physical_cash');

        $this->assertSame(0, CashClosing::count());
    }

    public function test_kasir_bisa_tutup_kasir(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier, 'cashier')->get('/cashier/cash-closing')->assertOk()->assertSee('Tutup Kasir');

        $this->tutup(['physical_cash' => '500000', 'draw_amount' => '500000'], 'cashier', $cashier)
             ->assertRedirect('/cashier/cash-closing');

        $closing = CashClosing::firstOrFail();
        $this->assertSame('cashier', $closing->role);
        $this->assertSame($cashier->id, $closing->role_id);
        $this->assertEquals(0, $this->balance('1111'));
    }

    public function test_batal_hanya_untuk_superadmin_lalu_bisa_ditutup_ulang(): void
    {
        $this->tutup(['physical_cash' => '490000', 'draw_amount' => '400000']);
        $closing = CashClosing::firstOrFail();

        $this->actingAs($this->admin(), 'admin')->post('/admin/cash-closing/' . $closing->id . '/cancel')->assertForbidden();
        $this->assertSame(1, CashClosing::count());

        $this->actingAs($this->admin('superadmin'), 'admin')
             ->post('/admin/cash-closing/' . $closing->id . '/cancel')
             ->assertRedirect('/admin/cash-closing');

        $this->assertSame(0, CashClosing::count());
        $this->assertEquals(500000, $this->balance('1111'), 'jurnal selisih & penarikan ikut terhapus');
        $this->assertEquals(0, $this->balance('5219'));

        $this->tutup(['physical_cash' => '500000'])->assertRedirect('/admin/cash-closing');
        $this->assertSame(1, CashClosing::count());
    }

    public function test_tutup_kasir_kemarin_tidak_bisa_dibatalkan(): void
    {
        $id = DB::table('cash_closings')->insertGetId([
            'closing_date' => date('Y-m-d', strtotime('-1 day')), 'system_balance' => 0, 'physical_cash' => 0,
            'difference' => 0, 'draw_amount' => 0, 'role' => 'admin', 'role_id' => 1, 'created_at' => now(),
        ]);

        $this->actingAs($this->admin('superadmin'), 'admin')
             ->post('/admin/cash-closing/' . $id . '/cancel')
             ->assertSessionHasErrors('cancel');
        $this->assertSame(1, CashClosing::count());
    }

    public function test_url_tutup_kasir_dijaga_hak_akses_menu(): void
    {
        $this->assertSame(['kas.tutup'], MenuAkses::menuUntukPath('cash-closing'));
        $this->assertSame(['kas.tutup', 'aksi.tutup_kasir.batal'], MenuAkses::menuUntukPath('cash-closing/5/cancel'));
        $this->assertTrue(MenuAkses::boleh('kas.tutup', 'admin'));
        $this->assertFalse(MenuAkses::boleh('aksi.tutup_kasir.batal', 'supervisor'));
    }
}
