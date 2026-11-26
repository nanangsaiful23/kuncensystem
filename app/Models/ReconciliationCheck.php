<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil cek kecocokan harian (lihat App\Services\CekKecocokan), satu baris per
 * tutup kasir. Yang tidak cocok (problem_count > 0) tampil di dashboard admin
 * sampai resolved_at diisi.
 *
 * results: [['key' => 'kas', 'label' => 'Kas', 'ok' => false, 'pesan' => '...',
 *            'url' => 'admin/...', 'rincian' => [['teks' => '...', 'url' => ?string]], 'lainnya' => 0,
 *            'good_ids' => [..] (hanya cek stok, barang yang stoknya beda dengan riwayat)], ...]
 */
class ReconciliationCheck extends Model
{
    public const DIBACA    = 'dibaca';
    public const DITANGANI = 'ditangani';

    protected $fillable = [
        'check_date', 'cash_closing_id', 'problem_count', 'results',
        'resolved_at', 'resolution', 'resolved_by_role', 'resolved_by_id', 'resolve_note',
    ];

    protected $casts = [
        'check_date'      => 'date',
        'cash_closing_id' => 'integer',
        'problem_count'   => 'integer',
        'results'         => 'array',
        'resolved_at'     => 'datetime',
        'resolved_by_id'  => 'integer',
    ];

    private static ?bool $tabelAda = null;

    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('reconciliation_checks');
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
            (require database_path('migrations/2026_11_26_000001_create_reconciliation_checks_table.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        self::$tabelAda = null;

        return self::tabelAda();
    }

    /** Hasil yang tidak cocok dan belum ditandai. */
    public function scopeBelumDitandai($query)
    {
        return $query->where('problem_count', '>', 0)->whereNull('resolved_at');
    }

    /** @return list<array> cek yang tidak cocok */
    public function masalah(): array
    {
        return array_values(array_filter($this->results ?? [], fn ($r) => empty($r['ok'])));
    }

    /** Nama admin/kasir yang menandai. */
    public function resolverName(): ?string
    {
        if ($this->resolved_by_id === null) {
            return null;
        }
        $user = $this->resolved_by_role === 'cashier' ? \App\Cashier::find($this->resolved_by_id) : \App\Admin::find($this->resolved_by_id);

        return $user->name ?? '#' . $this->resolved_by_id;
    }
}
