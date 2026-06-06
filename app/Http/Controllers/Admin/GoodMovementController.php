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

    public function index(Request $request)
    {
        // Filter default: 90 hari terakhir
        $startDate = $request->get('start_date', Carbon::now()->subDays(90)->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $kategori  = $request->get('kategori',   null);
        $status    = $request->get('status',     'all');
        $sortBy    = $request->get('sort_by',    'total_omzet');
        $sortDir   = $request->get('sort_dir',   'desc');

        // Validasi sort column (whitelist)
        $allowedSort = [
            'total_omzet', 'total_transaksi', 'total_qty',
            'days_of_stock', 'days_since_trx', 'stok_sekarang',
            'nilai_stok', 'total_laba', 'urgency',
        ];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'total_omzet';
        }

        $data       = $this->repo->getMovementData($startDate, $endDate, $kategori, $status, $sortBy, $sortDir);
        $categories = $this->repo->getCategories();

        return view('admin.good-movement', [
            'goods'      => $data['goods'],
            'summary'    => $data['summary'],
            'days'       => $data['days'],
            'categories' => $categories,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'kategori'   => $kategori,
            'status'     => $status,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ]);
    }
}