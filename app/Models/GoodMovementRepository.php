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
    // KONFIGURASI THRESHOLD — sesuaikan dengan kebutuhan bisnis
    // =========================================================================

    // Jumlah minimum transaksi dalam periode untuk dianggap "fast moving"
    const FAST_MOVING_MIN_TRX    = 10;
    // Jumlah maksimum transaksi dalam periode untuk dianggap "slow moving"
    const SLOW_MOVING_MAX_TRX    = 3;
    // Hari maksimum sejak transaksi terakhir untuk dianggap masih aktif
    const ACTIVE_LAST_TRX_DAYS   = 30;
    // Hari tanpa transaksi untuk dianggap "dead stock"
    const DEAD_STOCK_DAYS        = 90;
    // Stok minimum sebelum dianggap perlu reorder
    const REORDER_STOCK_MIN      = 10;

    /**
     * Query utama: agregasi data pergerakan per barang dalam periode.
     *
     * Data yang dikumpulkan per good_id:
     *  - total_qty_terjual  : SUM(real_quantity) semua unit (sudah dalam satuan terkecil)
     *  - total_transaksi    : berapa kali muncul di transaksi
     *  - total_omzet        : total pendapatan dari barang ini
     *  - total_laba         : laba kotor
     *  - last_transaction   : tanggal transaksi terakhir
     *  - last_loading       : tanggal loading terakhir dari goods.last_loading
     *  - stok_sekarang      : goods.last_stock (satuan terkecil)
     *
     * Kemudian diklasifikasi & diperkaya data master (nama, kategori, dsb).
     */
    public function getMovementData(
        string $startDate,
        string $endDate,
        ?string $kategori   = null,
        ?string $status     = null,   // fast|slow|dead|all
        string  $sortBy     = 'total_omzet',
        string  $sortDir    = 'desc'
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        $days  = $start->diffInDays($end) ?: 1;

        // ── TAHAP 1: Agregasi transaksi per good_id dalam periode ─────────────
        $salesAgg = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->select(
                'good_units.good_id',
                // real_quantity sudah dalam satuan terkecil
                DB::raw('COALESCE(SUM(transaction_details.real_quantity), 0)              AS total_qty_terjual'),
                DB::raw('COUNT(DISTINCT transactions.id)                                  AS total_transaksi'),
                DB::raw('COALESCE(SUM(transaction_details.sum_price), 0)                  AS total_omzet'),
                DB::raw('COALESCE(SUM(
                            (transaction_details.selling_price - transaction_details.buy_price)
                            * transaction_details.quantity
                         ), 0)                                                             AS total_laba'),
                DB::raw('MAX(transactions.created_at)                                     AS last_transaction_at')
            )
            ->groupBy('good_units.good_id')
            ->get()
            ->keyBy('good_id');

        // ── TAHAP 2: Ambil semua goods dengan master data ─────────────────────
        // Subquery satuan terkecil (sama dengan getPcsSellingPrice)
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
            // JOIN good_units + units untuk dapat harga & satuan terkecil
            ->leftJoin('good_units AS gu', function ($j) {
                $j->on('gu.good_id', '=', 'goods.id')
                  ->whereNull('gu.deleted_at');
            })
            ->leftJoin('units AS u', function ($j) {
                $j->on('u.id', '=', 'gu.unit_id');
            })
            ->whereNull('goods.deleted_at')
            ->whereRaw('CAST(u.quantity AS UNSIGNED) = base_u.min_qty OR base_u.min_qty IS NULL')
            ->select(
                'goods.id             AS good_id',
                'goods.code           AS kode',
                'goods.name           AS nama',
                'goods.last_stock     AS stok_sekarang',
                'goods.last_transaction AS last_transaction_goods',
                'goods.last_loading   AS last_loading_goods',
                'goods.total_transaction AS total_transaction_all',
                'categories.id        AS category_id',
                'categories.name      AS kategori',
                'brands.name          AS merk',
                'u.name               AS satuan',
                'gu.buy_price         AS harga_beli',
                'gu.selling_price     AS harga_jual'
            );

        if ($kategori) {
            $goodsQuery->where('categories.id', $kategori);
        }

        // Ambil 1 baris per good (karena join bisa duplikat jika ada banyak unit qty sama)
        $goodsRaw = $goodsQuery
            ->orderBy('goods.id')
            ->orderByRaw('CAST(u.quantity AS UNSIGNED) ASC')
            ->get()
            ->unique('good_id')
            ->keyBy('good_id');

        // ── TAHAP 3: Gabungkan & klasifikasikan ───────────────────────────────
        $result = [];

        foreach ($goodsRaw as $good) {
            $gid  = $good->good_id;
            $agg  = $salesAgg->get($gid);

            $totalTrx     = $agg ? (int)   $agg->total_transaksi   : 0;
            $totalQty     = $agg ? (float) $agg->total_qty_terjual  : 0;
            $totalOmzet   = $agg ? (float) $agg->total_omzet        : 0;
            $totalLaba    = $agg ? (float) $agg->total_laba         : 0;
            $lastTrxAt    = $agg ? $agg->last_transaction_at        : $good->last_transaction_goods;
            $stok         = (float) $good->stok_sekarang;
            $hargaBeli    = (float) ($good->harga_beli ?? 0);
            $hargaJual    = (float) ($good->harga_jual ?? 0);

            // Hari sejak transaksi terakhir
            $daysSinceTrx = $lastTrxAt
                ? (int) Carbon::parse($lastTrxAt)->diffInDays(Carbon::now())
                : 999;

            // Rata-rata penjualan per hari (dalam satuan terkecil)
            $avgQtyPerDay = $days > 0 ? round($totalQty / $days, 2) : 0;
            // Estimasi hari stok habis (Days of Stock)
            $daysOfStock  = ($avgQtyPerDay > 0 && $stok > 0)
                ? (int) round($stok / $avgQtyPerDay)
                : ($stok > 0 ? 9999 : 0);

            // Nilai stok saat ini
            $nilaiStok    = $stok * $hargaBeli;

            // Margin %
            $margin = $hargaJual > 0 ? round(($hargaJual - $hargaBeli) / $hargaJual * 100, 1) : 0;

            // ── Klasifikasi ───────────────────────────────────────────────────
            $classification = $this->classify($totalTrx, $daysSinceTrx, $stok);
            $recommendation = $this->recommend($classification, $stok, $daysOfStock, $totalTrx, $margin);

            // Filter by status jika ada
            if ($status && $status !== 'all' && $classification['status'] !== $status) {
                continue;
            }

            $result[] = (object) [
                'good_id'          => $gid,
                'kode'             => $good->kode,
                'nama'             => $good->nama,
                'kategori'         => $good->kategori         ?? '-',
                'category_id'      => $good->category_id,
                'merk'             => $good->merk             ?? '-',
                'satuan'           => $good->satuan           ?? '-',
                'stok_sekarang'    => $stok,
                'harga_beli'       => $hargaBeli,
                'harga_jual'       => $hargaJual,
                'nilai_stok'       => $nilaiStok,
                'margin_pct'       => $margin,

                // Performa periode
                'total_transaksi'  => $totalTrx,
                'total_qty'        => $totalQty,
                'total_omzet'      => $totalOmzet,
                'total_laba'       => $totalLaba,
                'avg_qty_per_day'  => $avgQtyPerDay,
                'days_of_stock'    => $daysOfStock,
                'days_since_trx'   => $daysSinceTrx,
                'last_trx_at'      => $lastTrxAt,

                // Klasifikasi & rekomendasi
                'status'           => $classification['status'],
                'status_label'     => $classification['label'],
                'status_color'     => $classification['color'],
                'recommendation'   => $recommendation['action'],
                'rec_label'        => $recommendation['label'],
                'rec_color'        => $recommendation['color'],
                'rec_icon'         => $recommendation['icon'],
                'urgency'          => $recommendation['urgency'],
            ];
        }

        // Sort
        usort($result, function ($a, $b) use ($sortBy, $sortDir) {
            $va = $a->{$sortBy} ?? 0;
            $vb = $b->{$sortBy} ?? 0;
            return $sortDir === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });

        // ── TAHAP 4: Hitung ringkasan statistik ───────────────────────────────
        $summary = $this->buildSummary($result, $days);

        return [
            'goods'   => collect($result),
            'summary' => $summary,
            'days'    => $days,
        ];
    }

    /**
     * Klasifikasi barang berdasarkan frekuensi & recency transaksi.
     *
     * STATUS:
     *  fast  → terjual banyak & baru-baru ini
     *  slow  → terjual sedikit tapi masih ada pergerakan
     *  dead  → tidak ada transaksi lama / tidak pernah terjual di periode ini
     */
    private function classify(int $totalTrx, int $daysSinceTrx, float $stok): array
    {
        if ($totalTrx >= self::FAST_MOVING_MIN_TRX && $daysSinceTrx <= self::ACTIVE_LAST_TRX_DAYS) {
            return ['status' => 'fast', 'label' => 'Fast Moving',  'color' => 'green'];
        }

        if ($daysSinceTrx >= self::DEAD_STOCK_DAYS || ($totalTrx === 0 && $stok > 0)) {
            return ['status' => 'dead', 'label' => 'Dead Stock',   'color' => 'red'];
        }

        if ($totalTrx === 0 && $stok <= 0) {
            return ['status' => 'dead', 'label' => 'Tidak Aktif',  'color' => 'red'];
        }

        return ['status' => 'slow', 'label' => 'Slow Moving', 'color' => 'orange'];
    }

    /**
     * Rekomendasi tindakan berdasarkan klasifikasi & kondisi stok.
     */
    private function recommend(
        array $classification,
        float $stok,
        int   $daysOfStock,
        int   $totalTrx,
        float $margin
    ): array {
        $status = $classification['status'];

        // ── Dead stock ─────────────────────────────────────────────────────
        if ($status === 'dead') {
            if ($stok <= 0) {
                return [
                    'action'  => 'discontinue',
                    'label'   => 'Discontinue',
                    'color'   => 'red',
                    'icon'    => '🗑️',
                    'urgency' => 3,
                ];
            }
            if ($margin < 5) {
                return [
                    'action'  => 'clearance',
                    'label'   => 'Obral / Clearance',
                    'color'   => 'red',
                    'icon'    => '🏷️',
                    'urgency' => 3,
                ];
            }
            return [
                'action'  => 'review',
                'label'   => 'Perlu Review',
                'color'   => 'orange',
                'icon'    => '⚠️',
                'urgency' => 2,
            ];
        }

        // ── Slow moving ────────────────────────────────────────────────────
        if ($status === 'slow') {
            if ($stok > 50 && $daysOfStock > 60) {
                return [
                    'action'  => 'reduce_order',
                    'label'   => 'Kurangi Order',
                    'color'   => 'orange',
                    'icon'    => '📉',
                    'urgency' => 2,
                ];
            }
            return [
                'action'  => 'monitor',
                'label'   => 'Monitor',
                'color'   => 'yellow',
                'icon'    => '👁️',
                'urgency' => 1,
            ];
        }

        // ── Fast moving ────────────────────────────────────────────────────
        if ($daysOfStock <= 7) {
            return [
                'action'  => 'reorder_urgent',
                'label'   => 'Reorder Segera!',
                'color'   => 'red',
                'icon'    => '🚨',
                'urgency' => 3,
            ];
        }
        if ($daysOfStock <= 14) {
            return [
                'action'  => 'reorder',
                'label'   => 'Tambah Stok',
                'color'   => 'orange',
                'icon'    => '📦',
                'urgency' => 2,
            ];
        }
        if ($stok <= self::REORDER_STOCK_MIN) {
            return [
                'action'  => 'reorder',
                'label'   => 'Tambah Stok',
                'color'   => 'orange',
                'icon'    => '📦',
                'urgency' => 2,
            ];
        }

        return [
            'action'  => 'maintain',
            'label'   => 'Pertahankan',
            'color'   => 'green',
            'icon'    => '✅',
            'urgency' => 0,
        ];
    }

    /**
     * Hitung KPI ringkasan dari semua data yang sudah difilter.
     */
    private function buildSummary(array $goods, int $days): array
    {
        $total      = count($goods);
        $fastCount  = 0; $slowCount  = 0; $deadCount  = 0;
        $totalOmzet = 0; $totalLaba  = 0; $totalNilaiStok = 0;
        $reorderCount = 0; $discontinueCount = 0; $reviewCount = 0;

        foreach ($goods as $g) {
            if ($g->status === 'fast') $fastCount++;
            elseif ($g->status === 'slow') $slowCount++;
            else $deadCount++;

            $totalOmzet     += $g->total_omzet;
            $totalLaba      += $g->total_laba;
            $totalNilaiStok += $g->nilai_stok;

            if (in_array($g->recommendation, ['reorder', 'reorder_urgent'])) $reorderCount++;
            if ($g->recommendation === 'discontinue') $discontinueCount++;
            if (in_array($g->recommendation, ['review', 'clearance'])) $reviewCount++;
        }

        return compact(
            'total', 'fastCount', 'slowCount', 'deadCount',
            'totalOmzet', 'totalLaba', 'totalNilaiStok',
            'reorderCount', 'discontinueCount', 'reviewCount', 'days'
        );
    }

    /**
     * Ambil daftar kategori untuk dropdown filter.
     */
    public function getCategories(): \Illuminate\Support\Collection
    {
        return DB::table('categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
    }
}