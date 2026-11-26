<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

/**
 * Tutup Kasir harian (lihat App\Services\TutupKasir).
 *
 * difference = physical_cash - system_balance: minus berarti uang kurang,
 * plus berarti uang lebih. Uang yang tersisa di laci = physical_cash - draw_amount.
 */
class CashClosing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'closing_date', 'system_balance', 'physical_cash', 'drawers', 'difference', 'draw_amount',
        'adjustment_journal_id', 'draw_journal_id', 'note', 'role', 'role_id',
    ];

    protected $casts = [
        'closing_date'   => 'date',
        'system_balance' => 'float',
        'physical_cash'  => 'float',
        // [['nama' => 'Kasir A', 'uang' => 450000.0, 'penjualan_cash' => 1200000.0|null], ...]
        'drawers'        => 'array',
        'difference'     => 'float',
        'draw_amount'    => 'float',
        'adjustment_journal_id' => 'integer',
        'draw_journal_id'       => 'integer',
        'role_id'               => 'integer',
    ];

    public static function tabelAda(): bool
    {
        return Schema::hasTable('cash_closings') && Schema::hasColumn('cash_closings', 'drawers')
            && Journal::punyaKolomPembuat();
    }

    /**
     * Buat tabel kalau belum ada. Hanya dari request GET: CREATE TABLE di
     * MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikanTabel(): bool
    {
        if (self::tabelAda()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_24_000001_create_cash_closings_table.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        Journal::lupakanKolomPembuat();

        return self::tabelAda();
    }

    public function remaining(): float
    {
        return round($this->physical_cash - $this->draw_amount, 2);
    }

    /** Nama admin/kasir yang menutup. */
    public function closerName(): string
    {
        $user = $this->role === 'cashier' ? \App\Cashier::find($this->role_id) : \App\Admin::find($this->role_id);

        return ($user->name ?? '#' . $this->role_id) . ($this->role === 'cashier' ? ' (kasir)' : '');
    }
}
