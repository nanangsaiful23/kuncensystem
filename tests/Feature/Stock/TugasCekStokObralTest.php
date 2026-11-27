<?php

namespace Tests\Feature\Stock;

use App\Models\GoodMovementRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tugas cek stok (Sidak berisi daftar: dari centang Perputaran Barang atau
 * "Sidak dead stock") dan harga obral (harga normal disimpan, label & teks,
 * harga kembali normal saat obral selesai).
 */
class TugasCekStokObralTest extends StockTestCase
{
    use TabelObralMusim;
    use TabelSidakObral;

    protected function setUp(): void
    {
        $this->siapkanTabelObralMusim();
        $this->siapkanTabelSidakObral();

        parent::setUp();
    }

    /** Barang lama (loading 200 hari lalu) dengan stok di kolom goods. */
    private function good(string $name, float $stock, int $boxQty = 0): array
    {
        [$good, $pcs, $box] = $this->makeGood($name, 1000, 1500, $boxQty);
        DB::table('goods')->where('id', $good)->update([
            'last_stock' => $stock, 'last_loading' => Carbon::now()->subDays(200)->toDateString(),
        ]);

        return [$good, $pcs, $box];
    }

    private function inspection(): int
    {
        return (int) DB::table('stock_inspections')->orderByDesc('id')->value('id');
    }

