<?php

namespace Tests\Feature\Stock;

use Illuminate\Support\Facades\DB;

/** Daftar Jurnal (JournalControllerBase::indexJournalBase): jurnal yang dihapus tidak tampil. */
class JournalListTest extends StockTestCase
{
    public function test_jurnal_yang_dihapus_tidak_tampil_di_daftar(): void
    {
        $today = date('Y-m-d');
        $jurnal = fn (string $name, string $type, $deletedAt = null) => DB::table('journals')->insert([
            'type' => $type, 'journal_date' => $today, 'name' => $name,
            'debit_account_id' => $this->acc['1112'], 'debit' => 1000,
            'credit_account_id' => $this->acc['1111'], 'credit' => 1000,
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => $deletedAt,
        ]);
        $jurnal('Pengambilan uang masih berlaku', 'cash_draw');
        $jurnal('Pengambilan uang dibatalkan', 'cash_draw', now()); // tutup kasir yang dibatalkan

        $admin = $this->admin('superadmin');
        foreach (['all/all', '1111/all', 'all/cash_draw'] as $filter) {
            foreach ([15, 'all'] as $pagination) {
                $this->actingAs($admin, 'admin')
                     ->get("/admin/journal/$filter/$today/$today/created_at/desc/$pagination")
                     ->assertOk()
                     ->assertSee('Pengambilan uang masih berlaku')
                     ->assertDontSee('Pengambilan uang dibatalkan');
            }
        }
    }
}
