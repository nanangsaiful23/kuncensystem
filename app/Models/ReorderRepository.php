<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Good;
use App\Models\GoodUnit;
use App\Models\Distributor;
use App\Models\TransactionDetail;

class ReorderRepository
{
    // =========================================================================
    // KONFIGURASI — semua angka asumsi dikumpulkan di sini agar mudah diubah
    // tanpa harus mengubah logika perhitungan.
    // =========================================================================

    /**
     * Lead time default (hari) dari order dikirim sampai barang tiba di toko.
     * Dipakai karena belum ada data lead time riil per distributor.
     * Bisa diganti nanti dengan kolom distributors.lead_time_days tanpa
     * mengubah rumus — cukup ganti sumber nilainya di getLeadTimeDays().
     */
    const DEFAULT_LEAD_TIME_DAYS = 5;

    /**
     * Berapa hari ke depan yang ingin "diamankan" oleh satu kali order.
     * Misal 14 = sekali order, toko ingin cukup stok untuk 2 minggu ke depan
     * (dihitung sejak barang tiba), supaya tidak terlalu sering order kecil-kecil.
     */
    const REVIEW_PERIOD_DAYS = 14;

    /**
     * Faktor pengaman (safety factor) dikali (avg_qty_per_day x lead_time).
     * Lebih besar untuk fast moving karena dampak kehabisan stok lebih terasa
     * (kehilangan penjualan harian yang signifikan), lebih kecil untuk slow
     * moving karena permintaan jarang & risiko stok mati lebih besar daripada
     * risiko kehabisan.
     */
    const SAFETY_FACTOR_FAST = 1.5;
    const SAFETY_FACTOR_SLOW = 1.2;
    const SAFETY_FACTOR_NEW  = 1.0; // barang tanpa riwayat penjualan dalam periode

    /**
     * Berapa hari riwayat penjualan dipakai untuk hitung rata-rata jual/hari.
     * Dibuat sama dengan window analisis GoodMovementRepository agar konsisten,
     * tapi didefinisikan independen supaya halaman ini bisa dipanggil sendiri.
     */
    const DEFAULT_ANALYSIS_DAYS = 90;

    // =========================================================================
    // QUERY UTAMA
    // =========================================================================

    /**
     * Ambil seluruh rekomendasi reorder, dikelompokkan per distributor.
     *
     * @param  string  $startDate     mulai periode analisis penjualan
     * @param  string  $endDate       akhir periode analisis penjualan
     * @param  int|null $distributorId  filter ke 1 distributor saja (opsional)
     * @param  int|null $kategoriId     filter ke 1 kategori saja (opsional)
     * @param  bool     $onlyNeeded     true = hanya tampilkan barang yang memang perlu order
     * @return array{groups: \Illuminate\Support\Collection, summary: array}
     */
    public function getReorderRecommendations(
        string $startDate,
        string $endDate,
        ?int   $distributorId = null,
        ?int   $kategoriId    = null,
        bool   $onlyNeeded    = true
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();
        $days  = $start->diffInDays($end) ?: 1;

        // ── Agregasi penjualan per good dalam periode (sama prinsipnya dengan
        //    GoodMovementRepository, supaya angka "rata-rata jual/hari" konsisten
        //    di seluruh sistem) ──────────────────────────────────────────────
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
                DB::raw('COALESCE(SUM(transaction_details.real_quantity), 0) AS total_qty_terjual'),
                DB::raw('COUNT(DISTINCT transactions.id)                     AS total_transaksi')
            )
            ->groupBy('good_units.good_id')
            ->get()
            ->keyBy('good_id');

        // ── Ambil goods aktif (belum discontinued), beserta base unit & harga,
        //    plus seluruh unit kemasan yang tersedia (untuk pembulatan order) ──
        $baseUnitSub = DB::table('good_units AS gu_base')
            ->join('units AS u_base', 'u_base.id', '=', 'gu_base.unit_id')
            ->whereNull('gu_base.deleted_at')
            ->select(
                'gu_base.good_id',
                DB::raw('MIN(CAST(u_base.quantity AS UNSIGNED)) AS min_qty')
            )
            ->groupBy('gu_base.good_id');

