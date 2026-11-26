<?php

namespace Tests\Feature\Stock;

use App\Models\ActionLog;
use App\Models\ReconciliationCheck;
use App\Models\StockMinusLog;
use App\Models\Transaction;
use App\Services\TutupKasir;
use Illuminate\Support\Facades\DB;

/**
 * Akun yang membuat stok minus (stock_minus_logs) dan siapa yang
 * mengedit/menghapus loading atau mengedit/membatalkan transaksi (action_logs).
 */
class StockMinusLogTest extends StockTestCase
{
    use TabelTutupKasir;

    protected function setUp(): void
    {
        $this->siapkanTabelTutupKasir();

        parent::setUp();
        \App\Models\Journal::lupakanKolomPembuat();

        foreach (['1113' => 'Kas di Nanang', '5219' => 'Biaya Uang Hilang'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }
    }

    private function kasir(string $nama)
    {
        $kasir = $this->cashier();
        DB::table('cashiers')->where('id', $kasir->id)->update(['name' => $nama]);

        return $kasir->fresh();
    }

    private function adminBernama(string $nama)
    {
        $admin = $this->admin();
        DB::table('admins')->where('id', $admin->id)->update(['name' => $nama]);

        return $admin->fresh();
    }

    private function jual($kasir, int $pcs, float $qty): int
    {
        $this->actingAs($kasir, 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$pcs, $qty, 1500]]))->assertRedirect();

        return (int) Transaction::max('id');
    }

    private function terbuka(int $good)
    {
        return StockMinusLog::where('good_id', $good)->whereNull('ended_at')->get();
    }

    public function test_penjualan_yang_membuat_minus_dicatat_atas_nama_kasirnya(): void
    {
        [$good, $pcs] = $this->makeGood('Sabun', 1000, 1500);
        $this->load($pcs, 1, 1000);
        $ani  = $this->kasir('Ani');
        $budi = $this->kasir('Budi');

        $this->jual($ani, $pcs, 1);
        $this->assertEquals(0, $this->stock($good));
        $this->assertCount(0, $this->terbuka($good), 'stok 0 belum minus');

        $nota = $this->jual($budi, $pcs, 2);
        $this->assertEquals(-2, $this->stock($good));
        $log = $this->terbuka($good)->sole();
        $this->assertSame(['transaksi', $nota, 'cashier', $budi->id, 'Budi', -2.0],
            [$log->action, $log->transaction_id, $log->role, $log->role_id, $log->actor_name, $log->stock]);
        $this->assertStringStartsWith('Transaksi oleh Budi (nota #' . $nota . ')', $log->teks());

        // Makin minus oleh orang lain: penyebab pertamanya tetap.
        $this->jual($ani, $pcs, 1);
        $this->assertSame('Budi', $this->terbuka($good)->sole()->actor_name);

        // Loading masuk, stok kembali >= 0: catatannya ditutup.
        $this->load($pcs, 5, 1000);
        $this->assertEquals(2, $this->stock($good));
        $this->assertCount(0, $this->terbuka($good));
        $this->assertNotNull(StockMinusLog::where('good_id', $good)->sole()->ended_at);

        // Minus lagi: catatan baru.
        $this->jual($ani, $pcs, 3);
        $this->assertSame('Ani', $this->terbuka($good)->sole()->actor_name);
        $this->assertSame(2, StockMinusLog::where('good_id', $good)->count());
    }

    public function test_hapus_loading_dicatat_siapa_yang_menghapus_dan_jadi_penyebab_minus(): void
    {
        [$good, $pcs] = $this->makeGood('Minyak', 10000, 12000);
        $this->load($pcs, 4, 10000);
        $loading = $this->load($pcs, 6, 10000);
        $this->jual($this->kasir('Ani'), $pcs, 7);
        $this->assertEquals(3, $this->stock($good));

        $this->bolehEditHapus();
        $rina = $this->adminBernama('Rina');
        $this->actingAs($rina, 'admin')->delete('/admin/good-loading/' . $loading . '/delete')->assertRedirect();

        $this->assertEquals(-3, $this->stock($good));
        $log = $this->terbuka($good)->sole();
        $this->assertSame(['hapus_loading', $loading, null, 'admin', $rina->id, 'Rina'],
            [$log->action, $log->good_loading_id, $log->transaction_id, $log->role, $log->role_id, $log->actor_name]);
        $this->assertNull($log->url(), 'loading yang dihapus tidak punya halaman');

        $hapus = ActionLog::where('action', 'hapus_loading')->sole();
        $this->assertSame([$loading, 'admin', $rina->id, 'Rina'], [$hapus->good_loading_id, $hapus->role, $hapus->role_id, $hapus->actor_name]);
        $this->assertStringContainsString('Loading Distributor A', $hapus->note);
        $this->assertStringContainsString('1 baris barang', $hapus->note);
    }

    public function test_edit_transaksi_dicatat_siapa_dan_apa_yang_berubah(): void
    {
        [$good, $pcs] = $this->makeGood('Mie', 1000, 1500);
        $this->load($pcs, 5, 1000);
        $nota = $this->jual($this->kasir('Ani'), $pcs, 2);
        $this->assertEquals(3, $this->stock($good));

        $this->bolehEditHapus();
        $rina = $this->adminBernama('Rina');
        $detailId = DB::table('transaction_details')->where('transaction_id', $nota)->value('id');
        $this->actingAs($rina, 'admin')->put('/admin/transaction/' . $nota . '/edit', [
            'member_id' => 1, 'note' => 'dikoreksi',
            'total_item_price' => '9000', 'total_discount_price' => '0', 'total_sum_price' => '9000',
            'money_paid' => '9000', 'money_returned' => '0',
            'ids' => [$detailId], 'quantities' => ['6'], 'sum_prices' => ['9000'],
        ])->assertRedirect();

        $this->assertEquals(-1, $this->stock($good));
        $log = $this->terbuka($good)->sole();
        $this->assertSame(['edit_transaksi', $nota, 'admin', $rina->id, 'Rina'],
            [$log->action, $log->transaction_id, $log->role, $log->role_id, $log->actor_name]);

        $edit = ActionLog::where('action', 'edit_transaksi')->sole();
        $this->assertSame([$nota, 'admin', $rina->id, 'Rina'], [$edit->transaction_id, $edit->role, $edit->role_id, $edit->actor_name]);
        $this->assertStringContainsString('Mie', $edit->note);
        $this->assertStringContainsString('2 → 6', $edit->note);
        $this->assertStringContainsString('Total', $edit->note);

        $this->actingAs($rina, 'admin')->get('/admin/transaction/' . $nota . '/detail')->assertOk()
             ->assertSee('Riwayat Perubahan')->assertSee('Edit transaksi oleh Rina')->assertSee('2 → 6');
    }

    public function test_edit_loading_dicatat_siapa_dan_apa_yang_berubah(): void
    {
        [$good, $pcs] = $this->makeGood('Gula', 10000, 12000);
        $loading = $this->load($pcs, 5, 10000);
        $this->jual($this->kasir('Ani'), $pcs, 3);

        $this->bolehEditHapus();
        $rina = $this->adminBernama('Rina');
        $detailId = DB::table('good_loading_details')->where('good_loading_id', $loading)->value('id');
        $this->actingAs($rina, 'admin')->put('/admin/good-loading/' . $loading . '/edit', [
            'note' => null, 'checker' => 'test', 'loading_date' => date('Y-m-d'), 'distributor_id' => 1, 'payment' => '1111',
            'total_item_price' => '20000', 'change' => '1',
            'ids' => [$detailId], 'name_temps' => ['Gula'], 'quantities' => ['2'], 'prices' => ['10000'], 'sell_prices' => ['12000'],
        ])->assertRedirect();

        $this->assertEquals(-1, $this->stock($good));
        $log = $this->terbuka($good)->sole();
        $this->assertSame(['edit_loading', $loading, 'Rina'], [$log->action, $log->good_loading_id, $log->actor_name]);

        $edit = ActionLog::where('action', 'edit_loading')->sole();
        $this->assertSame([$loading, 'Rina'], [$edit->good_loading_id, $edit->actor_name]);
        $this->assertStringContainsString('5 → 2', $edit->note);
    }

    public function test_batal_transaksi_yang_mengambil_lagi_barang_retur_jadi_penyebab_minus(): void
    {
        [$sold, $soldPcs] = $this->makeGood('Kecap', 1000, 1500);
        [$back, $backPcs] = $this->makeGood('Saus', 3000, 4000);
        $this->load($soldPcs, 10, 1000);

        // Saus diretur dalam kondisi baik (masuk stok 2), lalu terjual 2.
        $this->actingAs($this->kasir('Ani'), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$soldPcs, 1, 1500]], [[$backPcs, 2, 4000, 'not']]));
        $nota = (int) Transaction::max('id');
        $this->assertEquals(2, $this->stock($back));
        $this->jual($this->kasir('Budi'), $backPcs, 2);
        $this->assertEquals(0, $this->stock($back));

        $rina = $this->adminBernama('Rina');
        $this->actingAs($rina, 'admin')->put('/admin/transaction/' . $nota . '/reverse')->assertRedirect();
        $this->actingAs($rina, 'admin')->put('/admin/transaction/' . $nota . '/reverse')->assertRedirect(); // klik kedua

        $this->assertEquals(-2, $this->stock($back));
        $log = $this->terbuka($back)->sole();
        $this->assertSame(['batal_transaksi', $nota, 'admin', $rina->id, 'Rina'],
            [$log->action, $log->transaction_id, $log->role, $log->role_id, $log->actor_name]);

        $batal = ActionLog::where('action', 'batal_transaksi')->sole(); // klik kedua tidak dicatat lagi
        $this->assertSame([$nota, 'Rina'], [$batal->transaction_id, $batal->actor_name]);
    }

    public function test_cek_harian_dan_daftar_minus_menampilkan_akun_penyebab(): void
    {
        [$good, $pcs] = $this->makeGood('Sabun', 1000, 1500);
        $nota = $this->jual($this->kasir('Budi'), $pcs, 2);

        $today = date('Y-m-d');
        $this->actingAs($this->admin(), 'admin')->post('/admin/cash-closing', [
            'closing_date' => $today, 'physical_cash' => '3000',
            'system_balance_seen' => number_format(TutupKasir::saldoKasDiTangan($today), 2, '.', ''),
        ])->assertSessionHasNoErrors();

        $stok = collect(ReconciliationCheck::sole()->results)->firstWhere('key', 'stok');
        $this->assertStringContainsString('stok minus -2, jadi minus: Transaksi oleh Budi (nota #' . $nota . ')', $stok['rincian'][0]['teks']);

        $super = $this->admin('superadmin');
        $this->actingAs($super, 'admin')->getJson('/admin/stock-opname/minus-goods')->assertOk()
             ->assertJsonPath('goods.0.good_id', $good)
             ->assertJsonPath('goods.0.penyebab', StockMinusLog::sole()->teks());

        $this->assertSame(StockMinusLog::sole()->teks(),
            (new \App\Models\GoodMovementRepository())->getNegativeStockGoods()->firstWhere('good_id', $good)->penyebab);
    }

    public function test_hitung_ulang_tanpa_aksi_dicatat_sebagai_hitung_ulang(): void
    {
        [$good, $pcs] = $this->makeGood('Teh', 1000, 1500);
        // Ditulis langsung ke database (tanpa event model), lalu stok dihitung ulang (tombol Samakan stok / artisan).
        $nota = DB::table('transactions')->insertGetId([
            'type' => 'normal', 'role' => 'admin', 'role_id' => 1, 'member_id' => 1, 'payment' => 'cash',
            'total_item_price' => 1500, 'total_discount_price' => 0, 'total_sum_price' => 1500, 'money_paid' => 1500, 'money_returned' => 0,
        ]);
        DB::table('transaction_details')->insert([
            'transaction_id' => $nota, 'good_unit_id' => $pcs, 'type' => 'normal', 'quantity' => 1, 'real_quantity' => 1,
            'last_stock' => 0, 'buy_price' => 1000, 'selling_price' => 1500, 'discount_price' => 0, 'sum_price' => 1500,
        ]);

        \App\Services\StockService::sync($good);

        $this->assertSame(['hitung_ulang', null], [$this->terbuka($good)->sole()->action, $this->terbuka($good)->sole()->actor_name]);
        $this->assertStringStartsWith('Hitung ulang stok oleh sistem', $this->terbuka($good)->sole()->teks());
    }
}
