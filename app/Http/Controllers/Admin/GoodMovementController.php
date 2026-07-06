<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\GoodMovementRepository;

class GoodMovementController extends Controller
{
    protected $repo;

    public function __construct(GoodMovementRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Halaman utama analisis pergerakan barang.
     * GET /admin/reports/movement
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $kategori  = $request->get('kategori',   null);
        $status    = $request->get('status',     'all');
        $sortBy    = $request->get('sort_by',    'total_omzet');
        $sortDir   = $request->get('sort_dir',   'desc');

        $allowedSort = [
            'total_omzet', 'total_transaksi', 'total_qty',
            'days_of_stock', 'days_since_trx', 'stok_sekarang',
            'nilai_stok', 'total_laba', 'urgency',
        ];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'total_omzet';
        }

        $data              = $this->repo->getMovementData($startDate, $endDate, $kategori, $status, $sortBy, $sortDir);
        $categories        = $this->repo->getCategories();
        $discontinuedCount = $this->repo->countDiscontinued();
        $negativeStockCount = $this->repo->countNegativeStock();
        $negativeStockGoods = $negativeStockCount > 0 ? $this->repo->getNegativeStockGoods(20) : collect();

        return view('admin.good-movement', [
            'goods'              => $data['goods'],
            'summary'            => $data['summary'],
            'days'               => $data['days'],
            'categories'         => $categories,
            'discontinuedCount'  => $discontinuedCount,
            'negativeStockCount' => $negativeStockCount,
            'negativeStockGoods' => $negativeStockGoods,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'kategori'           => $kategori,
            'status'             => $status,
            'sortBy'             => $sortBy,
            'sortDir'            => $sortDir,
        ]);
    }

    /**
     * Tandai barang sebagai discontinued.
     * POST /admin/reports/movement/{id}/discontinue
     */
    public function discontinue(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'Alasan discontinue wajib diisi.',
            'reason.min'      => 'Alasan minimal 5 karakter.',
        ]);

        $userId = auth()->id() ?? 1;

        $ok = $this->repo->markDiscontinued($id, $request->input('reason'), $userId);

        if (!$ok) {
            return redirect()->back()
                ->with('error', 'Barang tidak ditemukan atau sudah dihapus.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', 'Barang berhasil di-discontinue dan tidak akan muncul dalam laporan.');
    }

    /**
     * Tandai BANYAK barang sekaligus sebagai discontinued (checklist).
     * Dipakai untuk beres-beres data lama tanpa harus satu-per-satu.
     * POST /admin/reports/movement/bulk-discontinue
     */
    public function bulkDiscontinue(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'reason' => 'required|string|min:5|max:500',
        ], [
            'ids.required'    => 'Pilih minimal satu barang terlebih dahulu.',
            'reason.required' => 'Alasan discontinue wajib diisi.',
            'reason.min'      => 'Alasan minimal 5 karakter.',
        ]);

        $userId = auth()->id() ?? 1;

        $count = $this->repo->markDiscontinuedBulk(
            $request->input('ids'),
            $request->input('reason'),
            $userId
        );

        if ($count === 0) {
            return redirect()->back()
                ->with('error', 'Tidak ada barang yang berhasil di-discontinue. Coba muat ulang halaman.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', $count . ' barang berhasil di-discontinue sekaligus.');
    }

    /**
     * Aktifkan kembali barang yang sudah discontinued.
     * POST /admin/reports/movement/{id}/restore
     */
    public function restore(int $id)
    {
        $ok = $this->repo->restoreDiscontinued($id);

        if (!$ok) {
            return redirect()->back()
                ->with('error', 'Barang tidak ditemukan atau tidak dalam status discontinued.');
        }

        return redirect()
            ->route('admin.reports.movement', ['status' => 'discontinued'])
            ->with('success', 'Barang berhasil diaktifkan kembali.');
    }
}