        $goodsQuery = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoinSub($baseUnitSub, 'base_u', 'base_u.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS gu', function ($j) {
                $j->on('gu.good_id', '=', 'goods.id')->whereNull('gu.deleted_at');
            })
            ->leftJoin('units AS u', 'u.id', '=', 'gu.unit_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0)
            ->whereRaw('CAST(u.quantity AS UNSIGNED) = base_u.min_qty OR base_u.min_qty IS NULL')
            ->select(
                'goods.id                  AS good_id',
                'goods.code                AS kode',
                'goods.name                AS nama',
                'goods.last_stock          AS stok_sekarang',
                'goods.last_distributor_id AS distributor_id',
                'categories.id             AS category_id',
                'categories.name           AS kategori',
                'brands.name               AS merk',
                'u.name                    AS satuan',
                'u.quantity                AS satuan_qty',
                'gu.id                     AS good_unit_id',
                'gu.buy_price              AS harga_beli',
                'gu.selling_price          AS harga_jual'
            );

        if ($kategoriId) {
            $goodsQuery->where('categories.id', $kategoriId);
        }
        if ($distributorId) {
            $goodsQuery->where('goods.last_distributor_id', $distributorId);
        }

        $goodsRaw = $goodsQuery
            ->orderBy('goods.id')
            ->orderByRaw('CAST(u.quantity AS UNSIGNED) ASC')
            ->get()
            ->unique('good_id')
            ->keyBy('good_id');

        // ── Ambil semua kemasan (good_units) per good untuk pembulatan order ─
        $allUnitsByGood = DB::table('good_units')
            ->join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereNull('good_units.deleted_at')
            ->select(
                'good_units.good_id',
                'units.name     AS unit_name',
                'units.quantity AS unit_qty',
                'good_units.buy_price'
            )
            ->orderBy('units.quantity', 'desc') // kemasan terbesar dulu
            ->get()
            ->groupBy('good_id');

        // ── Ambil daftar distributor untuk pengelompokan ──────────────────────
        $distributors = Distributor::whereNull('deleted_at')->get()->keyBy('id');

        // ── Hitung rekomendasi per barang ─────────────────────────────────────
        $items = collect();

        foreach ($goodsRaw as $good) {
            $gid       = $good->good_id;
            $agg       = $salesAgg->get($gid);
            $totalTrx  = $agg ? (int)   $agg->total_transaksi    : 0;
            $totalQty  = $agg ? (float) $agg->total_qty_terjual  : 0;
            $stok      = (float) $good->stok_sekarang;
            $hargaBeli = (float) ($good->harga_beli ?? 0);

            $avgQtyPerDay = $days > 0 ? $totalQty / $days : 0;

            $calc = $this->calculate($avgQtyPerDay, $totalTrx, $stok);

            // Lewati barang yang tidak perlu di-order, kecuali diminta tampilkan semua
            if ($onlyNeeded && !$calc['perlu_order']) {
                continue;
            }

            $unitOptions = $allUnitsByGood->get($gid, collect());
            $rounded     = $this->roundToPackaging($calc['reorder_qty'], $unitOptions);

            $distId   = $good->distributor_id;
            $distName = $distId && $distributors->has($distId)
                ? $distributors->get($distId)->name
                : 'Belum Ditentukan';

            $items->push((object) [
                'good_id'          => $gid,
                'kode'             => $good->kode,
                'nama'             => $good->nama,
                'kategori'         => $good->kategori ?? '-',
                'merk'             => $good->merk     ?? '-',
                'satuan'           => $good->satuan   ?? '-',
                'distributor_id'   => $distId,
                'distributor_nama' => $distName,
                'stok_sekarang'    => $stok,
                'avg_qty_per_day'  => round($avgQtyPerDay, 2),
                'total_transaksi'  => $totalTrx,
                'lead_time_days'   => $calc['lead_time_days'],
                'safety_stock'     => $calc['safety_stock'],
                'min_stock'        => $calc['min_stock'],     // reorder point
                'target_stock'     => $calc['target_stock'],
                'reorder_qty_raw'  => $calc['reorder_qty'],
                'reorder_qty'      => $rounded['qty'],
                'reorder_unit'     => $rounded['unit_name'],
                'reorder_paket'    => $rounded['label'],
                'harga_beli'       => $hargaBeli,
                'estimasi_biaya'   => round($rounded['qty'] * $hargaBeli, 0),
                'urgensi'          => $calc['urgensi'],         // 1=segera 2=mendekati 0=aman
                'urgensi_label'    => $calc['urgensi_label'],
                'tipe'             => $calc['tipe'],            // fast/slow/baru
            ]);
        }

        // ── Kelompokkan per distributor ───────────────────────────────────────
        $groups = $items
            ->groupBy('distributor_nama')
            ->map(function ($groupItems, $distName) {
                // Urutkan: urgensi tertinggi dulu (1=segera, 2=mendekati, 0=aman),
                // lalu nama barang A-Z. Pakai sortBy(callback) karena sintaks
                // multi-kolom ala query builder tidak didukung oleh Collection::sortBy().
                $sorted = $groupItems
                    ->sortBy(function ($item) {
                        // urgensi 1 harus tampil paling atas, jadi dibalik urutannya
                        $urgensiRank = $item->urgensi === 1 ? 0 : ($item->urgensi === 2 ? 1 : 2);
                        return sprintf('%d_%s', $urgensiRank, $item->nama);
                    })
                    ->values();

                return (object) [
                    'distributor_nama' => $distName,
                    'distributor_id'   => $sorted->first()->distributor_id ?? null,
                    'items'            => $sorted,
                    'jumlah_item'      => $sorted->count(),
                    'total_biaya'      => $sorted->sum('estimasi_biaya'),
                    'urgent_count'     => $sorted->where('urgensi', 1)->count(),
                ];
            })
            ->sortByDesc('urgent_count')
            ->values();

        $summary = [
            'total_item'        => $items->count(),
            'total_distributor' => $groups->count(),
            'total_biaya'       => $items->sum('estimasi_biaya'),
            'urgent_count'      => $items->where('urgensi', 1)->count(),
            'soon_count'        => $items->where('urgensi', 2)->count(),
            'days'              => $days,
        ];

        return [
            'groups'  => $groups,
            'summary' => $summary,
        ];
    }

