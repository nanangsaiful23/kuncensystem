<?php

namespace Tests\Feature\Stock;

use App\Models\CashClosing;
use App\Models\PosDevice;
use App\Models\ReconciliationCheck;
use App\Services\MenuAkses;
use App\Services\TutupKasir;
use Illuminate\Support\Facades\DB;

/**
 * Cek kecocokan harian (App\Services\CekKecocokan): berjalan setiap kali
 * Tutup Kasir disimpan, hasil yang tidak cocok tampil di dashboard admin
 * sampai ditandai. Database test MySQL seperti tes stok lain; setiap tes di-rollback.
 */
class CekKecocokanTest extends StockTestCase
{
    use TabelTutupKasir;

    private string $today;

    protected function setUp(): void
    {
        $this->siapkanTabelTutupKasir();

        parent::setUp();
        \App\Models\Journal::lupakanKolomPembuat();
        $this->today = date('Y-m-d');

        foreach (['1113' => 'Kas di Nanang', '5219' => 'Biaya Uang Hilang'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }
    }

    /** Barang dengan stok 10, lalu kasir menjual $qty lewat form kasir (jurnal penjualan & HPP dibuat aplikasi). */
    private function jual(int $qty = 2, float $stokAwal = 10): array
    {
        [$good, $pcs] = $this->makeGood('Sabun' . uniqid(), 1000, 1500);
        if ($stokAwal > 0) {
            $this->load($pcs, $stokAwal, 1000);
        }
        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$pcs, $qty, 1500]]))->assertRedirect();

        return [$good, $pcs];
    }

    private function tutup(float $fisik, string $guard = 'admin', $user = null)
    {
        $user ??= $guard === 'cashier' ? $this->cashier() : $this->admin();
        $saldo = TutupKasir::saldoKasDiTangan($this->today);

        return $this->actingAs($user, $guard)->post('/' . $guard . '/cash-closing', [
            'closing_date' => $this->today, 'physical_cash' => (string) $fisik,
            'system_balance_seen' => number_format($saldo, 2, '.', ''),
        ]);
    }

    private function hasil(ReconciliationCheck $cek, string $key): array
    {
        return collect($cek->results)->firstWhere('key', $key);
    }

    public function test_semua_cocok_tampil_satu_baris_hijau_di_dashboard(): void
    {
        $this->jual();

        $this->tutup(3000)->assertSessionHasNoErrors()
             ->assertSessionHas('tutup_kasir_ok', fn ($pesan) => str_contains($pesan, 'Cek kecocokan: semua cocok.'));

        $cek = ReconciliationCheck::sole();
        $this->assertSame(0, $cek->problem_count, json_encode($cek->results));
        $this->assertSame(CashClosing::sole()->id, $cek->cash_closing_id);
        $this->assertSame($this->today, $cek->check_date->format('Y-m-d'));
        foreach (['kas', 'penjualan', 'hpp', 'stok'] as $key) {
            $this->assertTrue($this->hasil($cek, $key)['ok'], $key);
        }

        $this->actingAs($this->admin(), 'admin')->get('/admin')->assertOk()
             ->assertSee('Tutup kasir ' . displayDate($this->today) . ': semua cocok.')
             ->assertSee('Kemarin (' . displayDate(date('Y-m-d', strtotime('-1 day'))) . ') belum tutup kasir.')
             ->assertDontSee('KEMAREN BELUM PENARIKAN UANG');

        $this->actingAs($this->admin(), 'admin')->get('/admin/transaction/resumeTotal/' . $this->today . '/' . $this->today)
             ->assertOk()->assertSee('Cek kecocokan')->assertSee('Semua cocok');
    }

    public function test_kas_dan_penjualan_tidak_cocok_tampil_sampai_ditandai_sudah_dibaca(): void
    {
        $this->jual();
        // Jurnal penjualan harian berkurang 1.000 (mis. diedit manual): saldo Kas di Tangan ikut 2.000.
        DB::table('journals')->where('type', 'transaction')->whereNull('type_id')->update(['debit' => 2000, 'credit' => 2000]);

        $this->tutup(2500)->assertSessionHasNoErrors()
             ->assertSessionHas('tutup_kasir_ok', fn ($pesan) => str_contains($pesan, 'Cek kecocokan: 2 tidak cocok (Kas, Penjualan)'));

        $cek = ReconciliationCheck::sole();
        $this->assertSame(2, $cek->problem_count);
        $this->assertStringContainsString('lebih Rp500.00', $this->hasil($cek, 'kas')['pesan']);
        $this->assertTrue($this->hasil($cek, 'hpp')['ok']);

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()
             ->assertSee('Tutup kasir ' . displayDate($this->today) . ': 2 tidak cocok')
             ->assertSee('Penjualan tunai: seharusnya Rp3,000.00, di jurnal Rp2,000.00 (jurnal kurang Rp1,000.00)')
             ->assertSee('admin/reconciliation/' . $cek->id . '/mark', false)
             ->assertDontSee('Samakan stok');

        $this->actingAs($admin, 'admin')->post('/admin/reconciliation/' . $cek->id . '/mark', ['resolution' => 'dibaca', 'note' => 'jurnal diedit owner'])
             ->assertRedirect('/admin#cek-kecocokan')->assertSessionHas('cek_ok');

        $cek->refresh();
        $this->assertNotNull($cek->resolved_at);
        $this->assertSame('dibaca', $cek->resolution);
        $this->assertSame('admin', $cek->resolved_by_role);
        $this->assertSame($admin->id, $cek->resolved_by_id);
        $this->assertSame('jurnal diedit owner', $cek->resolve_note);

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()
             ->assertDontSee('admin/reconciliation/' . $cek->id . '/mark', false)
             ->assertSee('ditandai sudah dibaca oleh Admin Test');

        // Tidak bisa ditandai dua kali.
        $this->actingAs($admin, 'admin')->post('/admin/reconciliation/' . $cek->id . '/mark', ['resolution' => 'ditangani'])
             ->assertSessionHasErrors('cek');
        $this->assertSame('dibaca', $cek->fresh()->resolution);
    }

    public function test_stok_beda_dengan_riwayat_bisa_disamakan_lalu_otomatis_ditangani(): void
    {
        [$good] = $this->jual(2);
        $this->assertEquals(8, $this->stock($good));
        DB::table('goods')->where('id', $good)->update(['last_stock' => 99]);

        $this->tutup(3000)->assertSessionHasNoErrors();
        $cek = ReconciliationCheck::sole();
        $stok = $this->hasil($cek, 'stok');
        $this->assertFalse($stok['ok']);
        $this->assertSame([$good], $stok['good_ids']);
        $this->assertStringContainsString('stok tercatat 99, hitungan dari loading & transaksi 8', $stok['rincian'][0]['teks']);

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->assertSee('Samakan stok 1 barang');

        $this->actingAs($admin, 'admin')->post('/admin/reconciliation/' . $cek->id . '/sync-stock')
             ->assertRedirect('/admin#cek-kecocokan')
             ->assertSessionHas('cek_ok', fn ($pesan) => str_contains($pesan, 'semua sudah cocok'));

        $this->assertEquals(8, $this->stock($good));
        $cek->refresh();
        $this->assertSame(0, $cek->problem_count);
        $this->assertSame('ditangani', $cek->resolution);
        $this->assertSame('Cek ulang: semua sudah cocok.', $cek->resolve_note);
        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->assertSee('semua cocok.');
    }

    public function test_stok_minus_barang_yang_terjual_hari_itu(): void
    {
        [$good] = $this->jual(2, 0);
        $this->assertEquals(-2, $this->stock($good));

        $this->tutup(3000)->assertSessionHasNoErrors();
        $stok = $this->hasil(ReconciliationCheck::sole(), 'stok');
        $this->assertFalse($stok['ok']);
        $this->assertSame([], $stok['good_ids'], 'stok tercatat sama dengan riwayatnya');
        $this->assertStringContainsString('stok minus -2', $stok['rincian'][0]['teks']);
        $this->assertSame('admin/good/' . $good . '/detail', $stok['rincian'][0]['url']);

        $this->actingAs($this->admin(), 'admin')->get('/admin')->assertOk()->assertDontSee('Samakan stok');
    }

    public function test_stok_tetap_benar_walau_transaksi_terhapus_lebih_dari_seribu(): void
    {
        // MariaDB: NOT IN (...) berisi 1000 nilai atau lebih bisa menghasilkan 0 baris.
        $hapus = [];
        for ($i = 0; $i < 1001; $i++) {
            $hapus[] = ['type' => 'normal', 'role' => 'cashier', 'role_id' => 1, 'member_id' => 1, 'payment' => 'cash',
                'total_item_price' => 0, 'total_sum_price' => 0, 'money_paid' => 0, 'money_returned' => 0, 'deleted_at' => now()];
        }
        DB::table('transactions')->insert($hapus);

        [$good] = $this->jual(2);
        \App\Services\StockService::sync($good);
        $this->assertEquals(8, $this->stock($good));
        $this->assertEquals(8, \App\Services\StockService::countersMany([$good])[$good]['last_stock']);
    }

    public function test_transaksi_kasir_hp_yang_ditolak_dan_cek_ulang(): void
    {
        if (! PosDevice::tabelAda()) {
            $this->markTestSkipped('Tabel Kasir HP tidak ada di database tes.');
        }
        $this->jual();
        $hp = PosDevice::create(['name' => 'HP Depan', 'code' => 'HP9', 'is_active' => true]);
        $tolak = \App\Models\PosSyncReject::create(['pos_device_id' => $hp->id, 'receipt_no' => 'HP9-0001', 'payload' => '{}', 'reason' => 'Barang tidak ditemukan.']);

        $this->tutup(3000)->assertSessionHasNoErrors();
        $cek = ReconciliationCheck::sole();
        $this->assertSame(1, $cek->problem_count);
        $this->assertStringContainsString('HP Depan, nota HP9-0001', $this->hasil($cek, 'kasir_hp')['rincian'][0]['teks']);

        // Belum diselesaikan: cek ulang tetap tidak cocok.
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post('/admin/reconciliation/' . $cek->id . '/recheck')->assertRedirect('/admin#cek-kecocokan');
        $this->assertNull($cek->fresh()->resolved_at);

        $tolak->update(['resolved_at' => now()]);
        $this->actingAs($admin, 'admin')->post('/admin/reconciliation/' . $cek->id . '/recheck');
        $this->assertSame(0, $cek->fresh()->problem_count);
        $this->assertSame('ditangani', $cek->fresh()->resolution);
    }

    public function test_batal_tutup_kasir_menghapus_hasil_ceknya(): void
    {
        $this->jual();
        $this->tutup(2000);
        $this->assertSame(1, ReconciliationCheck::count());

        $this->actingAs($this->admin('superadmin'), 'admin')->post('/admin/cash-closing/' . CashClosing::sole()->id . '/cancel')
             ->assertSessionHasNoErrors();
        $this->assertSame(0, ReconciliationCheck::count());

        $this->tutup(2000)->assertSessionHasNoErrors();
        $this->assertSame(1, ReconciliationCheck::count(), 'tutup ulang menjalankan cek baru');
    }

    public function test_kasir_hanya_melihat_ringkasan_dan_hak_akses_menu(): void
    {
        $this->jual();
        $kasir = $this->cashier();
        $this->tutup(2500, 'cashier', $kasir)->assertSessionHasNoErrors()
             ->assertSessionHas('tutup_kasir_ok', fn ($pesan) => str_contains($pesan, 'Cek kecocokan: 1 tidak cocok (Kas), sudah dilaporkan ke admin.'));

        $this->actingAs($kasir, 'cashier')->get('/cashier/transaction/resumeTotal/' . $this->today . '/' . $this->today)
             ->assertOk()->assertSee('1 tidak cocok (Kas), sudah dilaporkan ke admin.')
             ->assertDontSee('dashboard admin');

        $this->assertSame(['kas.cek_kecocokan'], MenuAkses::menuUntukPath('reconciliation/5/mark'));
        $this->assertTrue(MenuAkses::boleh('kas.cek_kecocokan', 'admin'));
    }
}
