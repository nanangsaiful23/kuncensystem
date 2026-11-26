<?php

namespace Tests\Feature\Stock;

use App\Models\CashClosing;
use App\Services\MenuAkses;
use Illuminate\Support\Facades\DB;

/**
 * Tutup Kasir (App\Services\TutupKasir), diisi di Resume Transaksi Total.
 * Memakai database test MySQL seperti tes stok lain; setiap tes di-rollback.
 *
 * Tabel cash_closings & reconciliation_checks dibuat sekali dari file SQL di
 * docs/sql (lihat TabelTutupKasir).
 */
class CashClosingTest extends StockTestCase
{
    use TabelTutupKasir;

    private string $today;
    private string $yesterday;

    protected function setUp(): void
    {
        $this->siapkanTabelTutupKasir();

        parent::setUp();
        \App\Models\Journal::lupakanKolomPembuat();

        $this->today = date('Y-m-d');
        $this->yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach (['1113' => 'Kas di Nanang', '5219' => 'Biaya Uang Hilang'] as $code => $name) {
            $this->acc[$code] = DB::table('accounts')->insertGetId(['code' => $code, 'name' => $name, 'balance' => 0]);
        }

        // Kas di Tangan berisi 500.000 (modal pemilik) hari ini.
        $this->kasMasuk($this->today, 500000);
    }

    private function kasMasuk(string $date, float $amount): void
    {
        DB::table('journals')->insert([
            'type' => 'test', 'journal_date' => $date, 'name' => 'modal',
            'debit_account_id' => $this->acc['1111'], 'debit' => $amount,
            'credit_account_id' => $this->acc['3001'], 'credit' => $amount,
        ]);
    }

    private function resumeTotal(string $from, ?string $to = null, string $guard = 'admin', $user = null)
    {
        $user ??= $guard === 'cashier' ? $this->cashier() : $this->admin();

        return $this->actingAs($user, $guard)->get('/' . $guard . '/transaction/resumeTotal/' . $from . '/' . ($to ?? $from));
    }

    private function tutup(array $form, string $guard = 'admin', $user = null)
    {
        $user ??= $guard === 'cashier' ? $this->cashier() : $this->admin();

        return $this->actingAs($user, $guard)->post('/' . $guard . '/cash-closing',
            $form + ['closing_date' => $this->today, 'system_balance_seen' => '500000.00']);
    }

    /** Cocokkan sebagian kunci rincian laci (angka dibandingkan longgar). */
    private function assertLaci(array $harap, array $laci, string $pesan = ''): void
    {
        $this->assertCount(count($harap), $laci, $pesan);
        foreach ($harap as $i => $h) {
            foreach ($h as $k => $v) {
                $this->assertEquals($v, $laci[$i][$k] ?? null, "laci[$i][$k] $pesan");
            }
        }
    }

    private function journals(string $type)
    {
        return DB::table('journals')->whereNull('deleted_at')->where('type', $type)->get();
    }

    public function test_resume_total_satu_tanggal_menampilkan_form_tutup_kasir(): void
    {
        $this->resumeTotal($this->today)
             ->assertOk()
             ->assertSee('Tutup Kasir')
             ->assertSee('Rp500,000.00')
             ->assertSee('name="laci_uang[]"', false)
             ->assertSee('name="closing_date" value="' . $this->today . '"', false)
             ->assertDontSee('pu-form', false);
    }

    public function test_uang_kurang_dijurnal_ke_biaya_uang_hilang(): void
    {
        $this->tutup(['physical_cash' => '480,000', 'draw_amount' => '400.000', 'note' => 'salah kembalian'])
             ->assertRedirect('/admin/transaction/resumeTotal/' . $this->today . '/' . $this->today)
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
        $this->assertSame('Pengambilan uang tanggal ' . $this->today, $draw->name);
    }

    public function test_uang_lebih_dijurnal_ke_pendapatan_lain(): void
    {
        $this->tutup(['physical_cash' => '510000'])->assertSessionHasNoErrors();

        $this->assertEquals(10000, CashClosing::firstOrFail()->difference);
        $this->assertEquals(510000, $this->balance('1111'));
        $this->assertEquals(-10000, $this->balance('6101'), 'pendapatan lain di sisi kredit');
        $this->assertCount(0, $this->journals('cash_draw'), 'tidak ada uang yang diserahkan');
    }

