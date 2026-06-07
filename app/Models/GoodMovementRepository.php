<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Good;
use App\Models\GoodUnit;
use App\Models\TransactionDetail;
use App\Models\Transaction;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;

class GoodMovementRepository
{
    // =========================================================================
    // KONFIGURASI THRESHOLD
    // =========================================================================
    const FAST_MOVING_MIN_TRX  = 10;
    const SLOW_MOVING_MAX_TRX  = 3;
    const ACTIVE_LAST_TRX_DAYS = 30;
    const DEAD_STOCK_DAYS      = 90;
    const REORDER_STOCK_MIN    = 10;

    // =========================================================================
    // QUERY UTAMA
    // =========================================================================

    /**
     * Ambil data pergerakan barang dalam periode.
     *
     * Status filter yang tersedia: fast | slow | dead | discontinued | all
     *
     * Barang dengan is_discontinued=1 HANYA muncul saat status='discontinued'.
     * Di semua status lain mereka dikecualikan sepenuhnya.
     */
    public function getMovementData(
        string  $startDate,
        string  $endDate,
        ?string $kategori  = null,
        ?string $status    = null,
        string  $sortBy    = 'total_omzet',
        string  $sortDir   = 'desc'
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        $days  = $start->diffInDays($end) ?: 1;

        // ── Jika filter = discontinued, tampilkan khusus barang discontinued ──
        if ($status === 'discontinued') {
            return $this->getDiscontinuedData($kategori, $sortBy, $sortDir);
        }

        // ── TAHAP 1: Agregasi transaksi per good_id dalam periode ─────────────
        // Hanya type='normal' — retur, void, dan tipe lain DIKECUALIKAN
        $salesAgg = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('transactions.type', 'normal')          // ← hanya transaksi normal
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->select(
                'good_units.good_id',
                DB::raw('COALESCE(SUM(transaction_details.real_quantity), 0)   AS total_qty_terjual'),
                DB::raw('COUNT(DISTINCT transactions.id)                        AS total_transaksi'),
                DB::raw('COALESCE(SUM(transaction_details.sum_price), 0)        AS total_omzet'),
                DB::raw('COALESCE(SUM(
                            (transaction_details.selling_price - transaction_details.buy_price)
                            * transaction_details.quantity
                         ), 0)                                                  AS total_laba'),
                DB::raw('MAX(transactions.created_at)                           AS last_transaction_at')
            )
            ->groupBy('good_units.good_id')
            ->get()
            ->keyBy('good_id');

