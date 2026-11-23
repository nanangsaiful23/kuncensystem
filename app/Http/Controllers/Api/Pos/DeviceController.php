<?php

namespace App\Http\Controllers\Api\Pos;

use App\Cashier;
use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** API Kasir HP: pasang perangkat, cek koneksi, login kasir. */
class DeviceController extends Controller
{
    /** Tukar kode pasang 6 angka (dari halaman admin Kasir HP) dengan token perangkat. */
    public function pair(Request $request)
    {
        $request->validate(['pair_code' => 'required|string|max:10']);

        $device = PosDevice::tabelAda()
            ? PosDevice::where('pair_code', trim($request->pair_code))
                       ->where('pair_expires_at', '>', now())
                       ->first()
            : null;

        if ($device === null) {
            return response()->json(['message' => 'Kode pasang salah atau sudah kedaluwarsa. Minta kode baru ke admin.'], 422);
        }

        if (! $device->is_active) {
            return response()->json(['message' => 'Perangkat ini dinonaktifkan admin.'], 403);
        }

        $token = $device->issueToken();

        return response()->json([
            'token'  => $token,
            'device' => $this->deviceData($device),
        ]);
    }

    public function ping(Request $request)
    {
        return response()->json([
            'server_time' => now()->toIso8601String(),
            'device'      => $this->deviceData($request->attributes->get('pos_device')),
        ]);
    }

    /**
     * Login akun kasir yang sudah ada (email + password, harus online).
     * PIN untuk membuka APK saat offline dibuat & disimpan di HP sendiri.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $cashier = Cashier::where('email', $request->email)->first();

        // Cara cek password harus sama dengan login web kasir (Cashier\Auth\LoginController::credentials).
        if ($cashier === null || ! Hash::check($request->password . Cashier::PASSWORD_SUFFIX, $cashier->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 422);
        }

        if (! $cashier->is_active) {
            return response()->json(['message' => 'Akun kasir ini tidak aktif.'], 403);
        }

        return response()->json([
            'cashier' => ['id' => $cashier->id, 'name' => $cashier->name, 'email' => $cashier->email],
        ]);
    }

    private function deviceData(PosDevice $device): array
    {
        return ['id' => $device->id, 'code' => $device->code, 'name' => $device->name];
    }
}