    // =========================================================================
    // RUMUS INTI
    // =========================================================================

    /**
     * Hitung safety stock, minimum stock (reorder point), target stock, dan
     * reorder quantity untuk satu barang.
     *
     * Rumus:
     *   lead_time      = asumsi hari pengiriman distributor (default config)
     *   safety_stock   = avg_qty_per_day x lead_time x faktor_buffer
     *   min_stock      = safety_stock + (avg_qty_per_day x lead_time)
     *                     -> titik pemicu order (reorder point)
     *   target_stock   = safety_stock + avg_qty_per_day x (lead_time + review_period)
     *                     -> stok ideal yang ingin dicapai setelah order tiba
     *   reorder_qty    = target_stock - stok_sekarang   (jika stok_sekarang < min_stock)
     */
    private function calculate(float $avgQtyPerDay, int $totalTrx, float $stok): array
    {
        $leadTime = self::DEFAULT_LEAD_TIME_DAYS;

        // Barang tanpa penjualan sama sekali dalam periode dianggap "baru/jarang"
        // -> dipakai faktor buffer paling kecil supaya tidak ikut direkomendasikan
        // order besar-besaran (mencegah over-order untuk barang yang memang sepi).
        if ($totalTrx === 0 || $avgQtyPerDay <= 0) {
            $tipe         = 'baru_atau_tidak_aktif';
            $safetyFactor = self::SAFETY_FACTOR_NEW;
        } elseif ($totalTrx >= 10) {
            $tipe         = 'fast';
            $safetyFactor = self::SAFETY_FACTOR_FAST;
        } else {
            $tipe         = 'slow';
            $safetyFactor = self::SAFETY_FACTOR_SLOW;
        }

        $safetyStock = round($avgQtyPerDay * $leadTime * $safetyFactor, 1);
        $minStock    = round($safetyStock + ($avgQtyPerDay * $leadTime), 1);
        $targetStock = round(
            $safetyStock + $avgQtyPerDay * ($leadTime + self::REVIEW_PERIOD_DAYS),
            1
        );

        $perluOrder = $stok <= $minStock && $avgQtyPerDay > 0;
        $reorderQty = $perluOrder ? max(0, round($targetStock - $stok, 1)) : 0;

        // Urgensi: 1 = segera (stok di bawah safety stock, risiko habis sebelum
        // barang baru tiba), 2 = mendekati (di antara safety stock & min stock),
        // 0 = masih aman / tidak perlu order
        if ($avgQtyPerDay <= 0) {
            $urgensi = 0;
            $label   = 'Tidak Bergerak';
        } elseif ($stok <= $safetyStock) {
            $urgensi = 1;
            $label   = 'Segera Order';
        } elseif ($stok <= $minStock) {
            $urgensi = 2;
            $label   = 'Mendekati Batas';
        } else {
            $urgensi = 0;
            $label   = 'Aman';
        }

        return [
            'lead_time_days' => $leadTime,
            'safety_stock'   => $safetyStock,
            'min_stock'      => $minStock,
            'target_stock'   => $targetStock,
            'reorder_qty'    => $reorderQty,
            'perlu_order'    => $perluOrder,
            'urgensi'        => $urgensi,
            'urgensi_label'  => $label,
            'tipe'           => $tipe,
        ];
    }

