<?php

namespace Tests\Feature\Stock;

use Illuminate\Support\Facades\DB;

/** Grafik Ledger Neraca (MainController::getScaleLedger): jurnal yang dihapus tidak ikut dihitung. */
class ScaleLedgerTest extends StockTestCase
{
    public function test_jurnal_yang_dihapus_tidak_ikut_dihitung(): void
    {
        $date = date('Y-m-d');
        $biaya = $this->acc['5220'];
        $jurnal = fn (float $n, $deletedAt = null) => DB::table('journals')->insert([
            'type' => 'other_payment', 'journal_date' => $date, 'name' => 'biaya',
            'debit_account_id' => $biaya, 'debit' => $n, 'credit_account_id' => $this->acc['1111'], 'credit' => $n,
            'deleted_at' => $deletedAt,
        ]);
        $jurnal(100000);
        $jurnal(50000, now()); // mis. jurnal dari tutup kasir yang dibatalkan

        $res = $this->actingAs($this->admin('superadmin'), 'admin')
            ->getJson('/admin/getScaleLedger/' . $date . '/' . $date . '/' . $biaya)
            ->assertOk()
            ->json('data.0.data');

        $this->assertCount(1, $res);
        $this->assertEquals(100000, $res[0]['debit']);
    }
}
