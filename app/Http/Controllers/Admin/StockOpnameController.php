<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\StockOpnameControllerBase;

use App\Models\StockOpname;
use App\Models\Good;
use App\Models\Journal;
use App\Services\StockOpnameService;

class StockOpnameController extends Controller
{
    use StockOpnameControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'all';

        $stock_opnames = $this->indexStockOpnameBase($start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'stock_opnames', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'create';

        // Badge jumlah barang minus, ditampilkan di atas form supaya
        // pemilik toko langsung sadar ada data yang perlu dibereskan.
        $minusCount = Good::whereNull('deleted_at')
            ->where('last_stock', '<', 0)
            ->count();

        return view('admin.layout.page', compact('default', 'minusCount'));
    }

    /**
     * Daftar barang dengan stok minus (last_stock < 0), untuk dipilih
     * dan langsung ditambahkan ke form stock opname.
     *
     * GET /admin/stock-opname/minus-goods?keyword=
     */
    public function minusGoods(Request $request)
    {
        $keyword = trim((string) $request->get('keyword', ''));

        $query = Good::whereNull('goods.deleted_at')
            ->where('goods.last_stock', '<', 0);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('goods.name', 'like', '%' . $keyword . '%')
                  ->orWhere('goods.code', 'like', '%' . $keyword . '%');
            });
        }

        // Urutkan paling minus dulu — biasanya itu yang paling "parah"
        // dan paling butuh perhatian.
        $goods = $query->orderBy('goods.last_stock', 'asc')
            ->limit(500)
            ->get();

        // Akun yang membuat stoknya minus, kalau tercatat.
        $penyebab = \App\Models\StockMinusLog::terbukaUntuk($goods->pluck('id')->all());

        $result = $goods->map(function ($good) use ($penyebab) {
            // Pakai fallback satuan yang sama seperti dipakai di seluruh
            // sistem (Good::getPcsSellingPrice) supaya konsisten dengan
            // satuan yang dipakai saat input stock opname manual.
            $unit = $good->getPcsSellingPrice();

            return [
                'good_id'    => $good->id,
                'code'       => $good->code,
                'name'       => $good->getFullName(),
                'last_stock' => (float) $good->last_stock,
                'unit_id'    => $unit->unit_id  ?? null,
                'unit_name'  => $unit->name     ?? '-',
                'unit_qty'   => $unit->quantity ?? 1,
                'penyebab'   => isset($penyebab[$good->id]) ? $penyebab[$good->id]->teks() : null,
            ];
        })->values();

        return response()->json([
            'total' => $result->count(),
            'goods' => $result,
        ]);
    }

    public function store(Request $request)
    {
        $stock_opname = $this->storeStockOpnameBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'stock opname barang']);

        return redirect('/admin/stock-opname/' . $stock_opname->id . '/detail');
    }

    public function detail($stock_opname_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'detail';

        $stock_opname = StockOpname::findOrFail($stock_opname_id);

        // Rugi terbesar di atas, lalu kelebihan terbesar, lalu yang cocok.
        $details = $stock_opname->detailsWithDeleted()->map(function ($detail) {
            $detail->selisih = round((float) $detail->new_stock - (float) $detail->old_stock, 3);
            // total selalu positif; tandanya dari selisih (stok sama = 0, juga untuk data lama)
            $detail->nilai = $detail->selisih == 0 ? 0.0 : ($detail->selisih > 0 ? 1 : -1) * (float) $detail->total;

            return $detail;
        })->sort(function ($a, $b) {
            $grup = fn ($d) => $d->nilai < 0 ? 0 : ($d->nilai > 0 ? 1 : 2);

            return [$grup($a), -abs($a->nilai)] <=> [$grup($b), -abs($b->nilai)];
        })->values();

        $journals = Journal::where('type', StockOpnameService::JURNAL)
                           ->where('type_id', $stock_opname->id)
                           ->orderBy('id')
                           ->get();

        return view('admin.layout.page', compact('default', 'stock_opname', 'details', 'journals'));
    }
}