    public function test_uang_pas_tanpa_jurnal_selisih_dan_hanya_sekali_per_tanggal(): void
    {
        $this->tutup(['physical_cash' => '500000', 'draw_amount' => '450000'])->assertSessionHasNoErrors();

        $this->assertCount(0, $this->journals('cash_adjustment'));
        $this->assertEquals(50000, $this->balance('1111'));

        $this->tutup(['physical_cash' => '50000', 'system_balance_seen' => '50000.00'])
             ->assertSessionHasErrors('closing_date');
        $this->assertSame(1, CashClosing::count());

        $this->resumeTotal($this->today)
             ->assertOk()->assertSee('Kasir tanggal ini sudah ditutup')->assertDontSee('name="laci_uang[]"', false);
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

    public function test_kasir_bisa_tutup_kasir_dari_resume_total(): void
    {
        $cashier = $this->cashier();
        $this->resumeTotal($this->today, null, 'cashier', $cashier)
             ->assertOk()->assertSee('name="laci_uang[]"', false)
             ->assertSee("'/cashier/transaction/resumeTotal/'", false);

        $this->tutup(['physical_cash' => '500000', 'draw_amount' => '500000'], 'cashier', $cashier)
             ->assertRedirect('/cashier/transaction/resumeTotal/' . $this->today . '/' . $this->today);

        $closing = CashClosing::firstOrFail();
        $this->assertSame('cashier', $closing->role);
        $this->assertSame($cashier->id, $closing->role_id);
        $this->assertEquals(0, $this->balance('1111'));
    }

    public function test_lupa_kemarin_bisa_diisi_dengan_saldo_sampai_kemarin(): void
    {
        $this->kasMasuk($this->yesterday, 200000);

        // Saldo sampai akhir kemarin hanya 200.000; 500.000 hari ini tidak ikut.
        $this->resumeTotal($this->yesterday)
             ->assertOk()->assertSee('Tanggal lampau')->assertSee('Rp200,000.00')
             ->assertSee('name="closing_date" value="' . $this->yesterday . '"', false);

        $this->tutup(['physical_cash' => '195000', 'draw_amount' => '150000',
                      'closing_date' => $this->yesterday, 'system_balance_seen' => '200000.00'])
             ->assertRedirect('/admin/transaction/resumeTotal/' . $this->yesterday . '/' . $this->yesterday);

        $closing = CashClosing::firstOrFail();
        $this->assertSame($this->yesterday, $closing->closing_date->format('Y-m-d'));
        $this->assertEquals(200000, $closing->system_balance);
        $this->assertEquals(-5000, $closing->difference);
        $this->assertSame($this->yesterday, $this->journals('cash_draw')->sole()->journal_date);
        $this->assertSame($this->yesterday, $this->journals('cash_adjustment')->sole()->journal_date);

        // Hari ini: 195.000 - 150.000 sisa kemarin + 500.000 hari ini.
        $this->resumeTotal($this->today)->assertOk()->assertSee('Rp545,000.00')->assertDontSee('Tanggal lampau');
        $this->tutup(['physical_cash' => '545000', 'system_balance_seen' => '545000.00'])->assertSessionHasNoErrors();
        $this->assertSame(2, CashClosing::count());
    }

    public function test_tidak_bisa_mengisi_tanggal_sebelum_tutup_kasir_terakhir_atau_besok(): void
    {
        $this->tutup(['physical_cash' => '500000'])->assertSessionHasNoErrors();

        $this->tutup(['physical_cash' => '0', 'closing_date' => $this->yesterday, 'system_balance_seen' => '0.00'])
             ->assertSessionHasErrors('closing_date');
        $this->resumeTotal($this->yesterday)->assertOk()->assertSee('Sudah ada tutup kasir tanggal')
             ->assertDontSee('name="laci_uang[]"', false);

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $this->tutup(['physical_cash' => '0', 'closing_date' => $tomorrow])->assertSessionHasErrors('closing_date');
        $this->resumeTotal($tomorrow)->assertOk()->assertSee('Tanggal ini belum terjadi');

        $this->assertSame(1, CashClosing::count());
    }

    public function test_periode_beberapa_hari_menampilkan_daftar_tutup_kasir(): void
    {
        $this->kasMasuk($this->yesterday, 200000);
        $this->tutup(['physical_cash' => '200000', 'closing_date' => $this->yesterday, 'system_balance_seen' => '200000.00']);

        $this->resumeTotal($this->yesterday, $this->today)
             ->assertOk()
             ->assertSee('tanggal awal dan tanggal akhir yang sama', false)
             ->assertSee('Tutup kasir di periode ini')
             ->assertSee(date('d/m/Y', strtotime('-1 day')))
             ->assertDontSee('name="laci_uang[]"', false);
    }

    public function test_batal_hanya_superadmin_dan_hanya_yang_terakhir(): void
    {
        $this->kasMasuk($this->yesterday, 200000);
        $this->tutup(['physical_cash' => '190000', 'draw_amount' => '100000', 'closing_date' => $this->yesterday, 'system_balance_seen' => '200000.00']);
        $this->tutup(['physical_cash' => '590000', 'system_balance_seen' => '590000.00']);
        [$kemarin, $hariIni] = [CashClosing::orderBy('closing_date')->first(), CashClosing::orderByDesc('closing_date')->first()];
        $superadmin = $this->admin('superadmin');

        $this->actingAs($this->admin(), 'admin')->post('/admin/cash-closing/' . $hariIni->id . '/cancel')->assertForbidden();

        $this->actingAs($superadmin, 'admin')->post('/admin/cash-closing/' . $kemarin->id . '/cancel')
             ->assertSessionHasErrors('cancel');
        $this->assertSame(2, CashClosing::count());

        $this->actingAs($superadmin, 'admin')->post('/admin/cash-closing/' . $hariIni->id . '/cancel')
             ->assertRedirect('/admin/transaction/resumeTotal/' . $this->today . '/' . $this->today);
        $this->actingAs($superadmin, 'admin')->post('/admin/cash-closing/' . $kemarin->id . '/cancel')
             ->assertSessionHasNoErrors();

        $this->assertSame(0, CashClosing::count());
        $this->assertEquals(700000, $this->balance('1111'), 'jurnal selisih & penarikan ikut terhapus');
        $this->assertEquals(0, $this->balance('5219'));
        $this->assertEquals(0, $this->balance('1113'));
    }

    private function jualCash(int $cashierId, float $total, string $date, string $type = 'normal'): void
    {
        DB::table('transactions')->insert([
            'type' => $type, 'role' => 'cashier', 'role_id' => $cashierId, 'member_id' => 1, 'payment' => 'cash',
            'total_item_price' => $total, 'total_sum_price' => $total, 'money_paid' => max($total, 0), 'money_returned' => 0,
            'created_at' => $date . ' 10:00:00', 'updated_at' => $date . ' 10:00:00',
        ]);
    }

    public function test_dua_kasir_dua_laci_dihitung_per_laci_dan_selisih_dari_total(): void
    {
        $kasirA = $this->cashier();
        $kasirB = $this->cashier();
        DB::table('cashiers')->where('id', $kasirA->id)->update(['name' => 'Kasir Depan']);
        DB::table('cashiers')->where('id', $kasirB->id)->update(['name' => 'Kasir Belakang']);
        $this->jualCash($kasirA->id, 300000, $this->today);
        $this->jualCash($kasirA->id, 20000, $this->today);
        $this->jualCash($kasirA->id, -5000, $this->today, 'retur');
        $this->jualCash($kasirB->id, 180000, $this->today);
        $this->jualCash($kasirB->id, 99000, $this->yesterday); // tanggal lain, tidak ikut
        DB::table('transactions')->insert(['type' => 'normal', 'role' => 'cashier', 'role_id' => $kasirB->id, 'payment' => 'transfer',
            'total_sum_price' => 70000, 'created_at' => $this->today . ' 11:00:00']); // transfer, tidak ikut

        // Satu baris per kasir yang ada penjualan cash, dengan panduannya.
        $this->resumeTotal($this->today)
             ->assertOk()
             ->assertSeeInOrder(['Kasir Depan', 'penjualan Rp315,000.00 (3 transaksi)', 'Kasir Belakang', 'penjualan Rp180,000.00'], false)
             ->assertSee('value="cashier:' . $kasirA->id . '"', false)
             ->assertSee('Sisa kemarin belum diketahui')
             ->assertSee('name="laci_tarik[]"', false)
             ->assertSee('+ Tambah laci');

        // Saldo sistem tetap satu: 500.000 dari setUp (jurnal penjualan tidak dibuat di tes ini).
        $this->tutup([
            'laci_kasir' => ['cashier:' . $kasirA->id, 'cashier:' . $kasirB->id, ''],
            'laci_nama'  => ['Kasir Depan', 'Kasir Belakang', ''],
            'laci_uang'  => ['300,000', '195,000', ''],
            'laci_tarik' => ['250,000', '200,000', ''],
        ])->assertSessionHasErrors('draw_amount'); // tarik laci belakang > uangnya
        $this->tutup([
            'laci_kasir' => ['cashier:' . $kasirA->id, 'cashier:' . $kasirB->id, ''],
            'laci_nama'  => ['Kasir Depan', 'Kasir Belakang', ''],
            'laci_uang'  => ['300,000', '195,000', ''],
            'laci_tarik' => ['250,000', '195,000', ''],
            'draw_amount' => '1', // diabaikan: penarikan dari jumlah per laci
        ])->assertSessionHasNoErrors();

        $closing = CashClosing::firstOrFail();
        $this->assertEquals(495000, $closing->physical_cash, 'jumlah semua laci');
        $this->assertEquals(-5000, $closing->difference, 'selisih resmi dari total');
        $this->assertLaci([
            ['key' => 'cashier:' . $kasirA->id, 'nama' => 'Kasir Depan', 'uang' => 300000, 'tarik' => 250000, 'panduan' => 315000,
             'penjualan_cash' => 315000, 'lain_cash' => 0, 'biaya_cash' => 0, 'sisa_kemarin' => null, 'perkiraan' => null],
            ['key' => 'cashier:' . $kasirB->id, 'nama' => 'Kasir Belakang', 'uang' => 195000, 'tarik' => 195000, 'panduan' => 180000],
        ], $closing->drawers, 'baris kosong dilewati, panduan dihitung ulang di server');
        $this->assertEquals(445000, $closing->draw_amount, 'penarikan = jumlah per laci');
        $draw = $this->journals('cash_draw')->sole();
        $this->assertEquals(445000, $draw->debit, 'satu jurnal penarikan');
        $this->assertEquals(445000, $this->balance('1113'));
        $this->assertEquals(5000, $this->balance('5219'));
        $this->assertEquals(50000, $this->balance('1111'));

        $this->resumeTotal($this->today)
             ->assertOk()->assertSee('Rincian per laci')
             ->assertSeeInOrder(['Kasir Depan', 'Rp315,000.00', 'Rp300,000.00', 'Rp250,000.00', 'Rp50,000.00',
                                 'Kasir Belakang', 'Rp180,000.00', 'Rp195,000.00'], false);
    }

    public function test_laci_tambahan_admin_dan_laci_bawaan_wajib_diisi(): void
    {
        // Laci bawaan (belum ada transaksi) wajib diisi walaupun admin menambah laci.
        $this->tutup(['laci_kasir' => ['', ''], 'laci_nama' => ['', 'Laci cadangan'], 'laci_uang' => ['200000', '300000']])
             ->assertSessionHasErrors('physical_cash');
        $this->assertSame(0, CashClosing::count());

        $this->tutup(['laci_kasir' => ['laci:1', '', ''], 'laci_nama' => ['', '', 'Laci cadangan'], 'laci_uang' => ['0', '200000', '300000']])
             ->assertSessionHasNoErrors();
        $this->assertLaci([
            ['key' => 'laci:1', 'nama' => 'Laci', 'uang' => 0, 'tarik' => 0],
            ['key' => 'tambahan:Laci tambahan 2', 'nama' => 'Laci tambahan 2', 'uang' => 200000, 'tarik' => 0, 'panduan' => null],
            ['key' => 'tambahan:Laci cadangan', 'nama' => 'Laci cadangan', 'uang' => 300000, 'tarik' => 0, 'panduan' => null],
        ], CashClosing::firstOrFail()->drawers);
        $this->assertEquals(0, CashClosing::firstOrFail()->difference);
    }

    public function test_transaksi_lain_dan_biaya_lain_kasir_masuk_panduan_lacinya(): void
    {
        $kasirA = $this->cashier();
        $kasirB = $this->cashier();
        DB::table('cashiers')->where('id', $kasirA->id)->update(['name' => 'Kasir Depan']);
        DB::table('cashiers')->where('id', $kasirB->id)->update(['name' => 'Kasir Belakang']);
        $this->jualCash($kasirA->id, 300000, $this->today);
        $this->jualCash($kasirB->id, 100000, $this->today);

        // Lewat form kasir yang asli: jual plastik/kardus dan bayar pengamen dari laci.
        $this->actingAs($kasirA, 'cashier')->post('/cashier/other-transaction/store',
            ['type' => 'box_transaction', 'money' => '10,000', 'note' => 'plastik'])->assertRedirect();
        $this->actingAs($kasirA, 'cashier')->post('/cashier/other-payment/store',
            ['debit_account_id' => '5220', 'payment' => 'cash', 'money' => '20,000', 'notes' => 'pengamen'])->assertRedirect();
        // Biaya lewat transfer tidak keluar dari laci.
        $this->actingAs($kasirB, 'cashier')->post('/cashier/other-payment/store',
            ['debit_account_id' => '5220', 'payment' => 'transfer', 'money' => '7,000', 'notes' => 'token'])->assertRedirect();
        // Jurnal lama tanpa penginput.
        DB::table('journals')->insert(['type' => 'box_transaction', 'journal_date' => $this->today, 'name' => 'kardus lama',
            'debit_account_id' => $this->acc['1111'], 'debit' => 5000, 'credit_account_id' => $this->acc['6101'], 'credit' => 5000]);

        $box = DB::table('journals')->where('type', 'box_transaction')->whereNotNull('created_by_role')->sole();
        $this->assertSame(['cashier', $kasirA->id], [$box->created_by_role, (int) $box->created_by_id], 'penginput jurnal tercatat');

        $this->resumeTotal($this->today)
             ->assertOk()
             ->assertSeeInOrder(['Kasir Depan', 'penjualan Rp300,000.00', '+ transaksi lain Rp10,000.00',
                                 'biaya lain Rp20,000.00', 'Rp290,000.00', 'Kasir Belakang', 'Rp100,000.00'], false)
             ->assertSee('tidak tercatat kasirnya')
             ->assertSee('Rp5,000.00');

        // Saldo: 500.000 + 10.000 - 20.000 + 5.000 (lama); biaya transfer tidak lewat kas.
        $this->tutup([
            'laci_kasir' => ['cashier:' . $kasirA->id, 'cashier:' . $kasirB->id],
            'laci_nama'  => ['Kasir Depan', 'Kasir Belakang'],
            'laci_uang'  => ['395,000', '100,000'],
            'system_balance_seen' => '495000.00',
        ])->assertSessionHasNoErrors();

        $this->assertLaci([
            ['nama' => 'Kasir Depan', 'uang' => 395000, 'panduan' => 290000, 'penjualan_cash' => 300000, 'lain_cash' => 10000, 'biaya_cash' => 20000],
            ['nama' => 'Kasir Belakang', 'uang' => 100000, 'panduan' => 100000],
        ], CashClosing::firstOrFail()->drawers);
        $this->assertEquals(0, CashClosing::firstOrFail()->difference);
    }

    public function test_kasir_hanya_mengisi_uang_laci_nama_dari_sistem(): void
    {
        $kasirA = $this->cashier();
        DB::table('cashiers')->where('id', $kasirA->id)->update(['name' => 'Kasir Depan']);
        $this->jualCash($kasirA->id, 300000, $this->today);

        $this->resumeTotal($this->today, null, 'cashier', $kasirA)
             ->assertOk()
             ->assertSee('<b>Kasir Depan</b>', false)
             ->assertSee('name="laci_kasir[]" value="cashier:' . $kasirA->id . '"', false)
             ->assertDontSee('type="text" class="form-control tk-nama"', false)
             ->assertDontSee('+ Tambah laci')
             ->assertDontSee('title="Hapus laci tambahan"', false);

        $form = fn (array $laci) => ['laci_kasir' => array_column($laci, 0), 'laci_nama' => array_column($laci, 1), 'laci_uang' => array_column($laci, 2)];
        $baris = ['cashier:' . $kasirA->id, 'Kasir Depan', '500,000'];

        // Kasir tidak bisa menambah laci.
        $this->tutup($form([$baris, ['', 'Laci rahasia', '1,000']]), 'cashier', $kasirA)
             ->assertSessionHasErrors(['physical_cash' => 'Kasir tidak bisa menambah laci.']);
        // Laci kasir wajib diisi.
        $this->tutup($form([['cashier:' . $kasirA->id, 'Kasir Depan', '']]), 'cashier', $kasirA)
             ->assertSessionHasErrors('physical_cash');
        // Laci yang tidak ada di sistem ditolak.
        $this->tutup($form([$baris, ['cashier:999999', 'Siapa', '0']]), 'cashier', $kasirA)
             ->assertSessionHasErrors('physical_cash');
        $this->assertSame(0, CashClosing::count());

        // Nama dari form diabaikan; nama laci dari sistem.
        $this->tutup($form([['cashier:' . $kasirA->id, 'Nama Diganti', '500,000']]), 'cashier', $kasirA)
             ->assertSessionHasNoErrors();
        $this->assertSame('Kasir Depan', CashClosing::firstOrFail()->drawers[0]['nama']);
    }

    public function test_tanpa_transaksi_ada_satu_laci_bawaan(): void
    {
        $kasir = $this->cashier();
        $this->resumeTotal($this->today, null, 'cashier', $kasir)
             ->assertOk()
             ->assertSee('name="laci_kasir[]" value="laci:1"', false)
             ->assertSee('<b>Laci</b>', false);

        $this->tutup(['laci_kasir' => ['laci:1'], 'laci_nama' => ['Laci'], 'laci_uang' => ['500,000']], 'cashier', $kasir)
             ->assertSessionHasNoErrors();
        $this->assertSame('Laci', CashClosing::firstOrFail()->drawers[0]['nama']);
        $this->assertEquals(0, CashClosing::firstOrFail()->difference);
    }

    public function test_daftar_perangkat_di_bawah_dan_bisa_disembunyikan(): void
    {
        if (! \App\Models\PosDevice::tabelAda()) {
            $this->markTestSkipped('Tabel Kasir HP belum ada di database tes.');
        }
        $hp = \App\Models\PosDevice::create(['name' => 'HP Lama', 'code' => 'HP77', 'is_active' => true]);
        $hp->forceFill(['last_seen_at' => now()->subHours(2)])->saveQuietly();

        $html = $this->resumeTotal($this->today)
             ->assertOk()
             ->assertSee('perangkat Kasir HP/PC tidak terhubung')
             ->assertSee('<details id="tk-perangkat"', false)
             ->getContent();
        $this->assertGreaterThan(strpos($html, 'Simpan Tutup Kasir'), strpos($html, '<details id="tk-perangkat"'), 'daftar perangkat di bawah form');
    }

    public function test_sisa_kemarin_dibawa_per_laci_dan_penarikan_jadi_satu_jurnal(): void
    {
        DB::table('journals')->where('name', 'modal')->delete(); // mulai dari kas kosong
        $kasirA = $this->cashier();
        $kasirB = $this->cashier();
        DB::table('cashiers')->where('id', $kasirA->id)->update(['name' => 'Kasir Depan']);
        DB::table('cashiers')->where('id', $kasirB->id)->update(['name' => 'Kasir Belakang']);
        $a = 'cashier:' . $kasirA->id;
        $b = 'cashier:' . $kasirB->id;

        // Kemarin: A jual 250.000, B jual 130.000 (jurnal kas dibuat terpisah di tes ini).
        $this->jualCash($kasirA->id, 250000, $this->yesterday);
        $this->jualCash($kasirB->id, 130000, $this->yesterday);
        $this->kasMasuk($this->yesterday, 380000);
        $this->tutup(['closing_date' => $this->yesterday, 'system_balance_seen' => '380000.00',
            'laci_kasir' => [$a, $b], 'laci_nama' => ['', ''], 'laci_uang' => ['250,000', '130,000'], 'laci_tarik' => ['200,000', '100,000']])
             ->assertSessionHasNoErrors();
        $this->assertEquals(300000, $this->journals('cash_draw')->sole()->debit);

        // Hari ini: hanya A yang jualan 100.000. Laci B masih berisi 30.000, jadi tetap harus dihitung.
        $this->jualCash($kasirA->id, 100000, $this->today);
        $this->kasMasuk($this->today, 100000);
        $this->resumeTotal($this->today)
             ->assertOk()
             ->assertDontSee('Sisa kemarin belum diketahui')
             ->assertSeeInOrder(['Kasir Depan', 'Rp50,000.00', 'Rp100,000.00', 'Rp150,000.00',
                                 'Kasir Belakang', 'Rp30,000.00', 'Rp0.00', 'Rp30,000.00'], false)
             ->assertSee('data-perkiraan="150000"', false);

        // Laci B wajib diisi walaupun tidak ada transaksi hari ini.
        $this->tutup(['system_balance_seen' => '180000.00', 'laci_kasir' => [$a], 'laci_nama' => [''], 'laci_uang' => ['147,000']])
             ->assertSessionHasErrors('physical_cash');

        $this->tutup(['system_balance_seen' => '180000.00',
            'laci_kasir' => [$a, $b], 'laci_nama' => ['', ''], 'laci_uang' => ['147,000', '30,000'], 'laci_tarik' => ['100,000', '']])
             ->assertSessionHasNoErrors();

        $closing = CashClosing::whereDate('closing_date', $this->today)->firstOrFail();
        $this->assertEquals(-3000, $closing->difference, 'selisih resmi dari total');
        $this->assertEquals(100000, $closing->draw_amount);
        $this->assertLaci([
            ['key' => $a, 'uang' => 147000, 'tarik' => 100000, 'sisa_kemarin' => 50000, 'panduan' => 100000, 'perkiraan' => 150000],
            ['key' => $b, 'uang' => 30000, 'tarik' => 0, 'sisa_kemarin' => 30000, 'panduan' => null, 'perkiraan' => 30000],
        ], $closing->drawers);
        $tarik = $this->journals('cash_draw')->where('journal_date', $this->today);
        $this->assertCount(1, $tarik, 'satu jurnal penarikan per tutup kasir');
        $this->assertEquals(100000, $tarik->first()->debit);

        // Ringkasan: selisih laci A kurang 3.000 terhadap perkiraannya.
        $this->resumeTotal($this->today)
             ->assertOk()
             ->assertSeeInOrder(['Rincian per laci', 'Kasir Depan', 'Rp50,000.00', 'Rp100,000.00', 'Rp150,000.00', 'Rp147,000.00', 'Kurang Rp3,000.00'], false);

        // Besok: sisa A 47.000, sisa B 30.000.
        $besok = collect(\App\Services\TutupKasir::daftarLaci(date('Y-m-d', strtotime('+1 day'))))->keyBy('key');
        $this->assertEquals(47000, $besok[$a]['sisa']);
        $this->assertEquals(30000, $besok[$b]['sisa']);
    }

    public function test_url_tutup_kasir_dijaga_hak_akses_menu(): void
    {
        $this->assertSame(['kas.tutup'], MenuAkses::menuUntukPath('cash-closing'));
        $this->assertSame(['kas.tutup', 'aksi.tutup_kasir.batal'], MenuAkses::menuUntukPath('cash-closing/5/cancel'));
        $this->assertTrue(MenuAkses::boleh('kas.tutup', 'admin'));
        $this->assertFalse(MenuAkses::boleh('aksi.tutup_kasir.batal', 'supervisor'));
    }
}