        // ── TAHAP 1b: Last normal transaction per good (ALL TIME) ────────────
        // Digunakan sebagai fallback daysSinceTrx untuk barang yang tidak ada
        // di periode filter. Tidak menggunakan goods.last_transaction karena
        // field itu diisi dari SEMUA tipe (termasuk retur) sehingga tidak akurat.
        $lastNormalTrx = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('transactions.type', 'normal')          // ← hanya transaksi normal
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                'good_units.good_id',
                DB::raw('MAX(transactions.created_at) AS last_normal_at')
            )
            ->groupBy('good_units.good_id')
            ->get()
            ->keyBy('good_id');

        // ── TAHAP 2: Ambil goods — KECUALIKAN yang sudah discontinued ─────────
        $baseUnitSub = DB::table('good_units AS gu_base')
            ->join('units AS u_base', 'u_base.id', '=', 'gu_base.unit_id')
            ->whereNull('gu_base.deleted_at')
            ->select(
                'gu_base.good_id',
                DB::raw('MIN(CAST(u_base.quantity AS UNSIGNED)) AS min_qty')
            )
            ->groupBy('gu_base.good_id');

        $goodsQuery = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->leftJoinSub($baseUnitSub, 'base_u', 'base_u.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS gu', function ($j) {
                $j->on('gu.good_id', '=', 'goods.id')->whereNull('gu.deleted_at');
            })
            ->leftJoin('units AS u', 'u.id', '=', 'gu.unit_id')
            ->whereNull('goods.deleted_at')
            // ✅ KUNCI: Hanya barang yang BELUM discontinued
            ->where('goods.is_discontinued', 0)
            ->whereRaw('CAST(u.quantity AS UNSIGNED) = base_u.min_qty OR base_u.min_qty IS NULL')
            ->select(
                'goods.id                AS good_id',
                'goods.code              AS kode',
                'goods.name              AS nama',
                'goods.last_stock        AS stok_sekarang',
                'goods.last_transaction  AS last_transaction_goods',
                'goods.last_loading      AS last_loading_goods',
                'goods.total_transaction AS total_transaction_all',
                'categories.id           AS category_id',
                'categories.name         AS kategori',
                'brands.name             AS merk',
                'u.name                  AS satuan',
                'gu.buy_price            AS harga_beli',
                'gu.selling_price        AS harga_jual'
            );

        if ($kategori) {
            $goodsQuery->where('categories.id', $kategori);
        }

        $goodsRaw = $goodsQuery
            ->orderBy('goods.id')
            ->orderByRaw('CAST(u.quantity AS UNSIGNED) ASC')
            ->get()
            ->unique('good_id')
            ->keyBy('good_id');

        // ── TAHAP 3: Gabungkan & klasifikasi ──────────────────────────────────
        $result = [];

        foreach ($goodsRaw as $good) {
            $gid        = $good->good_id;
            $agg        = $salesAgg->get($gid);
            $totalTrx   = $agg ? (int)   $agg->total_transaksi   : 0;
            $totalQty   = $agg ? (float) $agg->total_qty_terjual  : 0;
            $totalOmzet = $agg ? (float) $agg->total_omzet        : 0;
            $totalLaba  = $agg ? (float) $agg->total_laba         : 0;

            // Fallback: last normal transaction all-time (bukan goods.last_transaction
            // yang bisa terisi dari tipe retur/void)
            $lastNormal = $lastNormalTrx->get($gid);
            $lastTrxAt  = $agg
                ? $agg->last_transaction_at
                : ($lastNormal ? $lastNormal->last_normal_at : null);
            $stok       = (float) $good->stok_sekarang;
            $hargaBeli  = (float) ($good->harga_beli  ?? 0);
            $hargaJual  = (float) ($good->harga_jual  ?? 0);

            $daysSinceTrx = $lastTrxAt
                ? (int) Carbon::parse($lastTrxAt)->diffInDays(Carbon::now())
                : 999;

            $avgQtyPerDay = $days > 0 ? round($totalQty / $days, 2) : 0;
            $daysOfStock  = ($avgQtyPerDay > 0 && $stok > 0)
                ? (int) round($stok / $avgQtyPerDay)
                : ($stok > 0 ? 9999 : 0);

            $nilaiStok = $stok * $hargaBeli;
            $margin    = $hargaJual > 0
                ? round(($hargaJual - $hargaBeli) / $hargaJual * 100, 1)
                : 0;

            $classification = $this->classify($totalTrx, $daysSinceTrx, $stok);
            $recommendation = $this->recommend($classification, $stok, $daysOfStock, $totalTrx, $margin);

            if ($status && $status !== 'all' && $classification['status'] !== $status) {
                continue;
            }

            $result[] = (object) [
                'good_id'          => $gid,
                'kode'             => $good->kode,
                'nama'             => $good->nama,
                'kategori'         => $good->kategori        ?? '-',
                'category_id'      => $good->category_id,
                'merk'             => $good->merk            ?? '-',
                'satuan'           => $good->satuan          ?? '-',
                'stok_sekarang'    => $stok,
                'harga_beli'       => $hargaBeli,
                'harga_jual'       => $hargaJual,
                'nilai_stok'       => $nilaiStok,
                'margin_pct'       => $margin,
                'total_transaksi'  => $totalTrx,
                'total_qty'        => $totalQty,
                'total_omzet'      => $totalOmzet,
                'total_laba'       => $totalLaba,
                'avg_qty_per_day'  => $avgQtyPerDay,
                'days_of_stock'    => $daysOfStock,
                'days_since_trx'   => $daysSinceTrx,
                'last_trx_at'      => $lastTrxAt,
                'status'           => $classification['status'],
                'status_label'     => $classification['label'],
                'status_color'     => $classification['color'],
                'recommendation'   => $recommendation['action'],
                'rec_label'        => $recommendation['label'],
                'rec_color'        => $recommendation['color'],
                'rec_icon'         => $recommendation['icon'],
                'urgency'          => $recommendation['urgency'],
                // Flag discontinued selalu false di sini (sudah difilter)
                'is_discontinued'  => false,
            ];
        }

        usort($result, function ($a, $b) use ($sortBy, $sortDir) {
            $va = $a->{$sortBy} ?? 0;
            $vb = $b->{$sortBy} ?? 0;
            return $sortDir === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });

        $summary = $this->buildSummary($result, $days);

        return [
            'goods'   => collect($result),
            'summary' => $summary,
            'days'    => $days,
        ];
    }

    /**
     * Ambil daftar barang yang sudah di-discontinue.
     * Ditampilkan terpisah dengan info kapan & oleh siapa.
     */
    public function getDiscontinuedData(?string $kategori = null, string $sortBy = 'discontinued_at', string $sortDir = 'desc'): array
    {
        $query = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->leftJoin('users',      'users.id',      '=', 'goods.discontinued_by')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 1)
            ->select(
                'goods.id                 AS good_id',
                'goods.code               AS kode',
                'goods.name               AS nama',
                'goods.last_stock         AS stok_sekarang',
                'goods.discontinued_at    AS discontinued_at',
                'goods.discontinued_reason AS discontinued_reason',
                'categories.id            AS category_id',
                'categories.name          AS kategori',
                'brands.name              AS merk',
                'users.name               AS discontinued_by_name'
            );

        if ($kategori) {
            $query->where('categories.id', $kategori);
        }

        $allowedSort = ['discontinued_at', 'nama', 'kategori', 'stok_sekarang'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'discontinued_at';
        }

        $goods = $query->orderBy($sortBy === 'discontinued_at' ? 'goods.discontinued_at' : $sortBy, $sortDir)
            ->get()
            ->map(function ($g) {
                return (object) [
                    'good_id'            => $g->good_id,
                    'kode'               => $g->kode,
                    'nama'               => $g->nama,
                    'kategori'           => $g->kategori           ?? '-',
                    'merk'               => $g->merk               ?? '-',
                    'stok_sekarang'      => (float) $g->stok_sekarang,
                    'discontinued_at'    => $g->discontinued_at,
                    'discontinued_reason'=> $g->discontinued_reason ?? '-',
                    'discontinued_by'    => $g->discontinued_by_name ?? '-',
                    'is_discontinued'    => true,
                    // Field dummy agar blade tidak error
                    'status'             => 'discontinued',
                    'status_label'       => 'Discontinued',
                    'status_color'       => 'dead',
                    'recommendation'     => 'discontinue',
                    'rec_label'          => 'Discontinued',
                    'rec_icon'           => '🗑️',
                    'urgency'            => 0,
                    'total_transaksi'    => 0,
                    'total_qty'          => 0,
                    'total_omzet'        => 0,
                    'total_laba'         => 0,
                    'avg_qty_per_day'    => 0,
                    'days_of_stock'      => 0,
                    'days_since_trx'     => 999,
                    'nilai_stok'         => 0,
                    'margin_pct'         => 0,
                    'harga_beli'         => 0,
                    'harga_jual'         => 0,
                    'satuan'             => '-',
                ];
            });

        $summary = [
            'total'            => $goods->count(),
            'fastCount'        => 0,
            'slowCount'        => 0,
            'deadCount'        => 0,
            'discontinuedCount'=> $goods->count(),
            'totalOmzet'       => 0,
            'totalLaba'        => 0,
            'totalNilaiStok'   => 0,
            'reorderCount'     => 0,
            'discontinueCount' => $goods->count(),
            'reviewCount'      => 0,
            'days'             => 0,
        ];

        return [
            'goods'   => $goods,
            'summary' => $summary,
            'days'    => 0,
        ];
    }

    // =========================================================================
    // AKSI DISCONTINUE
    // =========================================================================

    /**
     * Tandai barang sebagai discontinued.
     * Barang ini tidak akan muncul lagi di laporan movement.
     *
     * @param  int    $goodId
     * @param  string $reason   Alasan discontinue dari user
     * @param  int    $userId   ID user yang melakukan aksi
     * @return bool
     */
    public function markDiscontinued(int $goodId, string $reason, int $userId): bool
    {
        return (bool) Good::where('id', $goodId)
            ->whereNull('deleted_at')
            ->update([
                'is_discontinued'     => 1,
                'discontinued_at'     => Carbon::now(),
                'discontinued_reason' => trim($reason),
                'discontinued_by'     => $userId,
            ]);
    }

    /**
     * Batalkan status discontinued — barang aktif kembali.
     *
     * @param  int $goodId
     * @return bool
     */
    public function restoreDiscontinued(int $goodId): bool
    {
        return (bool) Good::where('id', $goodId)
            ->where('is_discontinued', 1)
            ->update([
                'is_discontinued'     => 0,
                'discontinued_at'     => null,
                'discontinued_reason' => null,
                'discontinued_by'     => null,
            ]);
    }

    /**
     * Hitung jumlah barang discontinued (untuk badge nav/summary).
     */
    public function countDiscontinued(): int
    {
        return Good::whereNull('deleted_at')->where('is_discontinued', 1)->count();
    }

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    private function classify(int $totalTrx, int $daysSinceTrx, float $stok): array
    {
        if ($totalTrx >= self::FAST_MOVING_MIN_TRX && $daysSinceTrx <= self::ACTIVE_LAST_TRX_DAYS) {
            return ['status' => 'fast', 'label' => 'Fast Moving', 'color' => 'green'];
        }
        if ($daysSinceTrx >= self::DEAD_STOCK_DAYS || ($totalTrx === 0 && $stok > 0)) {
            return ['status' => 'dead', 'label' => 'Dead Stock',  'color' => 'red'];
        }
        if ($totalTrx === 0 && $stok <= 0) {
            return ['status' => 'dead', 'label' => 'Tidak Aktif', 'color' => 'red'];
        }
        return ['status' => 'slow', 'label' => 'Slow Moving', 'color' => 'orange'];
    }

    private function recommend(array $classification, float $stok, int $daysOfStock, int $totalTrx, float $margin): array
    {
        $status = $classification['status'];

        if ($status === 'dead') {
            if ($stok <= 0) {
                return ['action' => 'discontinue',   'label' => 'Discontinue',      'color' => 'red',    'icon' => '🗑️', 'urgency' => 3];
            }
            if ($margin < 5) {
                return ['action' => 'clearance',     'label' => 'Obral / Clearance', 'color' => 'red',    'icon' => '🏷️', 'urgency' => 3];
            }
            return     ['action' => 'review',        'label' => 'Perlu Review',      'color' => 'orange', 'icon' => '⚠️', 'urgency' => 2];
        }

        if ($status === 'slow') {
            if ($stok > 50 && $daysOfStock > 60) {
                return ['action' => 'reduce_order',  'label' => 'Kurangi Order',     'color' => 'orange', 'icon' => '📉', 'urgency' => 2];
            }
            return     ['action' => 'monitor',       'label' => 'Monitor',           'color' => 'yellow', 'icon' => '👁️', 'urgency' => 1];
        }

        // fast moving
        if ($daysOfStock <= 7)  return ['action' => 'reorder_urgent', 'label' => 'Reorder Segera!', 'color' => 'red',    'icon' => '🚨', 'urgency' => 3];
        if ($daysOfStock <= 14) return ['action' => 'reorder',        'label' => 'Tambah Stok',     'color' => 'orange', 'icon' => '📦', 'urgency' => 2];
        if ($stok <= self::REORDER_STOCK_MIN) {
                                return ['action' => 'reorder',        'label' => 'Tambah Stok',     'color' => 'orange', 'icon' => '📦', 'urgency' => 2];
        }
        return                         ['action' => 'maintain',       'label' => 'Pertahankan',     'color' => 'green',  'icon' => '✅', 'urgency' => 0];
    }

    private function buildSummary(array $goods, int $days): array
    {
        $total = count($goods);
        $fastCount = $slowCount = $deadCount = 0;
        $totalOmzet = $totalLaba = $totalNilaiStok = 0;
        $reorderCount = $discontinueCount = $reviewCount = 0;

        foreach ($goods as $g) {
            if ($g->status === 'fast')      $fastCount++;
            elseif ($g->status === 'slow')  $slowCount++;
            else                            $deadCount++;

            $totalOmzet     += $g->total_omzet;
            $totalLaba      += $g->total_laba;
            $totalNilaiStok += $g->nilai_stok;

            if (in_array($g->recommendation, ['reorder', 'reorder_urgent'])) $reorderCount++;
            if ($g->recommendation === 'discontinue')                         $discontinueCount++;
            if (in_array($g->recommendation, ['review', 'clearance']))        $reviewCount++;
        }

        return compact(
            'total', 'fastCount', 'slowCount', 'deadCount',
            'totalOmzet', 'totalLaba', 'totalNilaiStok',
            'reorderCount', 'discontinueCount', 'reviewCount', 'days'
        );
    }

    public function getCategories(): \Illuminate\Support\Collection
    {
        return DB::table('categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
    }
}