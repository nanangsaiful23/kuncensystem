<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreHealthRepository
{
    const FAST_MOVING_MIN_TRX = 10;
    const SLOW_MOVING_MAX_TRX = 3;
    const ACTIVE_LAST_TRX_DAYS = 30;
    const DEAD_STOCK_DAYS = 90;
    const REORDER_STOCK_MIN = 10;

    protected $salesRepo;
    protected $financialRepo;
    protected $movementRepo;

    public function __construct(
        SalesReportRepository $salesRepo,
        FinancialReportRepository $financialRepo,
        GoodMovementRepository $movementRepo
    ) {
        $this->salesRepo = $salesRepo;
        $this->financialRepo = $financialRepo;
        $this->movementRepo = $movementRepo;
    }

    public function getHealthData($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        $sales = $this->salesRepo->getSalesSummary($start, $end);
        $profitLoss = $this->salesRepo->getProfitLossSummary($start, $end);
        $receivables = $this->salesRepo->getMemberReceivables();
        $stockValuation = $this->getStoreHealthStockValuation();
        $cashFlow = $this->financialRepo->getCashFlowSummary($start, $end);
        $movement = $this->getStoreHealthMovementData($start, $end, $stockValuation);

        $movementGoods = $movement['goods'];
        $movementSummary = $movement['summary'];

        $metrics = $this->buildMetrics($sales, $profitLoss, $receivables, $stockValuation, $cashFlow, $movementSummary);
        $scores = $this->buildScores($metrics);
        $recommendations = $this->buildRecommendations($metrics, $movementGoods, $receivables, $stockValuation);

        return [
            'period' => [
                'start_date' => $start,
                'end_date' => $end,
            ],
            'metrics' => $metrics,
            'scores' => $scores,
            'recommendations' => $recommendations,
            'critical_goods' => $movementGoods->sortByDesc('urgency')->take(15)->values(),
            'top_receivables' => $receivables->sortByDesc('sisa_piutang')->take(10)->values(),
            'locked_stock' => $stockValuation->sortByDesc('nilai_hpp')->take(10)->values(),
            'cash_flow' => $cashFlow,
        ];
    }

    private function getStoreHealthStockValuation()
    {
        $fallbackUnit = $this->fallbackUnitSubquery();

        $stock = Good::leftJoinSub($fallbackUnit, 'fallback_unit', 'fallback_unit.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS base_gu', function ($join) {
                $join->on('base_gu.id', '=', 'goods.base_unit_id')
                    ->whereNull('base_gu.deleted_at');
            })
            ->leftJoin('units AS base_u', 'base_u.id', '=', 'base_gu.unit_id')
            ->leftJoin('good_units AS fallback_gu', function ($join) {
                $join->on('fallback_gu.id', '=', 'fallback_unit.good_unit_id')
                    ->whereNull('fallback_gu.deleted_at');
            })
            ->leftJoin('units AS fallback_u', 'fallback_u.id', '=', 'fallback_gu.unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->whereNull('goods.deleted_at')
            ->whereRaw('COALESCE(base_gu.id, fallback_gu.id) IS NOT NULL')
            ->select(
                'goods.id AS good_id',
                'goods.code AS kode',
                'goods.name AS nama_barang',
                'types.name AS tipe',
                'categories.name AS kategori',
                'brands.name AS merk',
                DB::raw('COALESCE(base_u.name, fallback_u.name) AS satuan'),
                DB::raw('COALESCE(base_u.quantity, fallback_u.quantity, 1) AS unit_qty'),
                DB::raw('COALESCE(base_gu.id, fallback_gu.id) AS stock_good_unit_id'),
                'goods.last_stock AS stok_akhir',
                DB::raw('COALESCE(base_gu.buy_price, fallback_gu.buy_price, 0) AS harga_beli'),
                DB::raw('COALESCE(base_gu.selling_price, fallback_gu.selling_price, 0) AS harga_jual'),
                DB::raw('goods.last_stock * COALESCE(base_gu.buy_price, fallback_gu.buy_price, 0) AS nilai_hpp'),
                DB::raw('goods.last_stock * COALESCE(base_gu.selling_price, fallback_gu.selling_price, 0) AS nilai_jual'),
                DB::raw('goods.last_stock * (COALESCE(base_gu.selling_price, fallback_gu.selling_price, 0) - COALESCE(base_gu.buy_price, fallback_gu.buy_price, 0)) AS potensi_laba')
            )
            ->orderBy('nilai_hpp', 'desc')
            ->get()
            ->map(function ($row) {
                $row->nama_barang = $this->formatGoodName($row->tipe ?? null, $row->nama_barang);
                return $row;
            });

        return $this->attachUnitBreakdown($stock);
    }

    private function attachUnitBreakdown($stock)
    {
        if ($stock->isEmpty()) {
            return $stock;
        }

        $unitRows = GoodUnit::join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereIn('good_units.good_id', $stock->pluck('good_id')->all())
            ->whereNull('good_units.deleted_at')
            ->select(
                'good_units.id AS good_unit_id',
                'good_units.good_id',
                'units.name AS satuan',
                'units.quantity AS unit_qty',
                'good_units.buy_price',
                'good_units.selling_price'
            )
            ->orderBy('good_units.good_id')
            ->orderByRaw('CAST(units.quantity AS UNSIGNED) ASC')
            ->get()
            ->groupBy('good_id');

        return $stock->map(function ($good) use ($unitRows) {
            $baseQty = (float) ($good->unit_qty ?: 1);
            $stockInSmallestUnit = (float) $good->stok_akhir * $baseQty;

            $good->unit_breakdown = $unitRows->get($good->good_id, collect())->map(function ($unit) use ($stockInSmallestUnit) {
                $unitQty = (float) ($unit->unit_qty ?: 1);
                $stockByUnit = $unitQty > 0 ? $stockInSmallestUnit / $unitQty : 0;

                return (object) [
                    'good_unit_id' => $unit->good_unit_id,
                    'satuan' => $unit->satuan,
                    'unit_qty' => $unitQty,
                    'stok_setara' => $stockByUnit,
                    'buy_price' => (float) $unit->buy_price,
                    'selling_price' => (float) $unit->selling_price,
                    'nilai_hpp_setara' => $stockByUnit * (float) $unit->buy_price,
                    'nilai_jual_setara' => $stockByUnit * (float) $unit->selling_price,
                ];
            })->values();

            return $good;
        });
    }

    private function getStoreHealthMovementData($startDate, $endDate, $stockValuation): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $days = $start->diffInDays($end) ?: 1;

        $fallbackUnit = $this->fallbackUnitSubquery();

        $salesAgg = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('good_units AS sold_gu', 'sold_gu.id', '=', 'transaction_details.good_unit_id')
            ->join('goods', 'goods.id', '=', 'sold_gu.good_id')
            ->leftJoinSub($fallbackUnit, 'fallback_unit', 'fallback_unit.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS base_gu', function ($join) {
                $join->on('base_gu.id', '=', 'goods.base_unit_id')
                    ->whereNull('base_gu.deleted_at');
            })
            ->leftJoin('units AS base_u', 'base_u.id', '=', 'base_gu.unit_id')
            ->leftJoin('good_units AS fallback_gu', function ($join) {
                $join->on('fallback_gu.id', '=', 'fallback_unit.good_unit_id')
                    ->whereNull('fallback_gu.deleted_at');
            })
            ->leftJoin('units AS fallback_u', 'fallback_u.id', '=', 'fallback_gu.unit_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->select(
                'sold_gu.good_id',
                DB::raw('COALESCE(SUM(transaction_details.real_quantity / COALESCE(NULLIF(CAST(base_u.quantity AS DECIMAL(18,4)), 0), NULLIF(CAST(fallback_u.quantity AS DECIMAL(18,4)), 0), 1)), 0) AS total_qty_terjual'),
                DB::raw('COUNT(DISTINCT transactions.id) AS total_transaksi'),
                DB::raw('COALESCE(SUM(transaction_details.sum_price), 0) AS total_omzet'),
                DB::raw('COALESCE(SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity), 0) AS total_laba'),
                DB::raw('MAX(transactions.created_at) AS last_transaction_at')
            )
            ->groupBy('sold_gu.good_id')
            ->get()
            ->keyBy('good_id');

        $lastNormalTrx = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select('good_units.good_id', DB::raw('MAX(transactions.created_at) AS last_normal_at'))
            ->groupBy('good_units.good_id')
            ->get()
            ->keyBy('good_id');

        $result = $stockValuation->map(function ($good) use ($salesAgg, $lastNormalTrx, $days) {
            $agg = $salesAgg->get($good->good_id);
            $totalTrx = $agg ? (int) $agg->total_transaksi : 0;
            $totalQty = $agg ? (float) $agg->total_qty_terjual : 0;
            $totalOmzet = $agg ? (float) $agg->total_omzet : 0;
            $totalLaba = $agg ? (float) $agg->total_laba : 0;
            $lastNormal = $lastNormalTrx->get($good->good_id);
            $lastTrxAt = $agg ? $agg->last_transaction_at : ($lastNormal ? $lastNormal->last_normal_at : null);
            $stok = (float) $good->stok_akhir;
            $hargaBeli = (float) $good->harga_beli;
            $hargaJual = (float) $good->harga_jual;
            $daysSinceTrx = $lastTrxAt ? (int) Carbon::parse($lastTrxAt)->diffInDays(Carbon::now()) : 999;
            $avgQtyPerDay = $days > 0 ? round($totalQty / $days, 2) : 0;
            $daysOfStock = ($avgQtyPerDay > 0 && $stok > 0) ? (int) round($stok / $avgQtyPerDay) : ($stok > 0 ? 9999 : 0);
            $classification = $this->classifyMovement($totalTrx, $daysSinceTrx, $stok);
            $margin = $hargaJual > 0 ? round(($hargaJual - $hargaBeli) / $hargaJual * 100, 1) : 0;
            $recommendation = $this->recommendMovement($classification, $stok, $daysOfStock, $totalTrx, $margin);

            return (object) [
                'good_id' => $good->good_id,
                'kode' => $good->kode,
                'nama' => $good->nama_barang,
                'kategori' => $good->kategori ?? '-',
                'merk' => $good->merk ?? '-',
                'satuan' => $good->satuan ?? '-',
                'stok_sekarang' => $stok,
                'unit_qty' => (float) ($good->unit_qty ?: 1),
                'unit_breakdown' => $good->unit_breakdown ?? collect(),
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'nilai_stok' => (float) $good->nilai_hpp,
                'margin_pct' => $margin,
                'total_transaksi' => $totalTrx,
                'total_qty' => $totalQty,
                'total_omzet' => $totalOmzet,
                'total_laba' => $totalLaba,
                'avg_qty_per_day' => $avgQtyPerDay,
                'days_of_stock' => $daysOfStock,
                'days_since_trx' => $daysSinceTrx,
                'last_trx_at' => $lastTrxAt,
                'status' => $classification['status'],
                'status_label' => $classification['label'],
                'status_color' => $classification['color'],
                'recommendation' => $recommendation['action'],
                'rec_label' => $recommendation['label'],
                'rec_color' => $recommendation['color'],
                'rec_icon' => $recommendation['icon'],
                'urgency' => $recommendation['urgency'],
            ];
        })->sortByDesc('urgency')->values();

        return [
            'goods' => $result,
            'summary' => $this->buildMovementSummary($result, $days),
            'days' => $days,
        ];
    }

    /**
     * Gabungkan tipe + nama barang jadi satu string tampilan, meniru persis
     * Good::getFullName() supaya penamaan konsisten dengan laporan lain
     * (Movement, Reorder, Sales).
     */
    private function formatGoodName(?string $tipe, string $nama): string
    {
        $tipe = trim((string) $tipe);
        if ($tipe === '' || $tipe === '-') {
            return ucfirst($nama);
        }
        return ucfirst(strtolower($tipe) . ' ' . $nama);
    }

    private function fallbackUnitSubquery()
    {
        return DB::table('good_units AS gu_min')
            ->join('units AS u_min', 'u_min.id', '=', 'gu_min.unit_id')
            ->whereNull('gu_min.deleted_at')
            ->select(
                'gu_min.good_id',
                DB::raw('SUBSTRING_INDEX(GROUP_CONCAT(gu_min.id ORDER BY CAST(u_min.quantity AS UNSIGNED) ASC, gu_min.id ASC), ",", 1) AS good_unit_id')
            )
            ->groupBy('gu_min.good_id');
    }

    private function buildMetrics($sales, $profitLoss, $receivables, $stockValuation, $cashFlow, $movementSummary): array
    {
        $totalOmzet = (float) ($sales['totalOmzet'] ?? 0);
        $totalLaba = (float) ($sales['totalLaba'] ?? 0);
        $totalHpp = (float) ($sales['totalHpp'] ?? 0);
        $totalDiskon = (float) ($sales['totalDiskon'] ?? 0);
        $totalTransaksi = (int) ($sales['totalTransaksi'] ?? 0);

        $marginPct = $totalOmzet > 0 ? round($totalLaba / $totalOmzet * 100, 1) : 0;
        $discountPct = $totalOmzet > 0 ? round($totalDiskon / $totalOmzet * 100, 1) : 0;

        $cashIn = (float) $cashFlow->sum('masuk');
        $cashOut = (float) $cashFlow->sum('keluar');
        $cashEnd = (float) $cashFlow->sum('saldo_akhir');
        $cashNet = $cashIn - $cashOut;

        $receivableTotal = (float) $receivables->sum('sisa_piutang');
        $receivableRatio = $totalOmzet > 0 ? round($receivableTotal / $totalOmzet * 100, 1) : 0;

        $stockHpp = (float) $stockValuation->sum('nilai_hpp');
        $stockPotentialProfit = (float) $stockValuation->sum('potensi_laba');
        $stockToSalesRatio = $totalOmzet > 0 ? round($stockHpp / $totalOmzet * 100, 1) : 0;

        $totalGoods = max((int) ($movementSummary['total'] ?? 0), 1);
        $fastCount = (int) ($movementSummary['fastCount'] ?? 0);
        $slowCount = (int) ($movementSummary['slowCount'] ?? 0);
        $deadCount = (int) ($movementSummary['deadCount'] ?? 0);
        $reorderCount = (int) ($movementSummary['reorderCount'] ?? 0);
        $reviewCount = (int) ($movementSummary['reviewCount'] ?? 0);
        $discontinueCount = (int) ($movementSummary['discontinueCount'] ?? 0);

        return [
            'total_omzet' => $totalOmzet,
            'total_laba' => $totalLaba,
            'total_hpp' => $totalHpp,
            'total_diskon' => $totalDiskon,
            'total_transaksi' => $totalTransaksi,
            'rata_transaksi' => (float) ($sales['rataRata'] ?? 0),
            'margin_pct' => $marginPct,
            'discount_pct' => $discountPct,
            'omzet_bersih' => (float) ($profitLoss['omzetBersih'] ?? 0),
            'total_pembelian' => (float) ($profitLoss['totalPembelian'] ?? 0),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_net' => $cashNet,
            'cash_end' => $cashEnd,
            'receivable_total' => $receivableTotal,
            'receivable_ratio' => $receivableRatio,
            'stock_hpp' => $stockHpp,
            'stock_potential_profit' => $stockPotentialProfit,
            'stock_to_sales_ratio' => $stockToSalesRatio,
            'goods_total' => $totalGoods,
            'fast_count' => $fastCount,
            'slow_count' => $slowCount,
            'dead_count' => $deadCount,
            'fast_pct' => round($fastCount / $totalGoods * 100, 1),
            'slow_pct' => round($slowCount / $totalGoods * 100, 1),
            'dead_pct' => round($deadCount / $totalGoods * 100, 1),
            'reorder_count' => $reorderCount,
            'review_count' => $reviewCount,
            'discontinue_count' => $discontinueCount,
        ];
    }

    private function buildScores(array $metrics): array
    {
        $financialScore = 100;
        $financialScore -= $metrics['margin_pct'] < 10 ? 25 : ($metrics['margin_pct'] < 18 ? 12 : 0);
        $financialScore -= $metrics['cash_net'] < 0 ? 20 : 0;
        $financialScore -= $metrics['receivable_ratio'] > 30 ? 20 : ($metrics['receivable_ratio'] > 15 ? 10 : 0);
        $financialScore -= $metrics['discount_pct'] > 12 ? 10 : 0;

        $stockScore = 100;
        $stockScore -= $metrics['dead_pct'] > 35 ? 30 : ($metrics['dead_pct'] > 20 ? 18 : 0);
        $stockScore -= $metrics['slow_pct'] > 45 ? 20 : ($metrics['slow_pct'] > 30 ? 10 : 0);
        $stockScore -= $metrics['reorder_count'] > 0 ? min(20, $metrics['reorder_count'] * 3) : 0;
        $stockScore -= $metrics['stock_to_sales_ratio'] > 250 ? 15 : 0;

        $decisionScore = 100;
        $decisionScore -= $metrics['review_count'] > 0 ? min(25, $metrics['review_count'] * 2) : 0;
        $decisionScore -= $metrics['discontinue_count'] > 0 ? min(20, $metrics['discontinue_count'] * 2) : 0;
        $decisionScore -= $metrics['total_transaksi'] <= 0 ? 25 : 0;

        $financialScore = $this->clampScore($financialScore);
        $stockScore = $this->clampScore($stockScore);
        $decisionScore = $this->clampScore($decisionScore);
        $overall = (int) round(($financialScore * 0.45) + ($stockScore * 0.40) + ($decisionScore * 0.15));

        return [
            'overall' => $overall,
            'financial' => $financialScore,
            'stock' => $stockScore,
            'decision' => $decisionScore,
            'label' => $this->scoreLabel($overall),
            'color' => $this->scoreColor($overall),
        ];
    }

    private function buildRecommendations(array $metrics, $movementGoods, $receivables, $stockValuation)
    {
        $items = collect();

        if ($metrics['cash_net'] < 0) {
            $items->push($this->recommendation('Keuangan', 'Arus kas negatif', 'Kas keluar lebih besar dari kas masuk pada periode ini.', 'Tunda pembelian non-prioritas dan cek biaya yang bisa ditekan.', 'high'));
        }

        if ($metrics['margin_pct'] < 12 && $metrics['total_omzet'] > 0) {
            $items->push($this->recommendation('Keuangan', 'Margin laba rendah', 'Margin saat ini ' . $metrics['margin_pct'] . '%.', 'Cek barang margin kecil, harga beli terbaru, dan diskon yang terlalu besar.', 'high'));
        }

        if ($metrics['receivable_ratio'] > 20) {
            $items->push($this->recommendation('Piutang', 'Piutang mulai berat', 'Sisa piutang setara ' . $metrics['receivable_ratio'] . '% dari omzet periode.', 'Prioritaskan penagihan member dengan piutang terbesar sebelum memberi kredit baru.', 'medium'));
        }

        if ($metrics['reorder_count'] > 0) {
            $items->push($this->recommendation('Stok', 'Ada barang perlu reorder', $metrics['reorder_count'] . ' barang masuk rekomendasi tambah stok.', 'Dahulukan fast moving dengan days of stock paling pendek.', 'high'));
        }

        if ($metrics['dead_count'] > 0) {
            $items->push($this->recommendation('Stok', 'Dead stock mengikat modal', $metrics['dead_count'] . ' barang tergolong dead stock.', 'Buat paket promo, clearance, retur distributor, atau discontinue bertahap.', 'medium'));
        }

        if ($metrics['stock_to_sales_ratio'] > 250 && $metrics['total_omzet'] > 0) {
            $items->push($this->recommendation('Stok', 'Nilai stok terlalu besar', 'Nilai HPP stok ' . $metrics['stock_to_sales_ratio'] . '% dibanding omzet periode.', 'Kurangi pembelian barang lambat dan alihkan modal ke barang cepat laku.', 'medium'));
        }

        if ($items->isEmpty()) {
            $items->push($this->recommendation('Toko', 'Kondisi relatif sehat', 'Tidak ada sinyal risiko besar pada periode ini.', 'Pertahankan ritme order, margin, dan penagihan.', 'low'));
        }

        return $items->sortByDesc(function ($item) {
            return ['high' => 3, 'medium' => 2, 'low' => 1][$item['priority']] ?? 0;
        })->values();
    }

    private function recommendation($area, $title, $reason, $action, $priority): array
    {
        return compact('area', 'title', 'reason', 'action', 'priority');
    }

    private function classifyMovement(int $totalTrx, int $daysSinceTrx, float $stok): array
    {
        if ($totalTrx >= self::FAST_MOVING_MIN_TRX && $daysSinceTrx <= self::ACTIVE_LAST_TRX_DAYS) {
            return ['status' => 'fast', 'label' => 'Fast Moving', 'color' => 'green'];
        }

        if ($daysSinceTrx >= self::DEAD_STOCK_DAYS || ($totalTrx === 0 && $stok > 0)) {
            return ['status' => 'dead', 'label' => 'Dead Stock', 'color' => 'red'];
        }

        if ($totalTrx === 0 && $stok <= 0) {
            return ['status' => 'dead', 'label' => 'Tidak Aktif', 'color' => 'red'];
        }

        return ['status' => 'slow', 'label' => 'Slow Moving', 'color' => 'orange'];
    }

    private function recommendMovement(array $classification, float $stok, int $daysOfStock, int $totalTrx, float $margin): array
    {
        $status = $classification['status'];

        if ($status === 'dead') {
            if ($stok <= 0) {
                return ['action' => 'discontinue', 'label' => 'Discontinue', 'color' => 'red', 'icon' => 'delete', 'urgency' => 3];
            }
            if ($margin < 5) {
                return ['action' => 'clearance', 'label' => 'Obral / Clearance', 'color' => 'red', 'icon' => 'tag', 'urgency' => 3];
            }
            return ['action' => 'review', 'label' => 'Perlu Review', 'color' => 'orange', 'icon' => 'warning', 'urgency' => 2];
        }

        if ($status === 'slow') {
            if ($stok > 50 && $daysOfStock > 60) {
                return ['action' => 'reduce_order', 'label' => 'Kurangi Order', 'color' => 'orange', 'icon' => 'down', 'urgency' => 2];
            }
            return ['action' => 'monitor', 'label' => 'Monitor', 'color' => 'yellow', 'icon' => 'eye', 'urgency' => 1];
        }

        if ($daysOfStock <= 7) {
            return ['action' => 'reorder_urgent', 'label' => 'Reorder Segera', 'color' => 'red', 'icon' => 'alert', 'urgency' => 3];
        }

        if ($daysOfStock <= 14 || $stok <= self::REORDER_STOCK_MIN) {
            return ['action' => 'reorder', 'label' => 'Tambah Stok', 'color' => 'orange', 'icon' => 'box', 'urgency' => 2];
        }

        return ['action' => 'maintain', 'label' => 'Pertahankan', 'color' => 'green', 'icon' => 'check', 'urgency' => 0];
    }

    private function buildMovementSummary($goods, int $days): array
    {
        $summary = [
            'total' => $goods->count(),
            'fastCount' => 0,
            'slowCount' => 0,
            'deadCount' => 0,
            'totalOmzet' => 0,
            'totalLaba' => 0,
            'totalNilaiStok' => 0,
            'reorderCount' => 0,
            'discontinueCount' => 0,
            'reviewCount' => 0,
            'days' => $days,
        ];

        foreach ($goods as $good) {
            if ($good->status === 'fast') {
                $summary['fastCount']++;
            } elseif ($good->status === 'slow') {
                $summary['slowCount']++;
            } else {
                $summary['deadCount']++;
            }

            $summary['totalOmzet'] += $good->total_omzet;
            $summary['totalLaba'] += $good->total_laba;
            $summary['totalNilaiStok'] += $good->nilai_stok;

            if (in_array($good->recommendation, ['reorder', 'reorder_urgent'])) {
                $summary['reorderCount']++;
            }
            if ($good->recommendation === 'discontinue') {
                $summary['discontinueCount']++;
            }
            if (in_array($good->recommendation, ['review', 'clearance'])) {
                $summary['reviewCount']++;
            }
        }

        return $summary;
    }

    private function clampScore($score): int
    {
        return max(0, min(100, (int) round($score)));
    }

    private function scoreLabel($score): string
    {
        if ($score >= 80) return 'Sehat';
        if ($score >= 60) return 'Perlu Dipantau';
        return 'Perlu Tindakan';
    }

    private function scoreColor($score): string
    {
        if ($score >= 80) return 'green';
        if ($score >= 60) return 'orange';
        return 'red';
    }

    /**
     * Menghasilkan konten CSV untuk data Barang Prioritas.
     */
    public function getCriticalGoodsCsv($startDate, $endDate): array
    {
        $data = $this->getHealthData($startDate, $endDate);
        $goods = $data['critical_goods'];

        $filename = "barang_prioritas_" . Carbon::now()->format('Ymd_His') . ".csv";
        
        $handle = fopen('php://temp', 'w+');
        // Tambahkan BOM untuk kompatibilitas Excel (UTF-8)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Tabel
        fputcsv($handle, [
            'Nama Barang', 'Kategori', 'Merk', 'Status', 'Rekomendasi', 
            'Stok Sekarang', 'Satuan', 'Hari Stok (DoS)', 'Omzet', 'Laba', 'Transaksi'
        ]);

        foreach ($goods as $g) {
            fputcsv($handle, [
                $g->nama,
                $g->kategori,
                $g->merk,
                $g->status_label,
                $g->rec_label,
                $g->stok_sekarang,
                $g->satuan,
                $g->days_of_stock >= 9999 ? 'Aman' : $g->days_of_stock,
                $g->total_omzet,
                $g->total_laba,
                $g->total_transaksi
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return [
            'content'  => $content,
            'filename' => $filename
        ];
    }
}