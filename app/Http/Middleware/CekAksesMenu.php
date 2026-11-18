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

        if ($keys !== []) {
            $boleh = false;
            foreach ($keys as $key) {
                if (MenuAkses::boleh($key)) {
                    $boleh = true;
                    break;
                }
            }

            abort_unless($boleh, 403, 'Menu ini ditutup untuk akun Anda. Minta superadmin membukanya di Hak Akses Menu.');
        }

        return $next($request);
    }
}
