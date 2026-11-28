<?php

namespace Tests\Feature\Stock;

use App\Models\StockOpname;
use App\Services\BarangSeringHilang;
use App\Services\StockOpnameService;
use Illuminate\Support\Facades\DB;

/**
 * Stock opname: stok dikoreksi per barang, jurnalnya satu per SO (kurang dan
 * lebih terpisah), transaksi/loading hasil SO terkunci, dan laporan Barang
 * Sering Hilang.
 */
class StockOpnameJurnalTest extends StockTestCase
{
    /** Barang dengan stok awal dari loading. Returns [good_id, good_unit_id, unit_id]. */
    private function good(string $name, float $stock, float $buy): array
    {
        [$good, $pcs] = $this->makeGood($name, $buy, $buy * 1.5);
        if ($stock > 0) {
            $this->load($pcs, $stock, $buy);
        }

        return [$good, $pcs, (int) DB::table('good_units')->where('id', $pcs)->value('unit_id')];
    }

    /** POST form stock opname. $rows: [[good_id, unit_id, stok fisik], ...] */
    private function opname($admin, array $rows, string $note = 'SO rak depan'): int
    {
        $form = ['note' => $note, 'checker' => 'Rina'];
        foreach ($rows as [$good, $unit, $fisik]) {
            $form['names'][] = $good;
            $form['units'][] = $unit;
            $form['old_stocks'][] = 999; // diabaikan: stok lama diambil dari server
            $form['new_stocks'][] = $fisik;
        }
        $form['names'][] = null; // baris kosong di form

        $this->actingAs($admin, 'admin')->post('/admin/stock-opname/store', $form)->assertRedirect();

        return (int) DB::table('stock_opnames')->orderByDesc('id')->value('id');
    }

    private function jurnalSo(int $id)
    {
        return DB::table('journals')->whereNull('deleted_at')
            ->where('type', 'stock_opname')->where('type_id', $id)->orderBy('id')->get();
    }

    public function test_satu_stock_opname_satu_jurnal_kurang_dan_satu_jurnal_lebih(): void
    {
        $admin = $this->admin('superadmin');
        [$a, , $ua] = $this->good('Sabun A', 10, 1000);
        [$b, , $ub] = $this->good('Sabun B', 5, 2000);
        [$c, , $uc] = $this->good('Sabun C', 4, 500);
        [$d, , $ud] = $this->good('Sabun D', 3, 700);

        $id = $this->opname($admin, [[$a, $ua, 7], [$b, $ub, 0], [$c, $uc, 6], [$d, $ud, 3]]);

        // stok dikoreksi per barang
        $this->assertEquals(7, $this->stock($a));
        $this->assertEquals(0, $this->stock($b));
        $this->assertEquals(6, $this->stock($c));
        $this->assertEquals(3, $this->stock($d));
        $this->assertSame(2, DB::table('transactions')->where('type', 'stock_opname')->count());
        $this->assertSame(1, DB::table('good_loadings')->where('payment', 'by system')->count());

        // jurnal: satu untuk semua yang kurang, satu untuk semua yang lebih, tidak dihitung bersih
        $jurnal = $this->jurnalSo($id);
        $this->assertCount(2, $jurnal);
        [$rugi, $lebih] = [$jurnal[0], $jurnal[1]];
        $this->assertEquals(13000, $rugi->debit);
        $this->assertSame($this->acc['5215'], (int) $rugi->debit_account_id);
        $this->assertSame($this->acc['1141'], (int) $rugi->credit_account_id);
        $this->assertEquals(1000, $lebih->debit);
        $this->assertSame($this->acc['1141'], (int) $lebih->debit_account_id);
        $this->assertSame($this->acc['5215'], (int) $lebih->credit_account_id);
        $this->assertSame(2, DB::table('journals')->count(), 'tidak ada jurnal per barang');

        // keterangan: ringkasan + barang rugi terbesar dulu
        $this->assertStringStartsWith('Rugi stock opname #' . $id . ' ', $rugi->name);
        $this->assertStringContainsString('2 barang Rp13.000', $rugi->name);
        $this->assertStringContainsString('Terbesar: Sabun B -5 pcs Rp10.000; Sabun A -3 pcs Rp3.000', $rugi->name);
        $this->assertStringStartsWith('Lebih stock opname #' . $id . ' ', $lebih->name);
        $this->assertStringContainsString('Sabun C +2 pcs Rp1.000', $lebih->name);

        $this->assertEquals(12000, $this->balance('5215'));
        $this->assertEquals(-12000, $this->balance('1141'));

        $so = DB::table('stock_opnames')->where('id', $id)->first();
        $this->assertEquals(-12000, $so->total);
        // barang yang stoknya cocok bernilai 0 (dulu ikut nilai baris sebelumnya)
        $this->assertEquals(0, DB::table('stock_opname_details')->where('stock_opname_id', $id)
            ->where('good_unit_id', DB::table('good_units')->where('good_id', $d)->value('id'))->value('total'));
    }

