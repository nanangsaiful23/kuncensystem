<?php

namespace Tests\Feature\Stock;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class CashierStockJournalTest extends StockTestCase
{
    public function test_sale_lowers_stock_and_books_hpp_at_server_buy_price(): void
    {
        [$good, $pcs] = $this->makeGood('Sabun', 1000, 1500);
        $this->load($pcs, 10, 1000);
        $this->assertSame(10.0, $this->stock($good));

        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$pcs, 3, 1500]]))
             ->assertRedirect();

        $this->assertSame(7.0, $this->stock($good));
        // buy_prices posted by the page (1) are ignored: HPP = 3 x 1000
        $this->assertSame(-3000.0, $this->balance('1141'));
        $this->assertSame(3000.0, $this->balance('5101'));
        $this->assertSame(4500.0, $this->balance('1111'));
    }

    public function test_same_form_posted_twice_is_saved_once(): void
    {
        [$good, $pcs] = $this->makeGood('Teh', 1000, 1500);
        $this->load($pcs, 10, 1000);
        $cashier = $this->cashier();
        $form = $this->saleForm([[$pcs, 2, 1500]], [], 'cash', 'token-abc');

        $this->actingAs($cashier, 'cashier')->post('/cashier/transaction/store', $form)->assertRedirect();
        $first = Transaction::max('id');
        $this->actingAs($cashier, 'cashier')->post('/cashier/transaction/store', $form)
             ->assertRedirect('/cashier/transaction/' . $first . '/print');

        $this->assertSame(1, Transaction::count());
        $this->assertSame(8.0, $this->stock($good));
    }

    public function test_bad_line_saves_nothing(): void
    {
        [$good, $pcs] = $this->makeGood('Kopi', 1000, 1500);
        $this->load($pcs, 10, 1000);

        // second line points to a good unit that does not exist
        $this->actingAs($this->cashier(), 'cashier')
             ->from('/cashier/transaction/create')
             ->post('/cashier/transaction/store', $this->saleForm([[$pcs, 1, 1500], [999999, 1, 1500]]))
             ->assertSessionHasErrors('transaction');

        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, DB::table('journals')->count());
        $this->assertSame(10.0, $this->stock($good));
    }

    public function test_error_halfway_rolls_back_everything(): void
    {
        [$good, $pcs] = $this->makeGood('Gula', 1000, 1500);
        $this->load($pcs, 10, 1000);
        DB::table('accounts')->where('code', '5101')->delete(); // HPP account missing -> error after the details were written

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->cashier(), 'cashier')
                 ->post('/cashier/transaction/store', $this->saleForm([[$pcs, 1, 1500]]));
            $this->fail('expected an error');
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, DB::table('transaction_details')->count());
        $this->assertSame(0, DB::table('journals')->count());
        $this->assertSame(10.0, $this->stock($good));
    }

    public function test_hpp_of_the_day_is_not_added_to_a_reverse_journal(): void
    {
        [$good, $pcs] = $this->makeGood('Mie', 1000, 1500);
        $this->load($pcs, 20, 1000);
        $cashier = $this->cashier();

        // first sale of the day, then cancel it: the cancel writes Debit Persediaan 'hpp'
        $this->actingAs($cashier, 'cashier')->post('/cashier/transaction/store', $this->saleForm([[$pcs, 2, 1500]]));
        DB::table('journals')->where('name', 'like', 'Penjualan tanggal %')->where('type', 'hpp')->delete();
        $id = Transaction::max('id');
        $this->actingAs($this->admin(), 'admin')->put('/admin/transaction/' . $id . '/reverse')->assertRedirect();

        // next sale must go to a proper Debit HPP / Credit Persediaan journal
        $this->actingAs($cashier, 'cashier')->post('/cashier/transaction/store', $this->saleForm([[$pcs, 5, 1500]]));

        $this->assertSame(15.0, $this->stock($good));
        $hpp = DB::table('journals')->where('type', 'hpp')->where('name', 'like', 'Penjualan tanggal %')->first();
        $this->assertEquals($this->acc['5101'], $hpp->debit_account_id);
        $this->assertEquals(5000, (float) $hpp->debit);
    }

    public function test_retur_only_good_condition_puts_the_returned_good_back(): void
    {
        [$sold, $soldPcs] = $this->makeGood('Roti', 1000, 1500);
        [$back, $backPcs] = $this->makeGood('Susu', 4000, 5000);
        $this->load($soldPcs, 10, 1000);
        $this->load($backPcs, 10, 4000);

        // retur worth more than what is bought, and one without anything bought
        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$soldPcs, 1, 1500]], [[$backPcs, 2, 5000, 'not']]))
             ->assertRedirect();
        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([], [[$backPcs, 1, 5000, 'not']]))
             ->assertRedirect();

        $this->assertSame(9.0, $this->stock($sold));    // only the sold one; the retur no longer hits it
        $this->assertSame(13.0, $this->stock($back));   // 10 + 2 + 1 back on the shelf
        // inventory journal moves exactly like the stock value:
        // -1 Roti x 1000 + 3 Susu x 4000 (the loadings in this test have no journal)
        $this->assertSame(-1 * 1000 + 3 * 4000.0, round($this->balance('1141'), 2));
    }

    public function test_damaged_retur_goes_to_retur_box_not_to_stock(): void
    {
        [$back, $backPcs] = $this->makeGood('Telur', 2000, 2500);
        $this->load($backPcs, 5, 2000);

        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([], [[$backPcs, 2, 2500, 'rusak']]))
             ->assertRedirect();

        $this->assertSame(5.0, $this->stock($back));
        $this->assertSame(2, DB::table('retur_items')->whereNull('returned_date')->count());
    }

    public function test_reverse_of_a_transaction_with_retur_undoes_stock_and_inventory_journal(): void
    {
        [$sold, $soldPcs] = $this->makeGood('Kecap', 1000, 1500);
        [$back, $backPcs] = $this->makeGood('Saus', 3000, 4000);
        $this->load($soldPcs, 10, 1000);
        $this->load($backPcs, 10, 3000);

        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$soldPcs, 1, 1500]], [[$backPcs, 2, 4000, 'not']]));
        $this->assertSame(9.0, $this->stock($sold));
        $this->assertSame(12.0, $this->stock($back));

        $id = Transaction::max('id');
        $this->actingAs($this->admin(), 'admin')->put('/admin/transaction/' . $id . '/reverse')->assertRedirect();
        // second click does nothing
        $this->actingAs($this->admin(), 'admin')->put('/admin/transaction/' . $id . '/reverse')->assertRedirect();

        $this->assertSame(10.0, $this->stock($sold));
        $this->assertSame(10.0, $this->stock($back));
        $this->assertSame(0.0, round($this->balance('1141'), 2)); // only loadings in this test were not journaled
        $this->assertSame(0.0, round($this->balance('1111'), 2)); // money back to where it was
    }

    public function test_deleting_a_loading_takes_its_goods_out_of_stock(): void
    {
        [$good, $pcs] = $this->makeGood('Minyak', 10000, 12000);
        $this->load($pcs, 4, 10000);
        $loading = $this->load($pcs, 6, 10000);
        $this->assertSame(10.0, $this->stock($good));

        $this->actingAs($this->admin(), 'admin')->delete('/admin/good-loading/' . $loading . '/delete')->assertRedirect();

        $this->assertSame(4.0, $this->stock($good));
    }

    public function test_stock_counter_drift_is_repaired_by_stock_sync(): void
    {
        [$good, $pcs] = $this->makeGood('Beras', 10000, 12000);
        $this->load($pcs, 10, 10000);
        DB::table('goods')->where('id', $good)->update(['last_stock' => 99, 'total_loading' => 99]);

        $this->artisan('stock:sync', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(99.0, $this->stock($good));

        $this->artisan('stock:sync')->assertSuccessful();
        $this->assertSame(10.0, $this->stock($good));
        $this->assertSame(10.0, StockService::counters(\App\Models\Good::find($good))['last_stock']);
    }
}
