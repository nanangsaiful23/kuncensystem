<?php

namespace Tests\Feature\Stock;

use App\Models\GoodMovementRepository;
use App\Models\ReorderRepository;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menu Perputaran Barang & Rekomendasi Reorder: status Kosong / Baru Masuk,
 * ambang fast yang mengikuti panjang periode, obral, dan musim.
 */
class GoodMovementTest extends StockTestCase
{
    use TabelObralMusim;
    use TabelSidakObral;

    protected function setUp(): void
    {
        $this->siapkanTabelObralMusim();
        $this->siapkanTabelSidakObral();

        parent::setUp();
    }

    private function good(string $name, array $set = [], int $boxQty = 0): array
    {
        [$good, $pcs, $box] = $this->makeGood($name, 1000, 1500, $boxQty);
        DB::table('goods')->where('id', $good)->update($set + ['last_loading' => Carbon::now()->subDays(200)->toDateString()]);

        return [$good, $pcs, $box];
    }

    /** Penjualan langsung di tabel (tanpa menghitung ulang stok). */
    private function sell(int $goodUnitId, float $qty, Carbon $at, float $unitQty = 1): void
    {
        $trx = DB::table('transactions')->insertGetId([
            'type' => 'normal', 'role' => 'admin', 'role_id' => 1, 'member_id' => 1,
            'total_item_price' => 1500 * $qty, 'total_sum_price' => 1500 * $qty,
            'created_at' => $at, 'updated_at' => $at,
        ]);
        DB::table('transaction_details')->insert([
            'transaction_id' => $trx, 'good_unit_id' => $goodUnitId, 'quantity' => $qty,
            'real_quantity' => $qty * $unitQty, 'buy_price' => 1000, 'selling_price' => 1500,
            'sum_price' => 1500 * $qty, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function movement(string $status = 'all', int $days = 90): array
    {
        return (new GoodMovementRepository())->getMovementData(
            Carbon::now()->subDays($days)->toDateString(), Carbon::now()->toDateString(), null, $status
        );
    }

    private function row(array $data, int $good)
    {
        return $data['goods']->firstWhere('good_id', $good);
    }

    private function reorderItem(int $good)
    {
        $data = (new ReorderRepository())->getReorderRecommendations(
            Carbon::now()->subDays(90)->toDateString(), Carbon::now()->toDateString(), null, null, false
        );

        return $data['groups']->flatMap(fn ($g) => $g->items)->firstWhere('good_id', $good);
    }

    private function season(string $name, array $periods, bool $repeats = false, int $prep = 30): int
    {
        $id = DB::table('seasons')->insertGetId(['name' => $name, 'repeats_yearly' => $repeats, 'prep_days' => $prep]);
        foreach ($periods as [$start, $end]) {
            DB::table('season_periods')->insert(['season_id' => $id, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]);
        }

        return $id;
    }

    private function type(string $name, ?int $seasonId = null): int
    {
        return DB::table('types')->insertGetId(['name' => $name, 'category_id' => 1, 'season_id' => $seasonId]);
    }

    public function test_barang_habis_tidak_masuk_dead_stock_dan_stok_minus_tidak_mengurangi_nilai_stok()
    {
        [$habis]  = $this->good('Habis', ['last_stock' => 0]);
        [$mati]   = $this->good('Mati', ['last_stock' => 5]);
        [$minus]  = $this->good('Minus', ['last_stock' => -3]);

        $data = $this->movement();

        $this->assertSame('kosong', $this->row($data, $habis)->status);
        $this->assertSame('kosong', $this->row($data, $minus)->status);
        $this->assertSame('dead', $this->row($data, $mati)->status);
        $this->assertSame(1, $data['summary']['deadCount']);
        $this->assertSame(2, $data['summary']['kosongCount']);
        $this->assertEquals(5000, $data['summary']['nilaiDead']);
        $this->assertEquals(0, $this->row($data, $minus)->nilai_stok);
        $this->assertEquals(5000, $data['summary']['totalNilaiStok']);
    }

    public function test_barang_baru_masuk_belum_dinilai()
    {
        [$baru] = $this->good('Baru', ['last_stock' => 10, 'last_loading' => Carbon::now()->subDays(3)->toDateString()]);

        $row = $this->row($this->movement(), $baru);

        $this->assertSame('baru', $row->status);
        $this->assertSame('wait', $row->recommendation);
    }

    public function test_ringkasan_menghitung_semua_status_walau_satu_tab_dibuka_dan_hari_periode_bulat()
    {
        $this->good('Habis', ['last_stock' => 0]);
        [$mati] = $this->good('Mati', ['last_stock' => 5]);

        $data = $this->movement('dead');

        $this->assertSame([$mati], $data['goods']->pluck('good_id')->all());
        $this->assertSame(1, $data['summary']['kosongCount']);
        $this->assertSame(91, $data['days']);
    }

    public function test_batas_fast_mengikuti_panjang_periode()
    {
        [$good, $pcs] = $this->good('Laris', ['last_stock' => 50]);
        foreach ([2, 5, 9, 14] as $d) {
            $this->sell($pcs, 1, Carbon::now()->subDays($d));
        }

        $this->assertSame('fast', $this->row($this->movement('all', 30), $good)->status);  // ≥4 trx / 30 hari
        $this->assertSame('slow', $this->row($this->movement('all', 90), $good)->status);  // butuh ≥10 trx / 90 hari
    }

    public function test_obral_tidak_jadi_fast_tidak_direorder_dan_otomatis_discontinue_saat_habis()
    {
        [$good, $pcs] = $this->good('Obral', ['last_stock' => 20]);
        $repo = new GoodMovementRepository();
        $admin = $this->admin();

        $this->assertSame(1, $repo->markClearance([$good], 'Dead stock dihabiskan', $admin->id));
        DB::table('goods')->where('id', $good)->update(['clearance_at' => Carbon::now()->subDays(20)]);
        for ($d = 1; $d <= 15; $d++) {
            $this->sell($pcs, 1, Carbon::now()->subDays($d));
        }
        DB::table('goods')->where('id', $good)->update(['last_stock' => 5]);

        $row = $this->row($this->movement(), $good);
        $this->assertSame('obral', $row->status);
        $this->assertSame('clearance_run', $row->recommendation);
        $this->assertEquals(15, $row->obral_terjual);
        $this->assertEquals(20, $row->obral_stok_awal);
        $this->assertNull($this->reorderItem($good), 'barang obral tidak boleh muncul di Rekomendasi Reorder');

        // Stok belum habis: tidak diselesaikan.
        $this->assertSame(0, $repo->finishSoldOutClearance());

        DB::table('goods')->where('id', $good)->update(['last_stock' => 0]);
        $this->assertSame(1, $repo->finishSoldOutClearance());

        $g = DB::table('goods')->find($good);
        $this->assertSame(1, (int) $g->is_discontinued);
        $this->assertSame(0, (int) $g->is_clearance);
        $this->assertStringStartsWith('Habis obral', $g->discontinued_reason);
        $this->assertSame($admin->id, (int) $g->discontinued_by);
    }

    public function test_obral_hanya_untuk_barang_yang_stoknya_ada()
    {
        [$good] = $this->good('Kosong', ['last_stock' => 0]);

        $this->assertSame(0, (new GoodMovementRepository())->markClearance([$good], 'Coba obral', 1));
    }

    public function test_barang_musiman_di_luar_musim_tidak_dicap_dead_dan_tidak_direorder()
    {
        $hujan = $this->season('Musim Hujan', [[Carbon::now()->addDays(100), Carbon::now()->addDays(130)]], true);
        $type  = $this->type('Jas hujan', $hujan);
        [$good, $pcs] = $this->good('Plastik', ['last_stock' => 0, 'type_id' => $type]);
        $this->sell($pcs, 2, Carbon::now()->subDays(20));
        $this->sell($pcs, 2, Carbon::now()->subDays(40));

        $row = $this->row($this->movement(), $good);
        $this->assertSame('musiman', $row->status);
        $this->assertSame('season_hold', $row->recommendation);
        $this->assertSame('Musim Hujan', $row->musim_nama);
        $this->assertSame('jenis', $row->musim_sumber);

        $item = $this->reorderItem($good);
        $this->assertSame(4, $item->urgensi);
        $this->assertSame(0, $item->reorder_qty_raw);

        // Dikecualikan per barang: dinilai seperti biasa lagi.
        DB::table('goods')->where('id', $good)->update(['season_id' => 0]);
        $this->assertNotSame('musiman', $this->row($this->movement(), $good)->status);
        $this->assertNotSame(4, $this->reorderItem($good)->urgensi);
    }

    public function test_menjelang_musim_disarankan_siapkan_stok_sebanyak_penjualan_musim_lalu()
    {
        $lalu = [Carbon::now()->subDays(355), Carbon::now()->subDays(325)];
        $lebaran = $this->season('Lebaran', [$lalu, [Carbon::now()->addDays(10), Carbon::now()->addDays(40)]], false, 30);
        $type = $this->type('Kue kering', $lebaran);
        [$good, $pcs] = $this->good('Nastar', ['last_stock' => 5, 'type_id' => $type]);
        $this->sell($pcs, 20, Carbon::now()->subDays(350));
        $this->sell($pcs, 10, Carbon::now()->subDays(330));

        $row = $this->row($this->movement(), $good);
        $this->assertSame('musiman', $row->status);
        $this->assertSame('season_prep', $row->recommendation);
        $this->assertEquals(30, $row->musim_lalu_qty);

        $item = $this->reorderItem($good);
        $this->assertSame('Siapkan Stok Musim', $item->urgensi_label);
        $this->assertSame(25, $item->reorder_qty_raw);
    }

    public function test_pola_penjualan_terkumpul_dua_bulan_disarankan_sebagai_kandidat_musiman()
    {
        [$good, $pcs] = $this->good('Terompet', ['last_stock' => 30]);
        $puncak = Carbon::now()->startOfMonth()->subMonths(5)->addDays(5);
        for ($i = 0; $i < 8; $i++) {
            $this->sell($pcs, 3, $puncak->copy()->addDays($i * 4));
        }
        $this->sell($pcs, 1, Carbon::now()->subMonths(9));

        $row = $this->row($this->movement(), $good);

        $this->assertNotNull($row->kandidat_musim);
        $this->assertGreaterThanOrEqual(60, $row->kandidat_musim['persen']);
        $this->assertSame(1, $this->movement('kandidat_musim')['goods']->where('good_id', $good)->count());
    }

    public function test_reorder_menghitung_stok_dalam_satuan_terkecil()
    {
        [$good, $pcs, $box] = $this->good('Mie', ['last_stock' => 2], 12);
        DB::table('goods')->where('id', $good)->update(['base_unit_id' => $box]); // stok dihitung per dus
        $this->sell($pcs, 30, Carbon::now()->subDays(5));

        $item = $this->reorderItem($good);

        $this->assertEquals(24, $item->stok_sekarang); // 2 dus × 12 pcs
        $this->assertEquals(30, $item->qty_terjual);
    }

    public function test_status_musim_berulang_yang_melewati_akhir_tahun()
    {
        $id = $this->season('Natal & Tahun Baru', [[Carbon::create(2020, 12, 1), Carbon::create(2021, 1, 5)]], true, 30);
        $season = Season::with('periods')->find($id);

        $this->assertSame(Season::MUSIM, $season->statusPada(Carbon::create(2026, 1, 2, 10))['status']);
        $this->assertSame(Season::PERSIAPAN, $season->statusPada(Carbon::create(2026, 11, 20, 10))['status']);
        $this->assertSame(Season::LUAR, $season->statusPada(Carbon::create(2026, 6, 1, 10))['status']);
    }

    public function test_halaman_perputaran_musim_dan_aksi_obral()
    {
        $admin = $this->admin('superadmin');
        [$good] = $this->good('Sabun', ['last_stock' => 8]);

        $this->actingAs($admin, 'admin')->get('/admin/reports/movement')
            ->assertOk()->assertSee('Modal Tertahan')->assertSee('Kandidat Musiman');

        $this->actingAs($admin, 'admin')
            ->post('/admin/reports/movement/obral', ['ids' => [$good], 'reason' => 'Habiskan stok lama'])
            ->assertRedirect();
        $this->assertSame(1, (int) DB::table('goods')->where('id', $good)->value('is_clearance'));

        $this->actingAs($admin, 'admin')->get('/admin/reports/movement?status=obral')->assertOk()->assertSee('Batal Obral');

        $this->actingAs($admin, 'admin')->get('/admin/reports/movement/musim')->assertOk()->assertSee('Tambah Musim');
        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/musim', [
            'name' => 'Tahun Ajaran Baru', 'start_date' => '2026-06-15', 'end_date' => '2026-07-31',
            'prep_days' => 30, 'repeats_yearly' => 1,
        ])->assertRedirect();
        $season = DB::table('seasons')->where('name', 'Tahun Ajaran Baru')->first();
        $this->assertNotNull($season);

        $type = $this->type('Buku tulis');
        $this->actingAs($admin, 'admin')->get('/admin/reports/movement/musim/' . $season->id)->assertOk()->assertSee('Buku tulis');
        $this->actingAs($admin, 'admin')->post('/admin/reports/movement/musim/' . $season->id, [
            'name' => 'Tahun Ajaran Baru', 'prep_days' => 45, 'repeats_yearly' => 1, 'type_ids' => [$type],
        ])->assertRedirect();
        $this->assertSame($season->id, (int) DB::table('types')->where('id', $type)->value('season_id'));

        // Atur musim per barang dari menu Perputaran.
        DB::table('goods')->where('id', $good)->update(['type_id' => $type]);
        $this->actingAs($admin, 'admin')
            ->post('/admin/reports/movement/' . $good . '/musim', ['scope' => 'barang', 'season_id' => 0])
            ->assertRedirect();
        $this->assertSame(0, (int) DB::table('goods')->where('id', $good)->value('season_id'));

        $this->actingAs($admin, 'admin')->get('/admin/reports/reorder?only_needed=0')->assertOk();
    }
}
