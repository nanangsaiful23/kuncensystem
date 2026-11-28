<?php

namespace Tests\Feature\Stock;

use App\Models\CashClosing;
use App\Models\GoodLoading;
use App\Models\ReconciliationCheck;
use App\Services\TutupKasir;
use Illuminate\Support\Facades\DB;

/**
 * Tutup Kasir per laci: loading barang yang dibayar dari 1111 Kas di Tangan
 * dipotong dari laci yang dipilih, dan jumlah perkiraan semua laci dicek sama
 * dengan saldo sistem.
 *
 * Datanya meniru kasus nyata 27 Nov 2026 (di sini: kemarin = 26 Nov, hari ini = 27 Nov):
 * - Kasir Al: sisa kemarin 2.140.000, penjualan 11.432.500, lain +4.000, biaya -77.000, fisik 13.499.500
 * - Kasir M : sisa kemarin   182.500, penjualan  8.583.000, lain +5.000, biaya -260.000, fisik 8.210.000
 * - loading cash 192.400 + pembulatan 100 dari 1111 (diambil dari laci Kasir M)
 * - retur cash 33.000 (penjualan bruto 20.048.500, neto 20.015.500)
 * - saldo 1111 sebelum ditarik owner 21.817.500
 * Diharapkan Kasir Al pas, Kasir M kurang 108.000 (bukan 300.500).
 */
class TutupKasirLaciTest extends StockTestCase
{
    use TabelTutupKasir;

    private string $today;
    private string $yesterday;
    private \App\Cashier $al;
    private \App\Cashier $m;
    private \App\Admin $owner;

