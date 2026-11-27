<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Base\StockOpnameControllerBase;

use App\Models\Category;
use App\Models\Good;
use App\Models\GoodMovementRepository;
use App\Models\GoodUnit;
use App\Models\StockInspection;
use App\Models\StockInspectionDetail;
use App\Models\StockInspectionTarget;
use App\Services\SkemaCekObral;
use App\Services\StockService;

/**
 * Sidak stok toko (dipakai dari HP).
 *
 * Supervisor membuka satu sesi sidak, lalu untuk tiap barang: scan barcode /
 * minta barang acak / cari nama -> lihat stok sistem (jumlah seharusnya) ->
 * hitung fisik di rak -> simpan.
 *
 * Sidak bisa membawa daftar barang (tugas cek stok): dari centang di
 * Perputaran Barang, atau "Sidak dead stock" yang memilih sendiri barang Dead
 * Stock / Perlu Dicek yang stoknya masih ada. Daftarnya bisa dicek langsung
 * di HP, atau disalin jadi teks WA untuk pegawai lalu balasannya diketik di
 * daftar yang sama (nomor urutnya sama). "Dihitung oleh" mencatat nama
 * pegawai yang menghitung kalau bukan yang menginput.
 *
 * Sidak hanya mencatat selisih. Stok tidak diubah; koreksi lewat Stock Opname.
 */
class StockInspectionController extends Controller
{
    use StockOpnameControllerBase;

    /** Paling banyak barang dalam satu daftar tugas cek stok. */
    public const MAX_TARGETS = 300;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(canMenu('sidak'), 403, 'Menu Sidak Stok ditutup untuk akun Anda.');

