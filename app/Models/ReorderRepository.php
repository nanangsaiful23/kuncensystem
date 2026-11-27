<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Good;
use App\Models\GoodUnit;
use App\Models\Distributor;
use App\Models\TransactionDetail;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Services\StockOutflow;

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

    /**
     * Batas "habis bukan karena laku". Jika dalam periode analisis porsi stok
     * yang keluar karena expired + rusak + hilang + retur ke distributor
     * mencapai angka ini (0.5 = separuh) dari seluruh stok keluar
     * (terjual + bukan-laku), barang TIDAK direkomendasikan order walaupun
     * stoknya kosong — ditandai "Tinjau Dulu".
     */
    const NON_SALE_DOMINANT_SHARE = 0.5;

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
        // Jumlah hari inklusif. Carbon 3 mengembalikan pecahan (90.99999...).
        $days  = max(1, (int) round($start->diffInDays($end)));
        $now   = Carbon::now();

        $movement     = app(GoodMovementRepository::class);
        $adaKolomBaru = Season::tabelAda();
        $minFast      = $movement->minFastTrx($days);

        // ── Agregasi penjualan per good dalam periode — query yang SAMA dengan
        //    menu Perputaran Barang (transaksi normal + retur, baris retur tidak
        //    dihitung), supaya angka "rata-rata jual/hari" konsisten. Dulu di sini
        //    hanya transaksi 'normal' dan baris retur ikut terhitung.
        $salesAgg = $movement->salesPerGood($start, $end)->toBase()->get()->keyBy('good_id');

        // ── Musim: status hari ini & penjualan musim lalu (acuan stok musim) ──
        $musim         = $adaKolomBaru ? Season::statusSemua($now) : collect();
        $musimLaluSold = [];
        foreach ($musim as $sid => $m) {
            if ($m['status'] === Season::PERSIAPAN && $m['lalu']) {
                $musimLaluSold[$sid] = $movement->salesPerGood($m['lalu'][0], $m['lalu'][1])->toBase()->get()->keyBy('good_id');
            }
        }

        // ── Stok keluar per penyebab dalam periode (terjual / retur ke
        //    distributor / expired / rusak / hilang / lainnya) ────────────────
        //
        //    `goods.last_stock` turun untuk SEMUA penyebab, bukan hanya karena
        //    laku. Kalau hanya melihat "stok sudah kosong", barang yang habis
        //    karena kadaluarsa, hilang, atau dikembalikan ke distributor ikut
        //    direkomendasikan — padahal itu tanda barangnya kurang laku atau
        //    bermasalah, bukan tanda perlu ditambah.
        //
        //    Rincian ini dipakai untuk menentukan barang habis KARENA APA
        //    (lihat NON_SALE_DOMINANT_SHARE).
        $outflowAgg = StockOutflow::byGood(null, $start, $end);

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
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoinSub($baseUnitSub, 'base_u', 'base_u.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS gu', function ($j) {
                $j->on('gu.good_id', '=', 'goods.id')->whereNull('gu.deleted_at');
            })
            ->leftJoin('units AS u', 'u.id', '=', 'gu.unit_id')
            // Satuan dasar barang (satuan goods.last_stock), untuk konversi stok.
            ->leftJoin('good_units AS gub', 'gub.id', '=', 'goods.base_unit_id')
            ->leftJoin('units AS ub', 'ub.id', '=', 'gub.unit_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0)
            // Dalam kurung: tanpa kurung, "OR" ini meloloskan barang terhapus /
            // discontinued yang tidak punya satuan.
            ->whereRaw('(CAST(u.quantity AS UNSIGNED) = base_u.min_qty OR base_u.min_qty IS NULL)')
            ->select(
                'goods.id                  AS good_id',
                'goods.code                AS kode',
                'goods.name                AS nama',
                'types.name                AS tipe',
                'goods.last_stock          AS stok_sekarang',
                'goods.last_distributor_id AS distributor_id',
                'categories.id             AS category_id',
                'categories.name           AS kategori',
                'brands.name               AS merk',
                'u.name                    AS satuan',
                'u.quantity                AS satuan_qty',
                'gu.id                     AS good_unit_id',
                'gu.buy_price              AS harga_beli',
                'gu.selling_price          AS harga_jual',
                'ub.quantity               AS base_qty'
            );

        if ($adaKolomBaru) {
            // Barang obral tidak pernah disarankan order.
            $goodsQuery->where('goods.is_clearance', 0)
                ->addSelect('goods.season_id AS season_good', 'types.season_id AS season_type');
        }

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

        // ── Ambil data loading (restock) TERAKHIR per barang, sebagai
        //    pembanding "biasanya order berapa" saat user menentukan qty
        //    order kali ini. Diambil dari good_loading_details, dicari yang
        //    loading_date paling baru untuk masing-masing good_id. ──────────
        $lastLoadingRaw = DB::table('good_loading_details AS gld')
            ->join('good_loadings AS gl', 'gl.id', '=', 'gld.good_loading_id')
            ->join('good_units AS gu',    'gu.id', '=', 'gld.good_unit_id')
            ->join('units AS u',          'u.id',  '=', 'gu.unit_id')
            ->whereNull('gld.deleted_at')
            ->whereNull('gl.deleted_at')
            // STRAIGHT_JOIN: paksa MySQL mulai dari good_loading_details sesuai
            // urutan join di atas. Tanpa ini optimizer memilih mulai dari tabel
            // units dan query butuh ±15 detik (vs ±0,5 detik).
            ->select(
                DB::raw('STRAIGHT_JOIN gu.good_id'),
                'gld.id AS detail_id',
                'gl.loading_date',
                'gld.real_quantity',
                'gld.quantity',
                'u.name     AS unit_name',
                'u.quantity AS unit_qty'
            )
            ->get()
            // Loading terbaru per barang dipilih di PHP. Mengurutkan puluhan ribu
            // baris hasil join di MySQL (ORDER BY good_id, loading_date) memakan
            // belasan detik; satu kali lintasan di sini jauh lebih cepat.
            ->reduce(function ($latest, $row) {
                $current = $latest[$row->good_id] ?? null;

                if ($current === null
                    || $row->loading_date > $current->loading_date
                    || ($row->loading_date == $current->loading_date && $row->detail_id > $current->detail_id)) {
                    $latest[$row->good_id] = $row;
                }

                return $latest;
            }, []);
        $lastLoadingRaw = collect($lastLoadingRaw);

        // ── Ambil daftar distributor untuk pengelompokan ──────────────────────
        $distributors = Distributor::whereNull('deleted_at')->get()->keyBy('id');

        // ── Hitung rekomendasi per barang ─────────────────────────────────────
        $items = collect();
        $reviewCount     = 0; // barang yang habis bukan karena laku
        $seasonOffCount  = 0; // barang musiman di luar musim (tidak disarankan order)
        $seasonPrepCount = 0; // barang musiman yang musimnya segera tiba

        foreach ($goodsRaw as $good) {
            $gid       = $good->good_id;
            $agg       = $salesAgg->get($gid);
            // Semua jumlah dihitung dalam SATUAN TERKECIL barang (kolom "satuan").
            // real_quantity (penjualan & stok keluar) dalam pcs, goods.last_stock
            // dalam satuan dasar — dulu keduanya dibandingkan langsung, salah untuk
            // barang yang satuan dasarnya pak/dus.
            $smallQty  = (float) $good->satuan_qty > 0 ? (float) $good->satuan_qty : 1;
            $baseQty   = (float) $good->base_qty > 0 ? (float) $good->base_qty : $smallQty;
            $totalTrx  = $agg ? (int)   $agg->total_transaksi   : 0;
            $totalQty  = $agg ? (float) $agg->total_qty_terjual / $smallQty : 0;
            $stok      = round((float) $good->stok_sekarang * $baseQty / $smallQty, 2);
            $hargaBeli = (float) ($good->harga_beli ?? 0);

            // ── Stok keluar bukan karena laku dalam periode ───────────────────
            $outflow    = array_map(fn ($q) => $q / $smallQty, $outflowAgg[$gid] ?? StockOutflow::emptyRow());
            $qtyNonSale = StockOutflow::nonSale($outflow);
            $qtyKeluar  = $totalQty + $qtyNonSale;
            $nonSaleShare = $qtyKeluar > 0 ? $qtyNonSale / $qtyKeluar : 0;

            // Penyebab bukan-laku yang paling besar (untuk label)
            $penyebab = null;
            foreach (StockOutflow::NON_SALE as $reason) {
                if ($outflow[$reason] > 0 && ($penyebab === null || $outflow[$reason] > $outflow[$penyebab])) {
                    $penyebab = $reason;
                }
            }

            $avgQtyPerDay = $days > 0 ? $totalQty / $days : 0;

            // Kebutuhan order dihitung dari penjualan saja (rata-rata jual/hari)
            // dan stok aktual.
            $calc = $this->calculate($avgQtyPerDay, $totalTrx, $stok, $minFast);

            // Barang yang stoknya menipis/habis TERUTAMA karena expired, rusak,
            // hilang atau diretur — bukan karena laku — tidak direkomendasikan
            // order. Ditandai "Tinjau Dulu" supaya diputuskan manual.
            $habisBukanLaku = $qtyNonSale > 0
                && $nonSaleShare >= self::NON_SALE_DOMINANT_SHARE
                && ($calc['perlu_order'] || $stok <= 0);

            if ($habisBukanLaku) {
                $reviewCount++;
                $calc['perlu_order']   = false;
                $calc['reorder_qty']   = 0;
                $calc['urgensi']       = 3;
                $calc['urgensi_label'] = 'Tinjau Dulu';
            }

            // ── Barang musiman ────────────────────────────────────────────────
            // Di luar musim: tidak disarankan order (kecuali tetap laris/fast).
            // Menjelang musim: order sebanyak penjualan musim lalu dikurangi stok,
            // karena rata-rata jual/hari periode ini belum mencerminkan musimnya.
            $seasonId   = $adaKolomBaru ? Season::efektif($good->season_good, $good->season_type) : null;
            $ms         = $seasonId ? $musim->get($seasonId) : null;
            $musimLabel = null;
            if ($ms && ! $habisBukanLaku) {
                if ($ms['status'] === Season::LUAR && $calc['tipe'] !== 'fast') {
                    if ($calc['perlu_order']) {
                        $seasonOffCount++; // yang tanpa musim akan disarankan order
                    }
                    $calc['perlu_order']   = false;
                    $calc['reorder_qty']   = 0;
                    $calc['urgensi']       = 4;
                    $calc['urgensi_label'] = 'Di Luar Musim';
                    $musimLabel = $ms['season']->name . ' · ' . $ms['label'];
                } elseif ($ms['status'] === Season::PERSIAPAN) {
                    $lalu    = ($musimLaluSold[$seasonId] ?? collect())->get($gid);
                    $qtyLalu = $lalu ? (float) $lalu->total_qty_terjual / $smallQty : 0;
                    $musimLabel = $ms['season']->name . ' · ' . $ms['label']
                        . ' · musim lalu terjual ' . number_format($qtyLalu, 0, ',', '.');
                    $butuh = (int) ceil($qtyLalu - max(0, $stok));
                    if ($butuh > max(0, $calc['reorder_qty'])) {
                        $seasonPrepCount++;
                        $calc['perlu_order']   = true;
                        $calc['reorder_qty']   = $butuh;
                        $calc['target_stock']  = (int) ceil($qtyLalu);
                        $calc['urgensi']       = 2;
                        $calc['urgensi_label'] = 'Siapkan Stok Musim';
                    }
                }
            }

            // Lewati barang yang tidak perlu di-order, kecuali diminta tampilkan semua
            if ($onlyNeeded && !$calc['perlu_order']) {
                continue;
            }

            $unitOptions = $allUnitsByGood->get($gid, collect());
            $converted   = $this->convertToBiggestUnit($calc['reorder_qty'], $unitOptions, $smallQty);

            $distId   = $good->distributor_id;
            $distName = $distId && $distributors->has($distId)
                ? $distributors->get($distId)->name
                : 'Belum Ditentukan';

            // ── Data loading (restock) terakhir, sebagai pembanding ──────────
            $lastLoading = $lastLoadingRaw->get($gid);
            if ($lastLoading) {
                $llQty  = (float) ($lastLoading->real_quantity ?? $lastLoading->quantity ?? 0);
                $llUnit = $lastLoading->unit_name ?? 'pcs';
                $llDate = $lastLoading->loading_date
                    ? Carbon::parse($lastLoading->loading_date)->format('d-m-Y')
                    : null;
            } else {
                $llQty  = null;
                $llUnit = null;
                $llDate = null;
            }

            $items->push((object) [
                'good_id'             => $gid,
                'kode'                => $good->kode,
                'nama'                => Good::formatName($good->tipe, $good->nama),
                'kategori'            => $good->kategori ?? '-',
                'merk'                => $good->merk     ?? '-',
                'satuan'              => $good->satuan   ?? '-',
                'distributor_id'      => $distId,
                'distributor_nama'    => $distName,
                'stok_sekarang'       => $stok,
                'qty_terjual'         => $totalQty,            // terjual dalam periode
                'keluar'              => $outflow,             // stok keluar per penyebab dlm periode
                'keluar_teks'         => $this->outflowText($totalQty, $outflow),
                'qty_non_sale'        => $qtyNonSale,          // retur + expired + rusak + hilang
                'non_sale_persen'     => (int) round($nonSaleShare * 100),
                'penyebab_utama'      => $penyebab,            // penyebab bukan-laku terbesar (atau null)
                'habis_bukan_laku'    => $habisBukanLaku,      // true = tidak direkomendasikan order
                'avg_qty_per_day'     => round($avgQtyPerDay, 2),
                'total_transaksi'     => $totalTrx,
                'lead_time_days'      => $calc['lead_time_days'],
                'safety_stock'        => $calc['safety_stock'],
                'min_stock'           => $calc['min_stock'],     // reorder point
                'target_stock'        => $calc['target_stock'],
                'reorder_qty_raw'     => $calc['reorder_qty'],   // dalam satuan dasar (pcs), utk referensi
                'reorder_qty'         => $converted['qty'],      // angka editable, dlm satuan kemasan terbesar
                'reorder_unit'        => $converted['unit_name'],// nama satuan kemasan terbesar (1 satuan saja)
                'reorder_unit_qty'    => $converted['unit_qty'], // faktor konversi satuan itu ke pcs
                'harga_beli'          => $hargaBeli,
                'harga_beli_per_unit' => round($hargaBeli * $converted['unit_qty'], 0), // harga per satuan kemasan
                'estimasi_biaya'      => round($converted['qty'] * $hargaBeli * $converted['unit_qty'], 0),
                'urgensi'             => $calc['urgensi'],         // 1=segera 2=mendekati 3=tinjau dulu 0=aman
                'urgensi_label'       => $calc['urgensi_label'],
                'tipe'                => $calc['tipe'],            // fast/slow/baru
                'musim_label'         => $musimLabel,              // null jika bukan barang musiman yang sedang di luar/menjelang musim
                'last_loading_date'   => $llDate,                  // null jika belum pernah loading
                'last_loading_qty'    => $llQty,
                'last_loading_unit'   => $llUnit,
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
                        $urgensiRank = [1 => 0, 2 => 1, 3 => 2, 4 => 4][$item->urgensi] ?? 3;
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
            // dihitung dari SEMUA barang, termasuk yang disembunyikan filter "hanya yang perlu order"
            'review_count'      => $reviewCount,
            'season_off_count'  => $seasonOffCount,
            'season_prep_count' => $seasonPrepCount,
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
    private function calculate(float $avgQtyPerDay, int $totalTrx, float $stok, int $minFast = 10): array
    {
        $leadTime = self::DEFAULT_LEAD_TIME_DAYS;

        // Barang tanpa penjualan sama sekali dalam periode dianggap "baru/jarang"
        // -> dipakai faktor buffer paling kecil supaya tidak ikut direkomendasikan
        // order besar-besaran (mencegah over-order untuk barang yang memang sepi).
        // Batas "fast" sama dengan menu Perputaran Barang (menyesuaikan panjang periode).
        if ($totalTrx === 0 || $avgQtyPerDay <= 0) {
            $tipe         = 'baru_atau_tidak_aktif';
            $safetyFactor = self::SAFETY_FACTOR_NEW;
        } elseif ($totalTrx >= $minFast) {
            $tipe         = 'fast';
            $safetyFactor = self::SAFETY_FACTOR_FAST;
        } else {
            $tipe         = 'slow';
            $safetyFactor = self::SAFETY_FACTOR_SLOW;
        }

        // Safety stock tetap desimal -> ini nilai perantara untuk hitung urgensi,
        // tidak ditampilkan langsung sebagai angka pemesanan ke user.
        $safetyStock = $avgQtyPerDay * $leadTime * $safetyFactor;

        // Min. Stok, Target Stok, dan Qty Order WAJIB bulat (integer) dan
        // dibulatkan KE ATAS (ceil) -- karena barang dipesan dalam satuan utuh
        // (pcs/dus), dan lebih aman kelebihan sedikit daripada kurang dari
        // kebutuhan minimum. Pembulatan dilakukan di angka akhir saja (bukan
        // di tiap langkah) supaya tidak terjadi penumpukan bias pembulatan.
        $minStock    = (int) ceil($safetyStock + ($avgQtyPerDay * $leadTime));
        $targetStock = (int) ceil($safetyStock + $avgQtyPerDay * ($leadTime + self::REVIEW_PERIOD_DAYS));

        $perluOrder = $stok <= $minStock && $avgQtyPerDay > 0;
        $reorderQty = $perluOrder ? max(0, (int) ceil($targetStock - $stok)) : 0;

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

    /** Ringkasan stok keluar dalam periode, mis. "terjual 14, expired 3". */
    private function outflowText(float $terjual, array $outflow): string
    {
        $parts = ['terjual ' . number_format($terjual, 0, ',', '.')];

        foreach (StockOutflow::NON_SALE as $reason) {
            if ($outflow[$reason] > 0) {
                $parts[] = strtolower(StockOutflow::LABELS[$reason]) . ' ' . number_format($outflow[$reason], 0, ',', '.');
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Konversi reorder_qty (dalam satuan dasar/pcs) menjadi 1 angka BULAT
     * dalam SATU satuan kemasan terbesar yang tersedia untuk barang itu.
     *
     * Hasilnya selalu integer dan dibulatkan KE ATAS (mis. kebutuhan 2.3 Dus
     * dibulatkan jadi 3 Dus) — karena pemesanan ke distributor dilakukan
     * dalam satuan utuh (tidak ada "setengah dus"), dan lebih aman kelebihan
     * sedikit daripada order kurang dari kebutuhan minimum.
     *
     * $units sudah diurutkan dari kemasan terbesar -> terkecil oleh query
     * pemanggil (orderBy unit_qty desc), jadi cukup ambil yang pertama.
     */
    private function convertToBiggestUnit(float $qtyNeeded, $units, float $smallQty = 1): array
    {
        if ($units->isEmpty()) {
            return [
                'qty'       => (int) ceil($qtyNeeded),
                'unit_name' => 'pcs',
                'unit_qty'  => 1,
            ];
        }

        // $qtyNeeded dalam satuan terkecil; unit_qty kemasan dalam pcs, jadi
        // isinya dihitung ulang dalam satuan terkecil (biasanya satuan terkecil = 1 pcs).
        $biggest = $units->first(); // kemasan terbesar (sudah di-sort desc oleh query)
        $unitQty = max(1, (int) round((float) $biggest->unit_qty / max($smallQty, 0.000001)));

        // Dibulatkan ke atas ke bilangan bulat penuh supaya jumlah yang dipesan
        // selalu kelipatan utuh kemasan dan tidak pernah kurang dari kebutuhan
        // riil (mis. kebutuhan 2.1 Dus -> tetap dibulatkan jadi 3 Dus).
        $qtyInBiggestUnit = $unitQty > 0 ? (int) ceil($qtyNeeded / $unitQty) : (int) ceil($qtyNeeded);

        return [
            'qty'       => $qtyInBiggestUnit,
            'unit_name' => $biggest->unit_name,
            'unit_qty'  => $unitQty,
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