    protected function setUp(): void
    {
        $this->siapkanTabelTutupKasir();

        parent::setUp();
        \App\Models\Journal::lupakanKolomPembuat();
        GoodLoading::lupakanKolomLaci();

        $this->today = date('Y-m-d');
        $this->yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach (['1113' => 'Kas di Nanang', '5219' => 'Biaya Uang Hilang', '5221' => 'Biaya Charity', '5225' => 'Biaya dapur & pasar'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }

        $this->al = $this->cashier();
        $this->m = $this->cashier();
        DB::table('cashiers')->where('id', $this->al->id)->update(['name' => 'Kasir Al']);
        DB::table('cashiers')->where('id', $this->m->id)->update(['name' => 'Kasir M']);
        $this->owner = $this->admin('superadmin');
    }

    private function key(\App\Cashier $c): string
    {
        return 'cashier:' . $c->id;
    }

    private function jualCash(int $cashierId, float $total, string $date, string $type = 'normal'): int
    {
        return DB::table('transactions')->insertGetId([
            'type' => $type, 'role' => 'cashier', 'role_id' => $cashierId, 'member_id' => 1, 'payment' => 'cash',
            'total_item_price' => $total, 'total_sum_price' => $total, 'money_paid' => max($total, 0), 'money_returned' => 0,
            'created_at' => $date . ' 10:00:00', 'updated_at' => $date . ' 10:00:00',
        ]);
    }

    /** Jurnal harian penjualan cash seperti yang dibuat saat jual (debit 1111, kredit 4101, tanpa type_id). */
    private function jurnalPenjualan(string $date, float $amount): void
    {
        DB::table('journals')->insert([
            'type' => 'transaction', 'journal_date' => $date, 'name' => 'Penjualan tanggal ' . displayDate($date),
            'debit_account_id' => $this->acc['1111'], 'debit' => $amount, 'credit_account_id' => $this->acc['4101'], 'credit' => $amount,
        ]);
    }

    /**
     * Kemarin (26 Nov): dua laci, ditutup dengan penarikan sehingga sisa
     * Kasir Al 2.140.000 dan Kasir M 182.500 (saldo 1111 akhir kemarin 2.322.500).
     */
    private function kemarin(): void
    {
        $this->jualCash($this->al->id, 3140000, $this->yesterday);
        $this->jualCash($this->m->id, 182500, $this->yesterday);
        $this->jurnalPenjualan($this->yesterday, 3322500);

        $this->actingAs($this->owner, 'admin')->post('/admin/cash-closing', [
            'closing_date' => $this->yesterday, 'system_balance_seen' => '3322500.00',
            'laci_kasir' => [$this->key($this->al), $this->key($this->m)], 'laci_nama' => ['', ''],
            'laci_uang' => ['3,140,000', '182,500'], 'laci_tarik' => ['1,000,000', ''],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(2322500, TutupKasir::saldoKasDiTangan($this->yesterday));
    }

    /**
     * Hari ini (27 Nov). Loading cash 192.400 (dibulatkan 192.500) lewat form
     * loading asli; $laci = laci asal uangnya. Mengembalikan ID loading.
     */
    private function hariIni(?string $laci): int
    {
        $this->jualCash($this->al->id, 11432500, $this->today);
        $this->jualCash($this->m->id, 8616000, $this->today);
        $retur = $this->jualCash($this->m->id, -33000, $this->today, 'retur');
        $this->jurnalPenjualan($this->today, 20048500);
        // Retur cash: jurnal "loading barang retur" tanpa type_id, kredit 1111 (seperti saat jual).
        DB::table('journals')->insert([
            'type' => 'good_loading', 'journal_date' => $this->today,
            'name' => 'Loading barang retur (ID transaksi ' . $retur . ') tanggal ' . displayDate($this->today),
            'debit_account_id' => $this->acc['4101'], 'debit' => 33000, 'credit_account_id' => $this->acc['1111'], 'credit' => 33000,
        ]);

        // Transaksi lain & biaya lain lewat form kasir (penginput tercatat).
        $this->actingAs($this->al, 'cashier')->post('/cashier/other-transaction/store', ['type' => 'box_transaction', 'money' => '4,000', 'note' => 'plastik'])->assertRedirect();
        $this->actingAs($this->m, 'cashier')->post('/cashier/other-transaction/store', ['type' => 'box_transaction', 'money' => '5,000', 'note' => 'kardus'])->assertRedirect();
        $this->actingAs($this->al, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '2,000', 'notes' => 'parkir'])->assertRedirect();
        $this->actingAs($this->al, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5221', 'payment' => 'cash', 'money' => '75,000', 'notes' => 'roti'])->assertRedirect();
        $this->actingAs($this->m, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '260,000', 'notes' => 'galon'])->assertRedirect();

        return $this->loadingCash($laci);
    }

    private ?array $minyak = null;

    /** Kirim form Tambah Loading: 2 x 96.200 = 192.400 (cash dibulatkan ke 192.500). */
    private function postLoading(?string $laci, string $payment = '1111')
    {
        $this->minyak ??= $this->makeGood('Minyak', 96200, 110000);
        [$good, $pcs] = $this->minyak;
        $this->load($pcs, 1, 96200); // form loading membandingkan dengan loading terakhir

        return $this->actingAs($this->owner, 'admin')->post('/admin/good-loading/normal/store', [
            'distributor_name' => null, 'distributor_id' => 1, 'loading_date' => $this->today, 'payment' => $payment,
            'cash_drawer' => $laci, 'checker' => 'test', 'note' => 'loading cash', 'total_item_price' => '192.400',
            'names' => [$good], 'name_temps' => ['Minyak'], 'barcodes' => ['T-Minyak'],
            'units' => [(int) DB::table('good_units')->where('id', $pcs)->value('unit_id')],
            'quantities' => ['2'], 'prices' => ['96.200'], 'sell_prices' => ['110.000'], 'old_stocks' => [1], 'exp_dates' => [null],
        ]);
    }

    /** Loading lewat form; mengembalikan ID loading. */
    private function loadingCash(?string $laci, string $payment = '1111'): int
    {
        $this->postLoading($laci, $payment)->assertRedirect()->assertSessionHasNoErrors();

        return (int) GoodLoading::latest('id')->value('id');
    }

    private function resume(?string $date = null)
    {
        $date ??= $this->today;

        return $this->actingAs($this->owner, 'admin')->get('/admin/transaction/resumeTotal/' . $date . '/' . $date)->assertOk();
    }

    private function tutupHariIni()
    {
        return $this->actingAs($this->owner, 'admin')->post('/admin/cash-closing', [
            'closing_date' => $this->today, 'system_balance_seen' => '21817500.00',
            'laci_kasir' => [$this->key($this->al), $this->key($this->m)], 'laci_nama' => ['', ''],
            'laci_uang' => ['13,499,500', '8,210,000'], 'laci_tarik' => ['', ''],
        ]);
    }

    public function test_kasus_27_nov_loading_dari_laci_kasir_m_kurang_108_ribu(): void
    {
        $this->kemarin();
        $loadingId = $this->hariIni($this->key($this->m));

        $this->assertSame($this->key($this->m), GoodLoading::find($loadingId)->cash_drawer);
        $this->assertEquals(21817500, TutupKasir::saldoKasDiTangan($this->today), '2.322.500 + 20.048.500 - 33.000 + 9.000 - 337.000 - 192.500');

        $laci = collect(TutupKasir::daftarLaci($this->today))->keyBy('key');
        $al = $laci[$this->key($this->al)];
        $m = $laci[$this->key($this->m)];
        $this->assertEquals([2140000, 11432500, 4000, 77000, 0, 13499500], [$al['sisa'], $al['p']['penjualan'], $al['p']['lain'], $al['p']['biaya'], $al['p']['loading'], $al['perkiraan']]);
        $this->assertEquals([182500, 8583000, 5000, 260000, 192500, 8318000], [$m['sisa'], $m['p']['penjualan'], $m['p']['lain'], $m['p']['biaya'], $m['p']['loading'], $m['perkiraan']]);

        $cek = TutupKasir::cekJumlahLaci($this->today, $laci->values()->all(), 21817500);
        $this->assertTrue($cek['cocok'], 'jumlah perkiraan semua laci = saldo sistem');
        $this->assertEquals(21817500, $cek['jumlah']);
        $this->assertSame([], $cek['luar']);

        $this->resume()
             ->assertSeeInOrder(['Kasir M', '&minus; loading barang cash Rp192,500.00', 'Rp8,318,000.00'], false)
             ->assertSeeInOrder(['Jumlah perkiraan semua laci', 'Rp21,817,500.00', 'Saldo sistem', 'Rp21,817,500.00'])
             ->assertSee('jumlah perkiraan semua laci sama dengan saldo sistem')
             ->assertDontSee('belum dialokasikan ke laci');

        $this->tutupHariIni()->assertSessionHasNoErrors();
        $closing = CashClosing::whereDate('closing_date', $this->today)->firstOrFail();
        $this->assertEquals(21817500, $closing->system_balance);
        $this->assertEquals(-108000, $closing->difference, 'selisih resmi dari total semua laci');
        $drawers = collect($closing->drawers)->keyBy('key');
        $this->assertEquals(13499500, $drawers[$this->key($this->al)]['perkiraan']);
        $this->assertEquals(8318000, $drawers[$this->key($this->m)]['perkiraan']);
        $this->assertEquals(192500, $drawers[$this->key($this->m)]['loading_cash']);

        $this->resume()
             ->assertSeeInOrder(['Rincian per laci', 'Kasir Al', 'Rp13,499,500.00', 'Rp13,499,500.00', '>Pas<',
                                 'Kasir M', 'loading barang −Rp192,500.00', 'Rp8,318,000.00', 'Rp8,210,000.00', 'Kurang Rp108,000.00'], false)
             ->assertDontSee('Kurang Rp300,500.00')
             ->assertSee('jumlah perkiraan semua laci sama dengan saldo sistem');

        // Cek kecocokan: rincian laci juga memakai perkiraan baru.
        $kas = collect(ReconciliationCheck::where('cash_closing_id', $closing->id)->firstOrFail()->results)->firstWhere('key', 'kas');
        $this->assertStringContainsString('Laci Kasir M: uang fisik Rp8,210,000.00, perkiraan Rp8,318,000.00 (kurang Rp108,000.00)', implode("\n", array_column($kas['rincian'], 'teks')));
    }

    public function test_loading_cash_tanpa_laci_tampil_belum_dialokasikan_dan_peringatan(): void
    {
        $this->kemarin();
        $loadingId = $this->hariIni($this->key($this->m));
        DB::table('good_loadings')->where('id', $loadingId)->update(['cash_drawer' => null]); // data lama

        $laci = collect(TutupKasir::daftarLaci($this->today))->keyBy('key');
        $this->assertEquals(8510500, $laci[$this->key($this->m)]['perkiraan'], 'tanpa laci: loading tidak dipotong dari Kasir M');

        $cek = TutupKasir::cekJumlahLaci($this->today, $laci->values()->all(), TutupKasir::saldoKasDiTangan($this->today));
        $this->assertFalse($cek['cocok']);
        $this->assertEquals(22010000, $cek['jumlah']);
        $this->assertEquals(-192500, $cek['selisih']);
        $this->assertEquals(-192500, $cek['keluar']);
        $this->assertEquals(0, $cek['belumKetemu'], 'semua beda ketemu sumbernya');
        $this->assertEquals(['good_loading' => -192400, 'good_loading_rounding' => -100], array_column($cek['luar'], 'nominal', 'jenis'));
        $jurnalLoading = DB::table('journals')->where('type', 'good_loading')->where('type_id', $loadingId)->value('id');

        $this->resume()
             ->assertSee('Pengeluaran kas belum dialokasikan ke laci')
             ->assertSee('−Rp192,500.00')
             ->assertSee('Jumlah perkiraan semua laci tidak sama dengan saldo sistem')
             ->assertSee('good_loading #' . $jurnalLoading)
             ->assertSee('loading ID ' . $loadingId . ' belum dipilih lacinya')
             ->assertSee('good_loading_rounding #')
             ->assertSee('admin/good-loading/' . $loadingId . '/edit', false)
             ->assertSeeInOrder(['Jumlah perkiraan semua laci', 'Rp22,010,000.00', 'Saldo sistem', 'Rp21,817,500.00']);

        $this->tutupHariIni()->assertSessionHasNoErrors();
        $closing = CashClosing::whereDate('closing_date', $this->today)->firstOrFail();
        $this->assertEquals(-108000, $closing->difference, 'selisih resmi tetap dari total');

        // Setelah ditutup: baris "belum dialokasikan" di rincian laci + peringatan; jumlahnya = saldo sistem.
        $this->resume()
             ->assertSeeInOrder(['Kasir M', 'Kurang Rp300,500.00', 'Pengeluaran kas belum dialokasikan ke laci', '−Rp192,500.00',
                                 'Jumlah', 'Rp21,817,500.00', 'Rp21,709,500.00'])
             ->assertSee('Jumlah perkiraan semua laci tidak sama dengan saldo sistem');
        $kas = collect(ReconciliationCheck::where('cash_closing_id', $closing->id)->firstOrFail()->results)->firstWhere('key', 'kas');
        $this->assertStringContainsString('belum dipilih lacinya', implode("\n", array_column($kas['rincian'], 'teks')));

        // Lacinya dipilih di Ubah Loading: tutup kasir yang sudah disimpan ikut terhitung ulang.
        $detail = DB::table('good_loading_details')->where('good_loading_id', $loadingId)->first();
        $this->actingAs($this->owner, 'admin')->put('/admin/good-loading/' . $loadingId . '/edit', [
            'distributor_id' => 1, 'loading_date' => $this->today, 'payment' => '1111', 'cash_drawer' => $this->key($this->m),
            'checker' => 'test', 'note' => 'loading cash', 'change' => '', 'ids' => [$detail->id], 'name_temps' => ['Minyak'],
            'barcodes' => ['T-Minyak'], 'quantities' => ['2'], 'prices' => ['96.200'], 'sell_prices' => ['110.000'], 'total_item_price' => '192.400',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->key($this->m), GoodLoading::find($loadingId)->cash_drawer);

        $this->resume()
             ->assertSeeInOrder(['Kasir M', 'Rp8,318,000.00', 'Rp8,210,000.00', 'Kurang Rp108,000.00'])
             ->assertDontSee('Kurang Rp300,500.00')
             ->assertDontSee('belum dialokasikan ke laci')
             ->assertSee('jumlah perkiraan semua laci sama dengan saldo sistem')
             // Cek kecocokan (Kas) juga dibaca dari data sekarang, bukan dari saat tutup kasir.
             ->assertSee('Laci Kasir M: uang fisik Rp8,210,000.00, perkiraan Rp8,318,000.00 (kurang Rp108,000.00)')
             ->assertDontSee('(kurang Rp300,500.00)')
             ->assertDontSee('belum dipilih lacinya');
    }

    public function test_jurnal_manual_dan_hari_tanpa_tutup_kasir_masuk_daftar_sumber_beda(): void
    {
        $this->kemarin();
        $this->jualCash($this->al->id, 100000, $this->today);
        $this->jurnalPenjualan($this->today, 100000);
        // Jurnal manual keluar dari Kas di Tangan tanpa laci.
        DB::table('journals')->insert(['type' => 'manual', 'journal_date' => $this->today, 'name' => 'bayar listrik',
            'debit_account_id' => $this->acc['5220'], 'debit' => 50000, 'credit_account_id' => $this->acc['1111'], 'credit' => 50000]);
        // Jurnal kemarin yang dibuat setelah tutup kasir kemarin.
        DB::table('journals')->insert(['type' => 'manual', 'journal_date' => $this->yesterday, 'name' => 'koreksi kemarin',
            'debit_account_id' => $this->acc['1111'], 'debit' => 7000, 'credit_account_id' => $this->acc['6101'], 'credit' => 7000]);

        $saldo = TutupKasir::saldoKasDiTangan($this->today);
        $this->assertEquals(2322500 + 100000 - 50000 + 7000, $saldo);
        $cek = TutupKasir::cekJumlahLaci($this->today, TutupKasir::daftarLaci($this->today), $saldo);
        $this->assertFalse($cek['cocok']);
        $this->assertEquals(-43000, $cek['selisih']);
        $this->assertEquals(['manual', 'saldo_awal'], array_column($cek['luar'], 'jenis'));
        $this->assertEquals([-50000, 7000], array_column($cek['luar'], 'nominal'));
        $this->assertEquals(0, $cek['belumKetemu']);

        $this->resume()
             ->assertSee('manual #')
             ->assertSee('bayar listrik')
             ->assertSee('dibuat/diubah setelah tutup kasir')
             ->assertSee('Pemasukan kas belum dialokasikan ke laci');
    }

    public function test_teks_cek_kecocokan_membaca_akun_jurnal_selisih_yang_sekarang(): void
    {
        DB::table('journals')->insert(['type' => 'test', 'journal_date' => $this->today, 'name' => 'modal',
            'debit_account_id' => $this->acc['1111'], 'debit' => 500000, 'credit_account_id' => $this->acc['3001'], 'credit' => 500000]);
        $this->actingAs($this->owner, 'admin')->post('/admin/cash-closing', [
            'closing_date' => $this->today, 'system_balance_seen' => '500000.00',
            'laci_kasir' => ['laci:1'], 'laci_nama' => ['Laci'], 'laci_uang' => ['392,000'],
        ])->assertSessionHasNoErrors();
        $closing = CashClosing::firstOrFail();
        $this->assertEquals(-108000, $closing->difference);

        $this->resume()->assertSee('kurang Rp108,000.00 (sudah dijurnal ke Biaya Uang Hilang)');

        // Owner mengganti akun jurnal selisih: ternyata charity.
        DB::table('journals')->where('id', $closing->adjustment_journal_id)->update(['debit_account_id' => $this->acc['5221']]);

        $this->resume()
             ->assertSee('kurang Rp108,000.00 (sudah dijurnal ke Biaya Charity)')
             ->assertDontSee('sudah dijurnal ke Biaya Uang Hilang');
        $this->actingAs($this->owner, 'admin')->get('/admin')->assertOk()->assertDontSee('sudah dijurnal ke Biaya Uang Hilang');
    }

    public function test_total_biaya_lain_resume_sama_dengan_daftar_biaya_lain(): void
    {
        $this->actingAs($this->al, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '2,000', 'notes' => 'parkir']);
        $this->actingAs($this->al, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5221', 'payment' => 'cash', 'money' => '75,000', 'notes' => 'roti']);
        $this->actingAs($this->m, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '260,000', 'notes' => 'galon']);
        $this->actingAs($this->m, 'cashier')->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'transfer', 'money' => '10,000', 'notes' => 'token']);

        // Charity (5221) ikut dihitung, sama dengan daftar Biaya Lain owner.
        $this->actingAs($this->owner, 'admin')->get('/admin/other-payment/' . $this->today . '/' . $this->today . '/all')
             ->assertOk()->assertSee('Total biaya lain hari ini: Rp347,000.00');
        $this->resume()
             ->assertSeeInOrder(['Total biaya lain', 'Rp347,000.00', 'dibayar dari Kas di Tangan', 'Rp337,000.00']);
    }

