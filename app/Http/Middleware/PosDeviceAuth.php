<?php

namespace App\Http\Middleware;

use App\Models\PosDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Kasir HP: header "Authorization: Bearer <token perangkat>".
 * Perangkat yang dinonaktifkan/dihapus admin langsung ditolak.
 */
class PosDeviceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $device = $token && PosDevice::tabelAda()
            ? PosDevice::where('token_hash', PosDevice::hashToken($token))->first()
            : null;

        if ($device === null) {
            return response()->json(['message' => 'Perangkat belum terdaftar. Pasang ulang HP dengan kode dari admin.', 'code' => 'device_unknown'], 401);
        }

        if (! $device->is_active) {
            return response()->json(['message' => 'Perangkat ini dinonaktifkan admin.', 'code' => 'device_inactive'], 403);
        }

        // Tidak perlu menyimpan setiap request; cukup sekali per menit.
        if ($device->last_seen_at === null || $device->last_seen_at->lt(now()->subMinute())) {
            $device->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        $request->attributes->set('pos_device', $device);

        return $next($request);
    }
}