    public function test_semua_stok_cocok_tidak_membuat_jurnal(): void
    {
        $admin = $this->admin('superadmin');
        [$a, , $ua] = $this->good('Teh Cocok', 4, 1000);

        $id = $this->opname($admin, [[$a, $ua, 4]]);

        $this->assertCount(0, $this->jurnalSo($id));
        $this->assertEquals(0, DB::table('stock_opnames')->where('id', $id)->value('total'));
    }

    public function test_keterangan_jurnal_tidak_lebih_dari_255_karakter(): void
    {
        $opname = new StockOpname();
        $opname->id = 77;

        $items = [];
        for ($i = 1; $i <= 40; $i++) {
            $items[] = ['nama' => 'Barang dengan nama yang cukup panjang nomor ' . $i, 'satuan' => 'pcs', 'selisih' => -$i, 'nilai' => $i * 1000];
        }

        $teks = StockOpnameService::keterangan($opname, 'kurang', $items, 820000);

        $this->assertLessThanOrEqual(255, mb_strlen($teks));
        $this->assertStringStartsWith('Rugi stock opname #77 ', $teks);
        $this->assertStringContainsString('40 barang Rp820.000 | Terbesar: Barang dengan nama yang cukup panjang nomor 40 -40 pcs Rp40.000', $teks);
        $this->assertMatchesRegularExpression('/; \+\d+ lainnya$/', $teks);
    }

    public function test_transaksi_dan_loading_hasil_stock_opname_tidak_bisa_diedit_atau_dihapus(): void
    {
        $admin = $this->admin('superadmin');
        [$a, , $ua] = $this->good('Kopi Kurang', 5, 1000);
        [$b, , $ub] = $this->good('Kopi Lebih', 5, 1000);
        $this->opname($admin, [[$a, $ua, 3], [$b, $ub, 6]]);

        $trx     = (int) DB::table('transactions')->where('type', 'stock_opname')->value('id');
        $loading = (int) DB::table('good_loadings')->where('payment', 'by system')->value('id');

        $this->actingAs($admin, 'admin')->from('/admin/transaction/' . $trx . '/detail')
             ->delete('/admin/transaction/' . $trx . '/delete')->assertSessionHasErrors('stock_opname');
        $this->actingAs($admin, 'admin')->from('/admin/transaction/' . $trx . '/detail')
             ->put('/admin/transaction/' . $trx . '/reverse')->assertSessionHasErrors('stock_opname');
        $this->actingAs($admin, 'admin')->from('/admin/transaction/' . $trx . '/detail')
             ->get('/admin/transaction/' . $trx . '/edit')->assertSessionHasErrors('stock_opname');
        $this->actingAs($admin, 'admin')->from('/admin/internal-transaction/' . $trx . '/detail')
             ->delete('/admin/internal-transaction/' . $trx . '/delete')->assertSessionHasErrors('stock_opname');
        $this->actingAs($admin, 'admin')->from('/admin/good-loading/' . $loading . '/detail')
             ->delete('/admin/good-loading/' . $loading . '/delete')->assertSessionHasErrors('stock_opname');
        $this->actingAs($admin, 'admin')->from('/admin/good-loading/' . $loading . '/detail')
             ->get('/admin/good-loading/' . $loading . '/edit')->assertSessionHasErrors('stock_opname');

        $this->assertSame('stock_opname', DB::table('transactions')->where('id', $trx)->value('type'));
        $this->assertNull(DB::table('good_loadings')->where('id', $loading)->value('deleted_at'));
        $this->assertEquals(3, $this->stock($a));
        $this->assertEquals(6, $this->stock($b));

        // detail transaksi: tombol Ubah diganti keterangan
        $this->actingAs($admin, 'admin')->get('/admin/transaction/' . $trx . '/detail')
             ->assertOk()->assertSee('Hasil stock opname')->assertDontSee('id="act-edit"', false);
    }