    public function test_biaya_lain_rp0_ditolak(): void
    {
        foreach (['0', '', 'abc'] as $uang) {
            $this->actingAs($this->al, 'cashier')
                 ->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => $uang, 'notes' => 'Santri'])
                 ->assertSessionHasErrors('money');
        }
        $this->assertSame(0, DB::table('journals')->where('type', 'other_payment')->count());

        $this->actingAs($this->al, 'cashier')
             ->post('/cashier/other-payment/store', ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '1,000', 'notes' => 'Santri'])
             ->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('journals')->where('type', 'other_payment')->count());
    }

    public function test_loading_kas_di_tangan_wajib_pilih_laci(): void
    {
        $this->actingAs($this->owner, 'admin')->get('/admin/good-loading/normal/createnew')
             ->assertOk()->assertSee('Diambil dari laci/kasir')
             ->assertSee('value="' . $this->key($this->m) . '"', false)->assertSee('Kasir M');

        $this->postLoading(null)->assertSessionHasErrors('cash_drawer');
        $this->postLoading('cashier:999999')->assertSessionHasErrors('cash_drawer');
        $this->assertSame(0, DB::table('journals')->where('type', 'good_loading')->count());

        // Bukan dari Kas di Tangan: tidak perlu laci, dan laci tidak disimpan.
        $id = $this->loadingCash($this->key($this->m), '1112');
        $this->assertNull(GoodLoading::find($id)->cash_drawer);

        $id = $this->loadingCash($this->key($this->al));
        $this->assertSame($this->key($this->al), GoodLoading::find($id)->cash_drawer);
        $this->actingAs($this->owner, 'admin')->get('/admin/good-loading/' . $id . '/detail')->assertOk()->assertSee('Kasir Al');
    }
}