    /**
     * Bulatkan reorder_qty ke kelipatan kemasan terbesar yang tersedia,
     * supaya order ke distributor rapi (mis. 2 dus + 5 pcs, bukan "53.4 pcs").
     *
     * $units diurutkan dari kemasan terbesar -> terkecil (sudah di-order DESC
     * di query). Algoritma greedy sederhana: pakai kemasan terbesar dulu,
     * sisanya pakai kemasan lebih kecil, sisa akhir dibulatkan ke atas pada
     * kemasan terkecil supaya jumlah order tidak kurang dari kebutuhan.
     */
    private function roundToPackaging(float $qtyNeeded, $units): array
    {
        if ($qtyNeeded <= 0) {
            return ['qty' => 0, 'unit_name' => '-', 'label' => '-'];
        }

        if ($units->isEmpty()) {
            $rounded = (int) ceil($qtyNeeded);
            return ['qty' => $rounded, 'unit_name' => 'pcs', 'label' => $rounded . ' pcs'];
        }

        $remaining = $qtyNeeded;
        $parts     = [];

        foreach ($units as $u) {
            $unitQty = max(1, (int) $u->unit_qty);
            if ($unitQty <= 1) {
                continue; // satuan dasar disisakan untuk pembulatan akhir
            }
            $count = floor($remaining / $unitQty);
            if ($count > 0) {
                $parts[]   = $count . ' ' . $u->unit_name;
                $remaining -= $count * $unitQty;
            }
        }

        // Sisa dibulatkan ke atas dalam satuan dasar (pcs/terkecil) —
        // pembulatan ke atas memastikan jumlah order tidak kurang dari kebutuhan
        $remainingRounded = (int) ceil($remaining);
        $smallestUnitName = $units->sortBy('unit_qty')->first()->unit_name ?? 'pcs';
        if ($remainingRounded > 0) {
            $parts[] = $remainingRounded . ' ' . $smallestUnitName;
        }

        // Total qty aktual (dalam satuan dasar) setelah pembulatan ke atas —
        // dipakai untuk hitung estimasi biaya & sorting, bisa sedikit lebih
        // besar dari $qtyNeeded karena efek pembulatan kemasan.
        $actualBaseQty = ($qtyNeeded - $remaining) + $remainingRounded;

        return [
            'qty'       => (int) $actualBaseQty,
            'unit_name' => $smallestUnitName,
            'label'     => implode(' + ', $parts) ?: '0 ' . $smallestUnitName,
        ];
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    public function getDistributors(): \Illuminate\Support\Collection
    {
        return Distributor::whereNull('deleted_at')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();
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