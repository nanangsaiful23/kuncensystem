<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreHealthRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StoreHealthController extends Controller
{
    protected $repo;

    public function __construct(StoreHealthRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        try {
            $health = $this->repo->getHealthData($startDate, $endDate);
        } catch (\Throwable $e) {
            \Log::error('StoreHealth error: ' . $e->getMessage());

            $health = [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'metrics' => [],
                'scores' => [
                    'overall' => 0,
                    'financial' => 0,
                    'stock' => 0,
                    'decision' => 0,
                    'label' => 'Data Belum Siap',
                    'color' => 'red',
                ],
                'recommendations' => collect(),
                'critical_goods' => collect(),
                'top_receivables' => collect(),
                'locked_stock' => collect(),
                'cash_flow' => collect(),
            ];
        }

        return view('admin.store-health', compact('health', 'startDate', 'endDate'));
    }
}
