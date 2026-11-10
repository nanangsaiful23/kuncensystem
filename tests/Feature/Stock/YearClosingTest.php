<?php

namespace Tests\Feature\Stock;

use App\Models\FinancialReportRepository;
use App\Models\Journal;
use App\Services\PeriodLock;
use App\Services\YearClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YearClosingTest extends StockTestCase
{
    private int $last;

    protected function setUp(): void
    {
        parent::setUp();
        $this->last = (int) date('Y') - 1;
    }

    private function journal(string $date, string $debit, string $credit, float $amount, string $type = 'test', ?int $typeId = null): void
    {
        DB::table('journals')->insert([
            'type' => $type, 'type_id' => $typeId, 'journal_date' => $date, 'name' => 'test',
            'debit_account_id' => $this->acc[$debit], 'debit' => $amount,
            'credit_account_id' => $this->acc[$credit], 'credit' => $amount,
        ]);
    }

    /** Capital in March, a sale and a cost in June of last year: profit 400.000. */
    private function lastYearBooks(): void
    {
        $this->journal("{$this->last}-03-10", '1111', '3001', 1_000_000);
        $this->journal("{$this->last}-06-05", '1111', '4101', 500_000);
        $this->journal("{$this->last}-06-20", '5220', '1111', 100_000);
    }

    private function row(array $mb, string $section, string $name): ?object
    {
        foreach ($mb['sections'][$section] as $row) {
            if ($row->kode === $name || str_starts_with($row->nama, $name)) {
                return $row;
            }
        }

        return null;
    }

    public function test_monthly_balance_sheet_shows_each_month_end_and_balances(): void
    {
        $this->lastYearBooks();
        $mb = (new FinancialReportRepository)->getMonthlyBalanceSheet($this->last);

        $kas = $this->row($mb, 'aktiva', '1111');
        $this->assertEquals(0, $kas->values[2]);
        $this->assertEquals(1_000_000, $kas->values[3]);
        $this->assertEquals(1_400_000, $kas->values[6]);
        $this->assertEquals(1_400_000, $kas->values[12]);
        $this->assertEquals(400_000, $this->row($mb, 'ekuitas', 'Laba tahun berjalan')->values[6]);
        foreach ($mb['difference'] as $diff) {
            $this->assertEquals(0, $diff);
        }

        // an opening balance on one side only shows up as the difference
        DB::table('accounts')->where('code', '1111')->update(['balance' => '50000', 'type' => 'Debet']);
        $mb = (new FinancialReportRepository)->getMonthlyBalanceSheet($this->last);
        $this->assertEquals(1_050_000, $this->row($mb, 'aktiva', '1111')->values[3]);
        $this->assertEquals(50_000, $mb['difference'][3]);
        $this->assertEquals(50_000, $mb['openingImbalance']);
    }

    public function test_closing_moves_the_profit_to_retained_earnings(): void
    {
        $this->lastYearBooks();

        $this->actingAs($this->admin(), 'admin')
             ->post('/admin/reports/financial/close-year', ['year' => $this->last])
             ->assertForbidden();

        $this->actingAs($this->admin('superadmin'), 'admin')
             ->post('/admin/reports/financial/close-year', ['year' => $this->last])
             ->assertRedirect();

        $this->assertTrue(YearClosing::isClosed($this->last));
        $this->assertSame([], YearClosing::profitLossBalances("{$this->last}-12-31"));
        $retained = DB::table('accounts')->where('code', '3003')->value('id');
        $this->assertEquals(400_000, Journal::where('credit_account_id', $retained)->sum('credit') - Journal::where('debit_account_id', $retained)->sum('debit'));

        // the closed year still shows its profit in December
        $mb = (new FinancialReportRepository)->getMonthlyBalanceSheet($this->last);
        $this->assertEquals(400_000, $this->row($mb, 'ekuitas', 'Laba tahun berjalan')->values[12]);
        $this->assertTrue($mb['closed']);

        // this year starts with the profit in Laba Ditahan and no profit yet
        $mb = (new FinancialReportRepository)->getMonthlyBalanceSheet($this->last + 1);
        $this->assertEquals(400_000, $this->row($mb, 'ekuitas', '3003')->values[1]);
        $this->assertNull($this->row($mb, 'ekuitas', 'Laba tahun-tahun lalu'));
        $this->assertEquals(0, $this->row($mb, 'ekuitas', 'Laba tahun berjalan')->values[1]);
        $this->assertEquals(0, $mb['difference'][1]);

        // closing again replaces the closing journals instead of adding to them
        YearClosing::close($this->last);
        $this->assertSame(2, Journal::where('type', 'closing')->count());
    }

    public function test_closed_year_is_locked_except_for_super_admin(): void
    {
        [$good, $pcs] = $this->makeGood('Gula', 1000, 1500);
        $loading = $this->load($pcs, 10, 1000);
        DB::table('good_loadings')->where('id', $loading)->update(['loading_date' => "{$this->last}-08-01"]);
        $this->journal("{$this->last}-08-01", '1141', '1111', 10_000, 'good_loading', $loading);
        $this->lastYearBooks();
        YearClosing::close($this->last);
        $this->assertSame("{$this->last}-12-31", PeriodLock::lockedUntil());

        // no journal in the closed year, also not from code without a login
        try {
            Journal::create(['type' => 'test', 'journal_date' => "{$this->last}-05-01", 'name' => 'x',
                'debit_account_id' => $this->acc['1111'], 'debit' => 1, 'credit_account_id' => $this->acc['4101'], 'credit' => 1]);
            $this->fail('expected the closed period to refuse the journal');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('periode', $e->errors());
        }

        // an admin cannot delete last year's loading: nothing changes
        $this->actingAs($this->admin(), 'admin')
             ->delete('/admin/good-loading/' . $loading . '/delete')
             ->assertSessionHasErrors('periode');
        $this->assertNull(DB::table('good_loadings')->where('id', $loading)->value('deleted_at'));
        $this->assertSame(10.0, $this->stock($good));

        // today's work goes on as usual
        $this->actingAs($this->cashier(), 'cashier')
             ->post('/cashier/transaction/store', $this->saleForm([[$pcs, 1, 1500]]))
             ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(9.0, $this->stock($good));

        // a super admin can still change it when it is urgent
        $this->actingAs($this->admin('superadmin'), 'admin')
             ->delete('/admin/good-loading/' . $loading . '/delete')
             ->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('good_loadings')->where('id', $loading)->value('deleted_at'));
        $this->assertSame(-1.0, $this->stock($good));

        // a late sale booked in the closed year changes its profit,
        // so the audit asks to close the year again
        Journal::create(['type' => 'test', 'journal_date' => "{$this->last}-12-30", 'name' => 'koreksi',
            'debit_account_id' => $this->acc['1111'], 'debit' => 25_000, 'credit_account_id' => $this->acc['4101'], 'credit' => 25_000]);
        $this->artisan('kuncen:audit', ['--from' => date('Y-m-d'), '--to' => date('Y-m-d')])
             ->expectsOutputToContain("Tahun {$this->last} berubah setelah tutup buku")
             ->assertSuccessful();
    }

    public function test_super_admin_can_open_a_closed_year_again(): void
    {
        $this->lastYearBooks();
        YearClosing::close($this->last);

        $this->actingAs($this->admin('superadmin'), 'admin')
             ->post('/admin/reports/financial/close-year', ['year' => $this->last, 'action' => 'reopen'])
             ->assertRedirect();

        $this->assertFalse(YearClosing::isClosed($this->last));
        $this->assertNull(PeriodLock::lockedUntil());
        $this->artisan('kuncen:close-year', ['year' => $this->last])->assertSuccessful();
        $this->assertTrue(YearClosing::isClosed($this->last));
    }
}
