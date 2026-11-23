<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * HP kasir yang boleh memakai API /api/pos/v1 (lihat docs/rancangan-kasir-hp-tahap1.md).
 * Token asli hanya disimpan di HP; di sini hanya hash SHA-256-nya.
 */
class PosDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'is_active', 'created_by',
    ];

    protected $hidden = [
        'token_hash', 'pair_code',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'pair_expires_at' => 'datetime',
        'last_seen_at'    => 'datetime',
        'last_sync_at'    => 'datetime',
    ];

    public const PAIR_MINUTES = 15;

    public static function tabelAda(): bool
    {
        return Schema::hasTable('pos_devices') && Schema::hasTable('pos_sync_rejects')
            && Schema::hasColumn('transactions', 'client_uuid');
    }

    /**
     * Buat tabel kalau belum ada. Hanya dari request GET: CREATE/ALTER TABLE
     * di MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikanTabel(): bool
    {
        if (self::tabelAda()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_23_000001_create_kasir_hp_tables.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }

        return self::tabelAda();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Kode nota berikutnya: HP1, HP2, ... untuk HP Android, PC1, PC2, ... untuk aplikasi Kasir PC
     * (termasuk yang sudah dihapus, supaya tidak dipakai ulang).
     */
    public static function nextCode(string $prefix = 'HP'): string
    {
        $prefix = $prefix === 'PC' ? 'PC' : 'HP';
        $n = 1;
        foreach (self::withTrashed()->pluck('code') as $code) {
            if (preg_match('/^' . $prefix . '(\d+)$/', $code, $m)) {
                $n = max($n, (int) $m[1] + 1);
            }
        }

        return $prefix . $n;
    }

    /** Kode pasang baru (6 angka), berlaku PAIR_MINUTES menit. Token lama tetap berlaku sampai HP memasang ulang. */
    public function newPairCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (self::where('pair_code', $code)->where('id', '!=', $this->id)->exists());

        $this->pair_code       = $code;
        $this->pair_expires_at = now()->addMinutes(self::PAIR_MINUTES);
        $this->save();

        return $code;
    }

    /** Tukar kode pasang dengan token baru. Mengembalikan token asli (hanya sekali). */
    public function issueToken(): string
    {
        $token = Str::random(48);

        $this->token_hash      = self::hashToken($token);
        $this->pair_code       = null;
        $this->pair_expires_at = null;
        $this->last_seen_at    = now();
        $this->save();

        return $token;
    }

    public function rejects()
    {
        return $this->hasMany(PosSyncReject::class);
    }
}