            return $next($request);
        });
    }

    /** Daftar sesi sidak + tombol mulai. */
    public function index(Request $request)
    {
        // Tabel daftar tugas & kolom "dihitung oleh" dibuat otomatis (produksi tanpa terminal).
        SkemaCekObral::pastikan();
        $adaDaftar = SkemaCekObral::sidakAda();

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->toDateString());

        $inspections = StockInspection::with('details')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('id', 'desc')
            ->get();

        // Sesi yang belum diselesaikan selalu ditampilkan, kapan pun dibuatnya.
        $open = StockInspection::with($adaDaftar ? ['details', 'targets'] : ['details'])
            ->whereNull('finished_at')->orderBy('id', 'desc')->get();

        $categories = $adaDaftar ? Category::orderBy('name')->get(['id', 'name']) : collect();

        return view('admin.sidak.index', compact('inspections', 'open', 'startDate', 'endDate', 'adaDaftar', 'categories'));
    }

    /**
     * Mulai sidak. Dengan good_ids[] (centang di Perputaran Barang) sidaknya
     * membawa daftar barang yang harus dicek, urut sesuai centang.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'note'       => 'nullable|string|max:255',
            'counted_by' => 'nullable|string|max:100',
            'good_ids'   => 'nullable|array|max:' . self::MAX_TARGETS,
            'good_ids.*' => 'integer',
        ], [
            'good_ids.max' => 'Paling banyak ' . self::MAX_TARGETS . ' barang dalam satu tugas cek stok.',
        ]);

        $goodIds = array_values(array_unique(array_map('intval', $data['good_ids'] ?? [])));
        if ($goodIds && ! SkemaCekObral::sidakAda()) {
            return redirect()->back()->with('error', 'Tabel daftar cek stok belum dibuat. Buka menu Sidak Stok sekali, lalu coba lagi.');
        }

        $inspection = $this->createInspection($data['note'] ?? null, $data['counted_by'] ?? null, $goodIds);

        return redirect()->route('admin.sidak.show', $inspection->id);
    }

    /**
     * Sidak dead stock: daftar barang Dead Stock & Perlu Dicek yang stoknya
     * masih ada, dari nilai stok (modal tertahan) terbesar. Barang yang sudah
     * dicek dalam 30 hari terakhir dilewati.
     */
    public function storeDeadStock(Request $request, GoodMovementRepository $repo)
    {
        $data = $request->validate([
            'category'   => 'nullable|integer',
            'limit'      => 'required|integer|min:5|max:100',
            'counted_by' => 'nullable|string|max:100',
        ]);

        if (! SkemaCekObral::sidakAda()) {
            return redirect()->back()->with('error', 'Tabel daftar cek stok belum dibuat. Buka ulang menu Sidak Stok, lalu coba lagi.');
        }

        $now      = Carbon::now();
        $sudahCek = $repo->lastCheckedPerGood($now->copy()->subDays(GoodMovementRepository::CHECKED_FRESH_DAYS));
        $category = ! empty($data['category']) ? (string) (int) $data['category'] : null;
        $movement = $repo->getMovementData($now->copy()->subDays(90)->toDateString(), $now->toDateString(), $category, 'all', 'nilai_stok', 'desc');

        $goodIds = $movement['goods']
            ->filter(fn ($g) => in_array($g->status, ['dead', 'at_risk'], true) && $g->stok_sekarang > 0 && ! isset($sudahCek[$g->good_id]))
            ->take((int) $data['limit'])
            ->pluck('good_id')->all();

        if (! $goodIds) {
            return redirect()->back()->with('error', 'Tidak ada barang Dead Stock / Perlu Dicek yang stoknya masih ada dan belum dicek 30 hari terakhir'
                . ($category ? ' di kategori ini.' : '.'));
        }

        $nama = $category ? optional(Category::find($category))->name : null;
        $inspection = $this->createInspection('Cek dead stock' . ($nama ? ' ' . $nama : ''), $data['counted_by'] ?? null, $goodIds);

        return redirect()->route('admin.sidak.show', $inspection->id);
    }

    private function createInspection(?string $note, ?string $countedBy, array $goodIds): StockInspection
    {
        $fields = [
            'role'    => 'admin',
            'role_id' => \Auth::user()->id,
            'note'    => mb_substr(trim((string) $note), 0, 255) ?: null,
        ];
        if (SkemaCekObral::sidakAda()) {
            $fields['counted_by'] = mb_substr(trim((string) $countedBy), 0, 100) ?: null;
        }
        $inspection = StockInspection::create($fields);

        if ($goodIds) {
            // urutan centang dipertahankan; barang yang sudah dihapus dilewati
            $ada = [];
            foreach (array_chunk($goodIds, 500) as $chunk) {
                $ada += array_flip(Good::whereIn('id', $chunk)->pluck('id')->all());
            }
            $now  = Carbon::now();
            $rows = [];
            foreach ($goodIds as $goodId) {
                if (isset($ada[$goodId])) {
                    $rows[] = ['stock_inspection_id' => $inspection->id, 'good_id' => $goodId, 'position' => count($rows) + 1,
                               'created_at' => $now, 'updated_at' => $now];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                StockInspectionTarget::insert($chunk);
            }
        }

        return $inspection;
    }

    /** Halaman kerja (sesi berjalan) atau hasil (sesi selesai). */
    public function show($id)
    {
        SkemaCekObral::pastikan();
        $adaDaftar = SkemaCekObral::sidakAda();

        $inspection = StockInspection::with('details.good')->findOrFail($id);
        $summary    = $inspection->summary();
        $categories = $inspection->isFinished() ? collect() : Category::orderBy('name')->get(['id', 'name']);

        $details    = $inspection->details->map(fn ($d) => $this->detailArray($d, $d->good))->values();
        $targets    = $adaDaftar ? $this->targetArray($inspection) : [];

        // Setelah Stock Opname: barang yang fisiknya 0 dan stoknya sekarang
        // habis bisa langsung di-discontinue (bawaan tercentang: yang dari daftar).
        $kosong = $inspection->stock_opname_id ? $this->emptyAfterOpname($inspection, $targets) : [];

        return view('admin.sidak.show', compact('inspection', 'summary', 'categories', 'details', 'targets', 'adaDaftar', 'kosong'));
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
            'source'     => 'nullable|in:scan,acak,cari,daftar',
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

    public function finish(Request $request, $id)
    {
        $inspection = $this->openInspection($id);
        $request->validate(['counted_by' => 'nullable|string|max:100']);

        if ($inspection->details()->count() === 0) {
            // sesi kosong tidak ada gunanya disimpan
            $inspection->delete();

            return redirect()->route('admin.sidak.index');
        }

        $fields = ['finished_at' => Carbon::now()];
        if ($request->has('counted_by') && SkemaCekObral::sidakAda()) {
            $fields['counted_by'] = mb_substr(trim((string) $request->counted_by), 0, 100) ?: null;
        }
        $inspection->update($fields);

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

            // Yang menghitung fisik (pegawai) dicatat sebagai pemeriksa, yang menginput di belakangnya.
            $checker = $inspection->counted_by
                ? $inspection->counted_by . ' (input: ' . \Auth::user()->name . ')'
                : \Auth::user()->name;

            $request = new Request([
                'note'       => 'Sidak stok #' . $inspection->id,
                'checker'    => mb_substr($checker, 0, 255),
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
            return response()->json(['message' => 'Barang ' . $good->getFullName() . ' belum punya satuan.'], 422);
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

    /**
     * Daftar tugas cek stok: nomor, barang, satuan hitung (satuan terkecil),
     * dan hasilnya kalau sudah dicek. Stok sistem tidak dihitung di sini
     * (mahal untuk ratusan barang); muncul saat barang dibuka / disimpan.
     */
    private function targetArray(StockInspection $inspection): array
    {
        $targets = $inspection->targets()->get();
        if ($targets->isEmpty()) {
            return [];
        }

        $goods = collect();
        $units = collect();
        foreach (array_chunk($targets->pluck('good_id')->all(), 500) as $chunk) {
            $goods = $goods->merge(DB::table('goods')
                ->leftJoin('types', 'types.id', '=', 'goods.type_id')
                ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
                ->whereIn('goods.id', $chunk)
                ->get(['goods.id', 'goods.code', 'goods.name', 'types.name AS tipe', 'categories.name AS kategori']));
            $units = $units->merge(DB::table('good_units')
                ->join('units', 'units.id', '=', 'good_units.unit_id')
                ->whereIn('good_units.good_id', $chunk)
                ->whereNull('good_units.deleted_at')
                ->get(['good_units.good_id', 'units.name', 'units.quantity']));
        }
        $goods = $goods->keyBy('id');
        // satuan hitung = satuan terkecil, sama dengan getPcsSellingPrice()
        $smallest = $units->groupBy('good_id')->map(function ($list) {
            $pcs = $list->first(fn ($u) => (string) $u->quantity === '1');

            return ($pcs ?? $list->sortBy(fn ($u) => (float) $u->quantity)->first())->name;
        });

        $checked = $inspection->details->keyBy('good_id');

        return $targets->map(function ($t) use ($goods, $smallest, $checked) {
            $g = $goods->get($t->good_id);
            $d = $checked->get($t->good_id);

            return [
                'no'       => (int) $t->position,
                'good_id'  => (int) $t->good_id,
                'code'     => $g->code ?? '-',
                'name'     => $g ? Good::formatName($g->tipe, $g->name) : '(barang dihapus)',
                'category' => $g->kategori ?? null,
                'unit'     => $smallest->get($t->good_id) ?? '-',
                'detail'   => $d ? $this->detailArray($d, $d->good) : null,
            ];
        })->values()->all();
    }

    /** Barang yang fisiknya 0 dan stok sistemnya sekarang habis, belum di-discontinue. */
    private function emptyAfterOpname(StockInspection $inspection, array $targets): array
    {
        $dariDaftar = array_flip(array_column($targets, 'good_id'));
        $ids = $inspection->details->filter(fn ($d) => (float) $d->real_stock <= 0)->pluck('good_id')->all();
        if (! $ids) {
            return [];
        }

        return Good::whereIn('id', $ids)
            ->where('is_discontinued', 0)
            ->where('last_stock', '<=', 0)
            ->get()
            ->map(fn ($g) => ['good_id' => $g->id, 'name' => $g->getFullName(), 'from_list' => isset($dariDaftar[$g->id])])
            ->sortByDesc('from_list')->values()->all();
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
