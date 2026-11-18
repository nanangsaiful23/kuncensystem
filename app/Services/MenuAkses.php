<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Hak akses menu per role (tabel `menu_permissions`, daftar menu di
 * config/menu_akses.php).
 *
 * Baris di tabel hanya menyimpan yang DIUBAH superadmin; menu yang tidak ada
 * barisnya memakai nilai 'bawaan' dari config. Kalau tabel belum dibuat,
 * semuanya memakai nilai bawaan (aturan lama).
 */
class MenuAkses
{
    public const CACHE_KEY = 'menu_permissions.v1';

    /** Isi tabel per request: [role][menu_key] => bool. */
    private static ?array $tersimpan = null;

    public static function menus(): array
    {
        return self::config()['menus'] ?? [];
    }

    public static function roles(): array
    {
        return self::config()['roles'] ?? [];
    }

    /**
     * Isi config/menu_akses.php. Dibaca langsung dari file kalau config Laravel
     * sedang di-cache (bootstrap/cache/config.php lama yang belum kenal file ini).
     */
    private static function config(): array
    {
        $config = config('menu_akses');
        if (! is_array($config)) {
            $config = require config_path('menu_akses.php');
            config(['menu_akses' => $config]);
        }

        return $config;
    }

    public const SESSION_LIHAT_SEBAGAI = 'menu_akses_lihat_sebagai';

    /** Role admin asli yang sedang login: 'superadmin', 'supervisor' atau 'admin'. */
    public static function roleAsli(): ?string
    {
        $user = Auth::guard('admin')->user();
        if ($user === null) {
            return null;
        }
        $role = $user->role ?? null;

        return in_array($role, ['superadmin', 'supervisor'], true) ? $role : 'admin';
    }

    /**
     * Role yang dipakai untuk cek menu. Superadmin bisa "melihat sebagai"
     * supervisor/admin untuk mencoba hasil pengaturan.
     */
    public static function roleSekarang(): ?string
    {
        $role = self::roleAsli();

        return $role === 'superadmin' ? (self::lihatSebagai() ?? $role) : $role;
    }

    /** Role yang sedang dicoba superadmin, atau null. */
    public static function lihatSebagai(): ?string
    {
        if (self::roleAsli() !== 'superadmin') {
            return null;
        }
        $role = session(self::SESSION_LIHAT_SEBAGAI);

        return array_key_exists((string) $role, self::roles()) ? $role : null;
    }

    /** Buat tabel kalau belum ada (server produksi tidak bisa menjalankan artisan). */
    public static function pastikanTabel(): bool
    {
        if (self::tabelAda()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_18_000002_create_menu_permissions_table.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }

        return self::tabelAda();
    }

    /** Boleh membuka menu ini? Dipakai di sidebar lewat helper canMenu(). */
    public static function boleh(string $key, ?string $role = null): bool
    {
        $menu = self::menus()[$key] ?? null;
        if ($menu === null) {
            return false;
        }

        // Sidebar kasir: tidak diatur di halaman, ikut config.
        if ($role === null && request()->is('cashier', 'cashier/*')) {
            return (bool) ($menu['kasir'] ?? false);
        }

        $role = $role ?? self::roleSekarang();
        if ($role === null) {
            return false;
        }
        if ($role === 'superadmin') {
            return true;
        }

        $tersimpan = self::tersimpan();
        if (isset($tersimpan[$role][$key])) {
            return $tersimpan[$role][$key];
        }

        return (bool) ($menu['bawaan'][$role] ?? false);
    }

    /** Ada sub menu dari induk ini yang boleh? (untuk menampilkan menu induk di sidebar) */
    public static function bolehInduk(string $induk): bool
    {
        foreach (self::menus() as $key => $menu) {
            if (($menu['induk'] ?? null) === $induk && self::boleh($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menu yang menjaga URL ini (path tanpa awalan "admin/").
     * Kosong = URL tidak dijaga.
     */
    public static function menuUntukPath(string $path): array
    {
        $path = trim($path, '/');

        foreach (self::config()['selalu_boleh'] ?? [] as $pola) {
            if (Str::is($pola, $path)) {
                return [];
            }
        }

        $keys = [];
        foreach (self::menus() as $key => $menu) {
            if (Str::is($menu['paths'] ?? [], $path) && ! Str::is($menu['kecuali'] ?? [], $path)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** Simpan centang dari halaman pengaturan: [role][menu_key] => bool. */
    public static function simpan(array $centang): void
    {
        $now  = now();
        $rows = [];
        foreach (array_keys(self::roles()) as $role) {
            foreach (array_keys(self::menus()) as $key) {
                $rows[] = [
                    'role'       => $role,
                    'menu_key'   => $key,
                    'allowed'    => ! empty($centang[$role][$key]) ? 1 : 0,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('menu_permissions')->upsert($rows, ['role', 'menu_key'], ['allowed', 'updated_at']);
        // Buang baris menu lama yang sudah tidak ada di config (mis. menu yang dipecah jadi sub menu).
        DB::table('menu_permissions')->whereNotIn('menu_key', array_keys(self::menus()))->delete();
        self::lupakan();
    }

    /** Hapus semua pengaturan: kembali ke nilai bawaan. */
    public static function kembalikanBawaan(): void
    {
        DB::table('menu_permissions')->delete();
        self::lupakan();
    }

    public static function tabelAda(): bool
    {
        try {
            DB::table('menu_permissions')->limit(1)->get();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function lupakan(): void
    {
        self::$tersimpan = null;
        Cache::forget(self::CACHE_KEY);
    }

    private static function tersimpan(): array
    {
        if (self::$tersimpan !== null) {
            return self::$tersimpan;
        }

        try {
            // 10 menit, supaya ubahan langsung lewat phpMyAdmin tetap terbaca.
            // Simpan dari halaman pengaturan menghapus cache seketika.
            $data = Cache::remember(self::CACHE_KEY, 600, function () {
                $hasil = [];
                foreach (DB::table('menu_permissions')->get(['role', 'menu_key', 'allowed']) as $row) {
                    $hasil[$row->role][$row->menu_key] = (bool) $row->allowed;
                }

                return $hasil;
            });
        } catch (\Throwable $e) {
            // Tabel belum dibuat (SQL belum dijalankan): pakai nilai bawaan.
            $data = [];
        }

        return self::$tersimpan = $data;
    }
}
