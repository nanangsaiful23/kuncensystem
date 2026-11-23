<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\PosSyncReject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Pengaturan > Kasir HP: daftar HP kasir, kode pasang, dan transaksi HP yang ditolak. */
class KasirHpController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(isSuperadmin(), 403, 'Kasir HP hanya untuk superadmin.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Dibuat di GET (bukan di POST): CREATE/ALTER TABLE di MySQL menutup
        // transaksi yang dibuka AtomicRequest untuk POST.
        if (! PosDevice::pastikanTabel()) {
            return view('admin.kasir-hp', ['tabelAda' => false]);
        }

        $devices = PosDevice::orderBy('code')->get();

        $today = DB::table('transactions')
            ->whereNotNull('pos_device_id')
            ->whereNull('deleted_at')
            ->whereDate('created_at', date('Y-m-d'))
            ->groupBy('pos_device_id')
            ->select('pos_device_id', DB::raw('COUNT(*) AS jumlah'), DB::raw('SUM(total_sum_price) AS total'))
            ->get()->keyBy('pos_device_id');

        $tampilSelesai = $request->boolean('selesai');
        $rejects = PosSyncReject::with('device')
            ->when(! $tampilSelesai, fn ($q) => $q->whereNull('resolved_at'))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('admin.kasir-hp', [
            'tabelAda'      => true,
            'devices'       => $devices,
            'today'         => $today,
            'rejects'       => $rejects,
            'tampilSelesai' => $tampilSelesai,
            'openRejects'   => PosSyncReject::whereNull('resolved_at')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'jenis' => 'nullable|in:hp,pc',
        ]);

        $device = PosDevice::create([
            'name'       => $request->name,
            'code'       => PosDevice::nextCode($request->jenis === 'pc' ? 'PC' : 'HP'),
            'is_active'  => true,
            'created_by' => \Auth::user()->id,
        ]);
        $code = $device->newPairCode();

        return redirect()->route('admin.kasir-hp.index')
            ->with('kasir_hp_pair', ['device' => $device->name . ' (' . $device->code . ')', 'code' => $code]);
    }

    public function pairCode(PosDevice $device)
    {
        abort_unless($device->is_active, 422, 'Aktifkan perangkat dulu.');

        $code = $device->newPairCode();

        return redirect()->route('admin.kasir-hp.index')
            ->with('kasir_hp_pair', ['device' => $device->name . ' (' . $device->code . ')', 'code' => $code]);
    }

    public function toggle(PosDevice $device)
    {
        $device->is_active = ! $device->is_active;
        if (! $device->is_active) {
            // Kode pasang yang belum dipakai ikut batal.
            $device->pair_code       = null;
            $device->pair_expires_at = null;
        }
        $device->save();

        return redirect()->route('admin.kasir-hp.index')->with('kasir_hp_ok', $device->name . ($device->is_active ? ' diaktifkan lagi.' : ' dinonaktifkan. HP ini tidak bisa mengirim transaksi lagi.'));
    }

    public function resolve(PosSyncReject $reject)
    {
        $reject->resolved_at = $reject->resolved_at ? null : now();
        $reject->save();

        return redirect()->back()->with('kasir_hp_ok', 'Transaksi ditolak ' . ($reject->receipt_no ?: '#' . $reject->id) . ($reject->resolved_at ? ' ditandai selesai.' : ' dibuka lagi.'));
    }
}