    public function test_detail_stock_opname_dan_daftar_jurnal(): void
    {
        $admin = $this->admin('superadmin');
        [$a, , $ua] = $this->good('Gula Sedikit', 10, 1000);
        [$b, , $ub] = $this->good('Gula Banyak', 10, 1000);
        [$c, , $uc] = $this->good('Gula Lebih', 10, 1000);
        $id = $this->opname($admin, [[$a, $ua, 9], [$c, $uc, 12], [$b, $ub, 4]]);

        // rugi terbesar di atas, lalu yang lebih
        $this->actingAs($admin, 'admin')->get('/admin/stock-opname/' . $id . '/detail')
             ->assertOk()
             ->assertSeeInOrder(['Gula Banyak', 'Gula Sedikit', 'Gula Lebih'])
             ->assertSee('Barang kurang (rugi)')
             ->assertSee('Rp7.000')
             ->assertSee('Rugi stock opname #' . $id);

        $today = date('Y-m-d');
        $this->actingAs($admin, 'admin')->get("/admin/journal/all/all/$today/$today/id/asc/15")
             ->assertOk()->assertSee('admin/stock-opname/' . $id . '/detail', false);
    }

    public function test_laporan_barang_sering_hilang(): void
    {
        $admin = $this->admin('superadmin');
        [$sering, , $us] = $this->good('Rokok Sering', 20, 1000);
        [$sekali, , $uk] = $this->good('Permen Sekali', 10, 5000);
        [$lebih, , $ul]  = $this->good('Korek Lebih', 5, 100);

        $this->opname($admin, [[$sering, $us, 18], [$sekali, $uk, 8], [$lebih, $ul, 6]]);
        $this->opname($admin, [[$sering, $us, 15], [$sekali, $uk, 8], [$lebih, $ul, 6]]);

        $today = date('Y-m-d');
        $data  = BarangSeringHilang::data($today, $today);
        $rows  = $data['rows']->keyBy('id');

        $this->assertSame([$sekali, $sering], $data['rows']->pluck('id')->map(fn ($v) => (int) $v)->all(), 'urut rugi terbesar');
        $this->assertSame(2, $rows[$sering]->kali_kurang);
        $this->assertSame(2, $rows[$sering]->kali_dicek);
        $this->assertEquals(5, $rows[$sering]->qty_kurang);
        $this->assertEquals(5000, $rows[$sering]->rp_kurang);
        $this->assertSame(1, $rows[$sekali]->kali_kurang);
        $this->assertSame(50, $rows[$sekali]->persen);
        $this->assertFalse($rows->has($lebih), 'barang yang tidak pernah kurang tidak tampil');
        $this->assertSame(2, $data['ringkasan']['jumlah_so']);
        $this->assertEquals(15000, $data['ringkasan']['rp_kurang']);
        $this->assertEquals(100, $data['ringkasan']['rp_lebih']);
        $this->assertSame(1, $data['ringkasan']['berulang']);

        $this->assertSame([$sering, $sekali], BarangSeringHilang::data($today, $today, urut: 'kali')['rows']->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([$sering], BarangSeringHilang::data($today, $today, minKurang: 2)['rows']->pluck('id')->map(fn ($v) => (int) $v)->all());

        $this->actingAs($admin, 'admin')->get('/admin/reports/lost-goods?urut=kali')
             ->assertOk()->assertSeeInOrder(['Rokok Sering', 'Permen Sekali'])->assertSee('2 dari 2 SO');

        $csv = $this->actingAs($admin, 'admin')->get('/admin/reports/lost-goods?export=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Rokok Sering', $csv);
        $this->assertStringContainsString('Kali kurang', $csv);

        // bawaannya hanya owner (superadmin)
        $this->actingAs($this->admin('admin'), 'admin')->get('/admin/reports/lost-goods')->assertForbidden();
    }
}
