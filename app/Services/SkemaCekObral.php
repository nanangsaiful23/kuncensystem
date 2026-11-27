<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

/**
 * Tabel & kolom untuk tugas cek stok (Sidak berisi daftar) dan harga obral,
 * dari migrasi 2026_11_28_000001. Server produksi tidak punya terminal, jadi
 * dibuat otomatis saat halaman Perputaran Barang / Sidak dibuka.
 */
class SkemaCekObral
{
    private static ?bool $sidak = null;
    private static ?bool $harga = null;

    /** Daftar barang per sesi sidak & kolom "dihitung oleh" sudah ada. */
    public static function sidakAda(): bool
    {
        return self::$sidak ??= Schema::hasTable('stock_inspection_targets')
            && Schema::hasTable('stock_inspections') && Schema::hasColumn('stock_inspections', 'counted_by');
    }

    /** Kolom harga normal sebelum obral (good_units.clearance_normal_price) sudah ada. */
    public static function hargaObralAda(): bool
    {
        return self::$harga ??= Schema::hasColumn('good_units', 'clearance_normal_price');
    }

    public static function lupakan(): void
    {
        self::$sidak = self::$harga = null;
    }

    /**
     * Buat kolom & tabel kalau belum ada. Hanya dari request GET: CREATE/ALTER
     * TABLE di MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikan(): void
    {
        if (self::sidakAda() && self::hargaObralAda()) {
            return;
        }
        try {
            (require database_path('migrations/2026_11_28_000001_add_sidak_targets_and_clearance_price.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        self::lupakan();
    }
}
