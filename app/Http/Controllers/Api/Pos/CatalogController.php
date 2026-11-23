<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Kasir HP: data yang disimpan di HP (barang + satuan + harga jual, member).
 *
 * Tanpa ?since= : semua data aktif (full).
 * Dengan ?since=<server_time sebelumnya> : hanya yang berubah sejak itu,
 * termasuk yang dihapus (is_active = false) supaya HP bisa menyembunyikannya.
 * Harga beli tidak pernah dikirim.
 */
class CatalogController extends Controller
{
    public function catalog(Request $request)
    {
        [$since, $serverTime] = $this->since($request);

        $goods = DB::table('goods')->select('id', 'code', 'name', 'last_stock', 'is_discontinued', 'deleted_at');

        if ($since) {
            $changedUnits = DB::table('good_units')
                ->where(fn ($q) => $q->where('updated_at', '>', $since)->orWhere('deleted_at', '>', $since))
                ->pluck('good_id')->unique()->values()->all();

            // *IntegerInRaw: MariaDB 10.4 mengembalikan 0 baris untuk IN (?, ?, ...) berisi >= 1000
            // parameter (in_predicate_conversion_threshold). ID ditulis langsung sebagai angka.
            $goods->where(fn ($q) => $q->where('updated_at', '>', $since)
                                       ->orWhere('deleted_at', '>', $since)
                                       ->orWhereIntegerInRaw('id', $changedUnits));
        } else {
            $goods->whereNull('deleted_at');
        }

        $goods = $goods->orderBy('id')->get();

        $units = DB::table('good_units')
            ->join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereIntegerInRaw('good_units.good_id', $goods->pluck('id')->all())
            ->whereNull('good_units.deleted_at')
            ->orderBy('units.quantity')
            ->get(['good_units.id', 'good_units.good_id', 'good_units.selling_price', 'units.code as unit_code', 'units.name as unit_name', 'units.quantity'])
            ->groupBy('good_id');

        return response()->json([
            'server_time' => $serverTime,
            'full'        => $since === null,
            'goods'       => $goods->map(fn ($g) => [
                'id'              => (int) $g->id,
                'code'            => $g->code,
                'name'            => $g->name,
                'is_active'       => $g->deleted_at === null,
                'is_discontinued' => (bool) $g->is_discontinued,
                'stock_estimate'  => (float) $g->last_stock,
                'units'           => $g->deleted_at !== null ? [] : ($units[$g->id] ?? collect())->map(fn ($u) => [
                    'good_unit_id'  => (int) $u->id,
                    'unit'          => $u->unit_code ?: $u->unit_name,
                    'unit_name'     => $u->unit_name,
                    'quantity'      => (float) $u->quantity,
                    'selling_price' => (float) $u->selling_price,
                ])->values(),
            ])->values(),
        ]);
    }

    public function members(Request $request)
    {
        [$since, $serverTime] = $this->since($request);

        $members = DB::table('members')->select('id', 'name', 'address', 'phone_number', 'store_name', 'deleted_at');

        if ($since) {
            $members->where(fn ($q) => $q->where('updated_at', '>', $since)->orWhere('deleted_at', '>', $since));
        } else {
            $members->whereNull('deleted_at');
        }

        return response()->json([
            'server_time' => $serverTime,
            'full'        => $since === null,
            'members'     => $members->orderBy('name')->get()->map(fn ($m) => [
                'id'           => (int) $m->id,
                'name'         => $m->name,
                'address'      => $m->address,
                'phone_number' => $m->phone_number,
                'store_name'   => $m->store_name,
                'is_active'    => $m->deleted_at === null,
            ])->values(),
        ]);
    }

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
