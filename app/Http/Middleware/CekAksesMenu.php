<?php

namespace App\Http\Middleware;

use App\Services\MenuAkses;
use Closure;

/**
 * Menolak (403) URL admin milik menu yang ditutup untuk role ini, supaya menu
 * yang disembunyikan tidak bisa dibuka dengan mengetik URL-nya.
 * Daftar URL per menu: config/menu_akses.php ('paths').
 */
class CekAksesMenu
{
    public function handle($request, Closure $next)
    {
        $path = preg_replace('#^admin/?#', '', $request->path());
        $keys = MenuAkses::menuUntukPath($path);

        // Semua menu yang cocok harus boleh: mis. good-loading/5/edit butuh
        // menu Loading Barang DAN hak Edit & hapus.
        foreach ($keys as $key) {
            abort_unless(MenuAkses::boleh($key), 403, 'Menu ini ditutup untuk akun Anda. Minta superadmin membukanya di Hak Akses Menu.');
        }

        return $next($request);
    }
}