    public function test_tugas_cek_stok_dari_centang_sampai_stock_opname_dan_discontinue(): void
    {
        $admin = $this->admin('superadmin');
        [$hilang, $hilangPcs] = $this->makeGood('Sabun Hilang');
        [$ada, $adaPcs]       = $this->makeGood('Sikat Ada');
        $this->load($hilangPcs, 6, 1000);
        $this->load($adaPcs, 4, 1000);

        // urutan centang dipertahankan: nomor 1 = barang pertama yang dicentang
        $this->actingAs($admin, 'admin')->post('/admin/sidak-stok', [
            'note' => 'Cek fisik dead stock', 'counted_by' => 'Rina', 'good_ids' => [$ada, $hilang],
        ])->assertRedirect();
        $id = $this->inspection();
        $this->assertSame('Rina', DB::table('stock_inspections')->where('id', $id)->value('counted_by'));
        $this->assertSame([$ada, $hilang], DB::table('stock_inspection_targets')->where('stock_inspection_id', $id)
            ->orderBy('position')->pluck('good_id')->map(fn ($v) => (int) $v)->all());

        $this->actingAs($admin, 'admin')->get('/admin/sidak-stok/' . $id)
            ->assertOk()->assertSee('Daftar cek stok')->assertSee('Copy teks WA')->assertSee('Sikat Ada');

        // diketik dari balasan WA
        $this->actingAs($admin, 'admin')->postJson('/admin/sidak-stok/' . $id . '/cek', ['good_id' => $hilang, 'real_stock' => 0, 'source' => 'daftar'])
            ->assertOk()->assertJsonPath('detail.difference', -6);
        $this->actingAs($admin, 'admin')->postJson('/admin/sidak-stok/' . $id . '/cek', ['good_id' => $ada, 'real_stock' => 4, 'source' => 'daftar'])
            ->assertOk();

        $this->actingAs($admin, 'admin')->post('/admin/sidak-stok/' . $id . '/selesai', ['counted_by' => 'Rina'])->assertRedirect();
        $this->actingAs($admin, 'admin')->post('/admin/sidak-stok/' . $id . '/stock-opname')->assertRedirect();

        // pegawai yang menghitung jadi pemeriksa, yang menginput di belakangnya
        $opname = DB::table('stock_opnames')->orderByDesc('id')->first();
        $this->assertSame('Rina (input: Admin Test)', $opname->checker);
        $this->assertEquals(0, $this->stock($hilang));
        $this->assertEquals(4, $this->stock($ada));

        // barang yang fisiknya 0 ditawarkan untuk di-discontinue
        $this->actingAs($admin, 'admin')->get('/admin/sidak-stok/' . $id)
            ->assertOk()->assertSee('Barang yang tidak ada di toko')->assertSee('Discontinue yang dicentang');
        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/bulk-discontinue', [
            'ids' => [$hilang], 'reason' => 'Sidak stok #' . $id . ': barang tidak ada di toko (stok fisik 0)',
        ])->assertRedirect();
        $this->assertSame(1, (int) DB::table('goods')->where('id', $hilang)->value('is_discontinued'));
        $this->assertSame(0, (int) DB::table('goods')->where('id', $ada)->value('is_discontinued'));
    }

    public function test_sidak_dead_stock_hanya_dead_stock_yang_stoknya_ada_dan_belum_dicek(): void
    {
        $admin = $this->admin('superadmin');
        [$dead]   = $this->good('Payung Lama', 8);
        [$besar]  = $this->good('Kompor Lama', 3);
        DB::table('good_units')->where('good_id', $besar)->update(['buy_price' => 90000]); // modal tertahan lebih besar
        [$habis]  = $this->good('Lilin Habis', 0);
        [$dicek]  = $this->good('Senter Dicek', 5);

        // Senter sudah dicek 10 hari lalu → dilewati
        $old = DB::table('stock_inspections')->insertGetId(['role' => 'admin', 'role_id' => $admin->id, 'finished_at' => Carbon::now()->subDays(10),
            'created_at' => Carbon::now()->subDays(10), 'updated_at' => Carbon::now()->subDays(10)]);
        DB::table('stock_inspection_details')->insert(['stock_inspection_id' => $old, 'good_id' => $dicek, 'system_stock' => 5, 'real_stock' => 5,
            'created_at' => Carbon::now()->subDays(10), 'updated_at' => Carbon::now()->subDays(10)]);

        $this->actingAs($admin, 'admin')->post('/admin/sidak-stok/dead-stock', ['limit' => 10, 'counted_by' => 'Budi'])->assertRedirect();
        $id = $this->inspection();

        $this->assertNotSame($old, $id);
        $this->assertSame([$besar, $dead], DB::table('stock_inspection_targets')->where('stock_inspection_id', $id)
            ->orderBy('position')->pluck('good_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('Budi', DB::table('stock_inspections')->where('id', $id)->value('counted_by'));
        $this->assertNotContains($habis, [$besar, $dead]);

        $this->actingAs($admin, 'admin')->get('/admin/sidak-stok')->assertOk()->assertSee('Sidak dead stock')->assertSee('Daftar 0/2 barang dicek');
    }

    public function test_obral_dengan_harga_menyimpan_harga_normal_dan_batal_mengembalikannya(): void
    {
        $admin = $this->admin('superadmin');
        [$good, $pcs, $box] = $this->good('Biskuit Lama', 8, 10);

        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/obral', [
            'ids' => [$good], 'reason' => 'Dead stock 6 bulan',
            'prices' => [$pcs => 1200, $box => 12000],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, (int) DB::table('goods')->where('id', $good)->value('is_clearance'));
        $this->assertEquals(1200, DB::table('good_units')->where('id', $pcs)->value('selling_price'));
        $this->assertEquals(1500, DB::table('good_units')->where('id', $pcs)->value('clearance_normal_price'));
        $this->assertEquals(12000, DB::table('good_units')->where('id', $box)->value('selling_price'));
        $this->assertEquals(15000, DB::table('good_units')->where('id', $box)->value('clearance_normal_price'));
        $this->assertSame(2, DB::table('good_prices')->where('reason', 'Obral: Dead stock 6 bulan')->count());

        // data untuk teks WA & jendela obral
        $this->actingAs($admin, 'admin')->getJson('/admin/reports/movement/obral/barang?ids[]=' . $good)
            ->assertOk()->assertJsonPath('goods.0.units.0.normal_price', 1500)->assertJsonPath('goods.0.units.0.selling_price', 1200);

        // label rak harga coret
        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/obral/label', ['ids' => [$good], 'layout' => 4])
            ->assertOk()->assertSee('OBRAL')->assertSee('HEMAT Rp300')->assertSee('SELAMA STOK ADA');

        $this->actingAs($admin, 'admin')->get('/admin/reports/movement?status=obral')
            ->assertOk()->assertSee('Label Obral')->assertSee('Teks WA Obral');

        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/' . $good . '/batal-obral')->assertRedirect();
        $this->assertEquals(1500, DB::table('good_units')->where('id', $pcs)->value('selling_price'));
        $this->assertEquals(15000, DB::table('good_units')->where('id', $box)->value('selling_price'));
        $this->assertNull(DB::table('good_units')->where('id', $pcs)->value('clearance_normal_price'));
        $this->assertSame(0, (int) DB::table('goods')->where('id', $good)->value('is_clearance'));
    }

    public function test_harga_obral_di_bawah_modal_ditolak_sama_dengan_modal_boleh(): void
    {
        $admin = $this->admin('superadmin');
        [$good, $pcs] = $this->good('Permen Lama', 8);

        $this->actingAs($admin, 'admin')->from('/admin/reports/movement')->post('/admin/reports/movement/obral', [
            'ids' => [$good], 'reason' => 'Dead stock lama', 'prices' => [$pcs => 900],
        ])->assertRedirect('/admin/reports/movement')->assertSessionHasErrors('harga');
        $this->assertSame(0, (int) DB::table('goods')->where('id', $good)->value('is_clearance'));
        $this->assertEquals(1500, DB::table('good_units')->where('id', $pcs)->value('selling_price'));

        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/obral', [
            'ids' => [$good], 'reason' => 'Dead stock lama', 'prices' => [$pcs => 1000],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(1000, DB::table('good_units')->where('id', $pcs)->value('selling_price'));
    }

    public function test_obral_habis_stok_harganya_kembali_normal(): void
    {
        $admin = $this->admin('superadmin');
        [$good, $pcs] = $this->good('Teh Lama', 8);

        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/obral', [
            'ids' => [$good], 'reason' => 'Habiskan stok', 'prices' => [$pcs => 1100],
        ])->assertRedirect();
        DB::table('goods')->where('id', $good)->update(['last_stock' => 0]);

        $this->assertSame(1, (new GoodMovementRepository())->finishSoldOutClearance());
        $this->assertSame(1, (int) DB::table('goods')->where('id', $good)->value('is_discontinued'));
        $this->assertEquals(1500, DB::table('good_units')->where('id', $pcs)->value('selling_price'));
        $this->assertNull(DB::table('good_units')->where('id', $pcs)->value('clearance_normal_price'));
        $this->assertSame(1, DB::table('good_prices')->where('good_unit_id', $pcs)->where('reason', 'like', 'Obral selesai (stok habis)%')->count());
    }

    public function test_perputaran_menampilkan_tanda_dicek_dan_tombol_tugas_cek_stok(): void
    {
        $admin = $this->admin('superadmin');
        [$good] = $this->good('Gunting Lama', 4);

        $at = Carbon::now()->subDays(3);
        $sid = DB::table('stock_inspections')->insertGetId(['role' => 'admin', 'role_id' => $admin->id, 'finished_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
        DB::table('stock_inspection_details')->insert(['stock_inspection_id' => $sid, 'good_id' => $good, 'system_stock' => 4, 'real_stock' => 4,
            'created_at' => $at, 'updated_at' => $at]);

        $this->actingAs($admin, 'admin')->get('/admin/reports/movement?status=dead')
            ->assertOk()->assertSee('dicek 3 hr lalu')->assertSee('Tugas Cek Stok')->assertSee('data-can-cek="1"', false);
    }
}
