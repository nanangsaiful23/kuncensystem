<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Base\StockOpnameControllerBase;

use App\Models\Category;
use App\Models\Good;
use App\Models\GoodUnit;
use App\Models\StockInspection;
use App\Models\StockInspectionDetail;
use App\Services\StockService;

/**
 * Sidak stok toko (dipakai dari HP).
 *
 * Supervisor membuka satu sesi sidak, lalu untuk tiap barang: scan barcode /
 * minta barang acak / cari nama -> lihat stok sistem (jumlah seharusnya) ->
 * hitung fisik di rak -> simpan.
 *
 * Sidak hanya mencatat selisih. Stok tidak diubah; koreksi lewat Stock Opname.
 */
class StockInspectionController extends Controller
{
    use StockOpnameControllerBase;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(isSupervisor(), 403, 'Sidak stok hanya untuk supervisor.');

            return $next($request);
        });
    }

    /** Daftar sesi sidak + tombol mulai. */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->toDateString());

        $inspections = StockInspection::with('details')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('id', 'desc')
            ->get();

        // Sesi yang belum diselesaikan selalu ditampilkan, kapan pun dibuatnya.
        $open = StockInspection::with('details')->whereNull('finished_at')->orderBy('id', 'desc')->get();

        return view('admin.sidak.index', compact('inspections', 'open', 'startDate', 'endDate'));
    }

    public function store(Request $request)
    {
        $inspection = StockInspection::create([
            'role'    => 'admin',
            'role_id' => \Auth::user()->id,
            'note'    => mb_substr(trim((string) $request->note), 0, 255) ?: null,
        ]);

        return redirect()->route('admin.sidak.show', $inspection->id);
    }

    /** Halaman kerja (sesi berjalan) atau hasil (sesi selesai). */
    public function show($id)
    {
        $inspection = StockInspection::with('details.good')->findOrFail($id);
        $summary    = $inspection->summary();
        $categories = $inspection->isFinished() ? collect() : Category::orderBy('name')->get(['id', 'name']);

        $details    = $inspection->details->map(fn ($d) => $this->detailArray($d, $d->good))->values();

        return view('admin.sidak.show', compact('inspection', 'summary', 'categories', 'details'));
    }

    /** Cari satu barang dari barcode hasil scan. */
    public function findByCode(Request $request, $id)
    {
        $inspection = $this->openInspection($id);
        $code = trim((string) $request->get('code'));

        if ($code === '') {
            return response()->json(['message' => 'Barcode kosong.'], 422);
        }

        // Scanner kadang menambah / membuang angka 0 di depan (UPC-A vs EAN-13).
        $good = Good::where('code', $code)->first()
            ?? Good::whereIn('code', array_unique(['0' . $code, ltrim($code, '0') ?: $code]))->first();

        if ($good === null) {
            return response()->json(['message' => 'Barcode ' . $code . ' tidak terdaftar di sistem.'], 404);
        }

        return $this->itemResponse($inspection, $good);
    }

    /** Ambil satu barang acak yang belum dicek pada sesi ini. */
    public function random(Request $request, $id)
    {
        $inspection = $this->openInspection($id);

        $good = Good::where('is_discontinued', 0)
            ->where('last_stock', '>', 0)
            ->whereNotIn('id', $inspection->details()->pluck('good_id'))
            ->whereIn('id', GoodUnit::select('good_id'))
            ->when($request->get('category'), fn ($q, $category) => $q->where('category_id', (int) $category))
            ->inRandomOrder()
            ->first();

        if ($good === null) {
            return response()->json(['message' => 'Tidak ada lagi barang berstok yang belum dicek.'], 404);
        }

        return $this->itemResponse($inspection, $good);
    }

    /** Cari barang dari nama / kode (kalau barcode rusak atau tidak ada). */
    public function search(Request $request, $id)
    {
        $this->openInspection($id);
        $keyword = trim((string) $request->get('q'));

        if (mb_strlen($keyword) < 2) {
            return response()->json(['goods' => []]);
        }

        $goods = Good::where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('code', 'like', '%' . $keyword . '%');
            })
            ->whereIn('id', GoodUnit::select('good_id'))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'goods' => $goods->map(fn ($good) => [
                'good_id' => $good->id,
                'code'    => $good->code,
                'name'    => $good->getFullName(),
            ])->values(),
        ]);
    }

    public function item($id, $goodId)
    {
        return $this->itemResponse($this->openInspection($id), Good::findOrFail($goodId));
    }

    /** Simpan hasil hitung fisik satu barang. */
    public function check(Request $request, $id)
    {
        $inspection = $this->openInspection($id);

        $data = $request->validate([
            'good_id'    => 'required|integer',
            'real_stock' => 'required|numeric|min:0|max:9999999',
            'source'     => 'nullable|in:scan,acak,cari',
            'note'       => 'nullable|string|max:255',
        ]);

        $good = Good::findOrFail($data['good_id']);
        $unit = $this->smallestUnit($good);

        if ($unit === null) {
            return response()->json(['message' => 'Barang ini belum punya satuan.'], 422);
        }

        // Stok sistem diambil dari server saat disimpan, bukan dari halaman.
        $systemStock = StockService::stock($good);
        $realStock   = round((float) $data['real_stock'], 3);

        $detail = StockInspectionDetail::updateOrCreate(
            ['stock_inspection_id' => $inspection->id, 'good_id' => $good->id],
            [
                'good_unit_id' => $unit->id,
                'unit_name'    => $unit->name,
                'system_stock' => $systemStock,
                'real_stock'   => $realStock,
                'difference'   => round($realStock - $systemStock, 3),
                'buy_price'    => (float) $unit->buy_price,
                'source'       => $data['source'] ?? 'scan',
                'note'         => $data['note'] ?? null,
            ]
        );

        return response()->json([
            'detail'  => $this->detailArray($detail, $good),
            'summary' => $inspection->summary(),
        ]);
    }

    public function removeDetail($id, $detailId)
    {
        $inspection = $this->openInspection($id);

        StockInspectionDetail::where('stock_inspection_id', $inspection->id)->where('id', $detailId)->delete();

        return response()->json(['summary' => $inspection->summary()]);
    }

    public function finish($id)
    {
        $inspection = $this->openInspection($id);

        if ($inspection->details()->count() === 0) {
            // sesi kosong tidak ada gunanya disimpan
            $inspection->delete();

            return redirect()->route('admin.sidak.index');
        }

        $inspection->update(['finished_at' => Carbon::now()]);

        return redirect()->route('admin.sidak.show', $inspection->id);
    }

    /**
     * Jadikan barang yang selisih pada sidak yang sudah selesai sebagai satu
     * Stock Opname (stok dikoreksi + jurnal dibuat, lewat alur opname biasa).
     * Hanya bisa sekali per sidak.
     *
     * Yang dipakai adalah SELISIH-nya, bukan angka fisik mentah: stok baru =
     * stok sistem saat ini + selisih sidak. Jadi penjualan / loading yang
     * terjadi setelah barang dihitung tidak ikut terhapus.
     */
    public function toStockOpname($id)
    {
        $stockOpname = DB::transaction(function () use ($id) {
            // kunci baris supaya dua kali tekan tidak membuat dua opname
            $inspection = StockInspection::lockForUpdate()->findOrFail($id);

            abort_unless($inspection->isFinished(), 409, 'Selesaikan sidak dulu.');
            abort_if($inspection->stock_opname_id !== null, 409, 'Sidak ini sudah dijadikan Stock Opname.');

            $names = $units = $oldStocks = $newStocks = [];

            foreach ($inspection->details()->where('difference', '<>', 0)->get() as $detail) {
                $good     = Good::find($detail->good_id);
                $goodUnit = GoodUnit::with('unit')->find($detail->good_unit_id);

                // barang / satuannya sudah dihapus sejak sidak: tidak bisa dikoreksi
                if ($good === null || $goodUnit === null || $goodUnit->unit === null) {
                    continue;
                }

                $current = round(StockService::stock($good) * StockService::smallestQuantity($good)
                    / max((float) $goodUnit->unit->quantity, 0.000001), 3);

                $names[]     = $good->id;
                $units[]     = $goodUnit->unit_id;
                $oldStocks[] = $current;
                $newStocks[] = round($current + (float) $detail->difference, 3);
            }

            abort_if(count($names) === 0, 422, 'Tidak ada barang selisih yang bisa dikoreksi.');

            $request = new Request([
                'note'       => 'Sidak stok #' . $inspection->id,
                'checker'    => \Auth::user()->name,
                'names'      => $names,
                'units'      => $units,
                'old_stocks' => $oldStocks,
                'new_stocks' => $newStocks,
            ]);

            $stockOpname = $this->storeStockOpnameBase('admin', \Auth::user()->id, $request);

            $inspection->update(['stock_opname_id' => $stockOpname->id]);

            return $stockOpname;
        });

        session(['alert' => 'add', 'data' => 'stock opname dari sidak stok']);

        return redirect('/admin/stock-opname/' . $stockOpname->id . '/detail');
    }

    // ------------------------------------------------------------------

    private function openInspection($id): StockInspection
    {
        $inspection = StockInspection::findOrFail($id);

        abort_if($inspection->isFinished(), 409, 'Sidak ini sudah diselesaikan.');

        return $inspection;
    }

    /** Satuan terkecil barang (satuan yang dipakai StockService::stock()). */
    private function smallestUnit(Good $good)
    {
        // getPcsSellingPrice() berhenti dengan dd() kalau barang tanpa satuan
        if (! GoodUnit::where('good_id', $good->id)->exists()) {
            return null;
        }

        return $good->getPcsSellingPrice();
    }

    /** Data barang untuk form hitung, termasuk stok sistem (jumlah seharusnya). */
    private function itemResponse(StockInspection $inspection, Good $good)
    {
        $unit = $this->smallestUnit($good);

        if ($unit === null) {
            return response()->json(['message' => 'Barang ' . $good->name . ' belum punya satuan.'], 422);
        }

        $checked = StockInspectionDetail::where('stock_inspection_id', $inspection->id)
            ->where('good_id', $good->id)
            ->first();

        return response()->json([
            'good_id'  => $good->id,
            'code'     => $good->code,
            'name'     => $good->getFullName(),
            'category' => optional($good->category)->name,
            'unit'     => $unit->name,
            'system_stock' => StockService::stock($good),
            'checked'  => $checked ? (float) $checked->real_stock : null,
        ]);
    }

    private function detailArray(StockInspectionDetail $detail, ?Good $good): array
    {
        return [
            'id'           => $detail->id,
            'good_id'      => $detail->good_id,
            'code'         => $good->code ?? '-',
            'name'         => $good ? $good->getFullName() : '(barang dihapus)',
            'unit'         => $detail->unit_name,
            'system_stock' => (float) $detail->system_stock,
            'real_stock'   => (float) $detail->real_stock,
            'difference'   => (float) $detail->difference,
            'value'        => $detail->valueDifference(),
            'note'         => $detail->note,
        ];
    }
}
