<?php

namespace Tests\Feature\Stock;

use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use Illuminate\Support\Facades\DB;

/** Prices typed as "12.500" or "12,500" must both be saved as 12500. */
class GoodLoadingNumberTest extends StockTestCase
{
    private function unitId(int $goodUnitId): int
    {
        return (int) DB::table('good_units')->where('id', $goodUnitId)->value('unit_id');
    }

    /** Fields as the loading form posts them. $rows: [good_id, good_unit_id, qty, buy, sell] */
    private function loadingForm(array $rows, string $total): array
    {
        $f = ['distributor_name' => null, 'distributor_id' => 1, 'loading_date' => date('Y-m-d'), 'payment' => '1111',
              'checker' => 'test', 'note' => 'uji angka', 'total_item_price' => $total];
        foreach ($rows as [$good, $unit, $qty, $buy, $sell]) {
            $f['names'][] = $good;
            $f['name_temps'][] = DB::table('goods')->where('id', $good)->value('name');
            $f['barcodes'][] = DB::table('goods')->where('id', $good)->value('code');
            $f['units'][] = $this->unitId($unit);
            $f['quantities'][] = $qty;
            $f['prices'][] = $buy;
            $f['sell_prices'][] = $sell;
            $f['old_stocks'][] = 0;
            $f['exp_dates'][] = null;
        }

        return $f;
    }

    public function test_store_reads_indonesian_and_english_separators(): void
    {
        [$sabun, $sabunPcs] = $this->makeGood('Sabun', 10000, 12000);
        [$teh, $tehPcs] = $this->makeGood('Teh', 1000, 1500);
        $this->load($sabunPcs, 1, 10000); // the store compares with the last loading

        $this->actingAs($this->admin('supervisor'), 'admin')
             ->post('/admin/good-loading/normal/store', $this->loadingForm([
                 [$sabun, $sabunPcs, '2', '12.500', '15.000'],      // Indonesian
                 [$teh, $tehPcs, '1,5', '1,250', '1.750,5'],        // English thousands, Indonesian decimals
             ], '27.375'))
             ->assertRedirect()->assertSessionHasNoErrors();

        $loading = GoodLoading::latest('id')->first();
        $details = GoodLoadingDetail::where('good_loading_id', $loading->id)->orderBy('id')->get();
        $this->assertEquals([12500, 1250], $details->pluck('price')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals([15000, 1750.5], $details->pluck('selling_price')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals([2, 1.5], $details->pluck('quantity')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals(27500, (float) $loading->total_item_price); // 27.375 rounded up to 500 (cash)
        $this->assertEquals(12500, (float) DB::table('good_units')->where('id', $sabunPcs)->value('buy_price'));
        $this->assertEquals(1750.5, (float) DB::table('good_units')->where('id', $tehPcs)->value('selling_price'));
        $this->assertSame(3.0, $this->stock($sabun));
        $this->assertSame(1.5, $this->stock($teh));
    }

    public function test_edit_reads_formatted_numbers(): void
    {
        [$gula, $gulaPcs] = $this->makeGood('Gula', 10000, 12000);
        $this->load($gulaPcs, 1, 10000);
        $admin = $this->admin('supervisor');
        $this->actingAs($admin, 'admin')
             ->post('/admin/good-loading/normal/store', $this->loadingForm([[$gula, $gulaPcs, '2', '10.000', '12.000']], '20.000'))
             ->assertRedirect();
        $loading = GoodLoading::latest('id')->first();
        $detail = GoodLoadingDetail::where('good_loading_id', $loading->id)->first();

        $this->actingAs($admin, 'admin')
             ->put('/admin/good-loading/' . $loading->id . '/edit', [
                 'distributor_id' => 1, 'loading_date' => date('Y-m-d'), 'payment' => '1111', 'checker' => 'test', 'note' => 'uji angka',
                 'change' => '1', 'ids' => [$detail->id], 'name_temps' => ['Gula'], 'barcodes' => ['T-Gula'],
                 'quantities' => ['2,5'], 'prices' => ['11.000'], 'sell_prices' => ['13,500'], 'total_item_price' => '27.500',
             ])
             ->assertRedirect()->assertSessionHasNoErrors();

        $detail->refresh();
        $this->assertEquals(2.5, (float) $detail->quantity);
        $this->assertEquals(11000, (float) $detail->price);
        $this->assertEquals(13500, (float) $detail->selling_price);
        $this->assertEquals(11000, (float) DB::table('good_units')->where('id', $gulaPcs)->value('buy_price'));
        $this->assertSame(3.5, $this->stock($gula));
    }

    public function test_new_good_from_the_loading_page_accepts_indonesian_prices(): void
    {
        DB::table('units')->insert(['code' => 'PCS', 'name' => 'pcs', 'quantity' => '1', 'base' => 'pcs']);
        DB::table('brands')->insert(['id' => 1, 'name' => 'Tanpa merek']);

        $this->actingAs($this->admin('supervisor'), 'admin')
             ->post('/admin/good/store', [
                 'role' => 'admin', 'category_id' => 1, 'type_id' => null, 'code' => 'NEW-1', 'name' => 'Barang Baru',
                 'brand_id' => 1, 'unit_id' => DB::table('units')->value('id'), 'price' => '12.500', 'selling_price' => '15.000',
             ], ['Accept' => 'application/json'])
             ->assertSuccessful();

        $unit = DB::table('good_units')->join('goods', 'goods.id', '=', 'good_units.good_id')->where('goods.code', 'NEW-1')->first();
        $this->assertEquals(12500, (float) $unit->buy_price);
        $this->assertEquals(15000, (float) $unit->selling_price);
    }
}
