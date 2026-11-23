<?php

namespace App\Http\Controllers\Api\Pos;

use Carbon\Carbon;
use Illuminate\Http\Request;

/** Parameter ?since= untuk tarikan "yang berubah saja" dari Kasir HP/PC. */
trait ReadsSince
{
    /**
     * [since (Y-m-d H:i:s, zona aplikasi) | null, server_time untuk tarikan berikutnya].
     * server_time diambil SEBELUM query dan dimundurkan 5 detik supaya perubahan
     * yang terjadi bersamaan dengan tarikan ini tidak terlewat.
     */
    private function since(Request $request): array
    {
        $serverTime = now()->subSeconds(5)->toIso8601String();

        $since = null;
        if ($request->filled('since')) {
            try {
                $since = Carbon::parse($request->since)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $since = null; // tidak terbaca: kirim penuh
            }
        }

        return [$since, $serverTime];
    }
}
