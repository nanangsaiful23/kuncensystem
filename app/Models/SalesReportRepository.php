<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;
use App\Models\Good;
use App\Models\Member;
use App\Models\PiutangPayment;
use App\Models\Journal;
use App\Models\ScaleLedger;
use App\Models\Account;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ReturItem;
use App\Models\Voucher;
class SalesReportRepository
{
    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================
 
    /**
     * Kembalikan [start, end] Carbon yang sudah di-parse,
     * atau [null, null] jika salah satu kosong.
     */
    private function parseDateRange($startDate, $endDate): array
    {
        if (!$startDate || !$endDate) {
            return [null, null];
        }
        return [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ];
    }
 
    // =========================================================================
    // A. RINGKASAN PENJUALAN (Sales Summary)
    // =========================================================================
 
    /**
     * KPI Utama: Total Omzet, HPP, Laba Kotor, Jumlah Transaksi
     * ✅ Optimasi: agregasi langsung di DB — tidak load collection ke PHP
     */
    public function getSalesSummary($startDate = null, $endDate = null)
    {
        // ── Agregat dari transactions ─────────────────────────────────────
        $trxQuery = Transaction::where('type', 'normal')
            ->whereNull('deleted_at')
            ->select(
                DB::raw('COUNT(*)                                              AS jumlah'),
                DB::raw('COALESCE(SUM(total_sum_price),        0)             AS omzet'),
                DB::raw('COALESCE(SUM(total_discount_price),   0)
                       + COALESCE(SUM(voucher_nominal),        0)             AS diskon')
            );
 
        // ── Agregat HPP dari transaction_details (sub-query) ──────────────
        //    Lebih cepat daripada JOIN ke transactions lagi
        $hppQuery = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                DB::raw('COALESCE(SUM(transaction_details.buy_price * transaction_details.quantity), 0) AS hpp')
            );
 
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
        if ($start) {
            $trxQuery->whereBetween('created_at',              [$start, $end]);
            $hppQuery->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        $trx = $trxQuery->first();
        $hpp = $hppQuery->first();
 
        $totalOmzet     = (float) $trx->omzet;
        $totalDiskon    = (float) $trx->diskon;
        $totalHpp       = (float) $hpp->hpp;
        $totalTransaksi = (int)   $trx->jumlah;
        $totalLaba      = $totalOmzet - $totalDiskon - $totalHpp;
        $rataRata       = $totalTransaksi > 0 ? $totalOmzet / $totalTransaksi : 0;
 
        return compact(
            'totalOmzet', 'totalDiskon', 'totalHpp',
            'totalLaba',  'totalTransaksi', 'rataRata'
        );
    }
 
    /**
     * Omzet & Laba harian dalam rentang tanggal (untuk chart trend)
     */
    public function getDailySalesTrend($startDate, $endDate)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        return Transaction::where('type', 'normal')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at)                                              AS tanggal'),
                DB::raw('COALESCE(SUM(total_sum_price), 0)                            AS omzet'),
                DB::raw('COALESCE(SUM(total_sum_price), 0)
                       - COALESCE(SUM(total_discount_price), 0)
                       - COALESCE(SUM(voucher_nominal),      0)                        AS omzet_bersih'),
                DB::raw('COUNT(*)                                                      AS jumlah_transaksi')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('tanggal', 'asc')
            ->get();
    }
 
    /**
     * Omzet & Laba per bulan (untuk chart bulanan)
     */
    public function getMonthlySalesTrend($year = null)
    {
        $year = $year ?? now()->year;
 
        return Transaction::where('type', 'normal')
            ->whereNull('deleted_at')
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('YEAR(created_at)                                               AS tahun'),
                DB::raw('MONTH(created_at)                                              AS bulan'),
                DB::raw('COALESCE(SUM(total_sum_price), 0)                             AS omzet'),
                DB::raw('COALESCE(SUM(total_discount_price), 0)
                       + COALESCE(SUM(voucher_nominal),      0)                         AS total_diskon'),
                DB::raw('COUNT(*)                                                       AS jumlah_transaksi')
            )
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderBy('bulan', 'asc')
            ->get();
    }
 
    // =========================================================================
    // B. LAPORAN PRODUK (Product Performance)
    // =========================================================================
 
    /**
     * Produk terlaris — dipisah per unit karena tiap unit punya
     * harga & konversi qty yang berbeda (Pcs ≠ Pack ≠ Dus).
     *
     * MENGAPA HARUS GROUP BY good_unit_id (bukan good_id)?
     * ──────────────────────────────────────────────────────
     * Contoh nyata satu produk "Indomie Goreng":
     *   - good_unit_id=10  satuan=Pcs   buy=Rp2.000  sell=Rp2.500  qty terjual=240
     *   - good_unit_id=11  satuan=Pack  buy=Rp23.000 sell=Rp28.000 qty terjual=50
     *   - good_unit_id=12  satuan=Dus   buy=Rp85.000 sell=Rp95.000 qty terjual=10
     *
     * Jika GROUP BY good_id:
     *   total_qty   = 240+50+10 = 300  ← SALAH, satuan berbeda tidak bisa dijumlah
     *   AVG(harga)  = avg(2500,28000,95000) ← TIDAK BERMAKNA
     *   total_laba  = campur aduk harga beli & jual beda unit ← SALAH
     *
     * Dengan GROUP BY good_unit_id:
     *   Setiap baris = 1 kombinasi (produk + satuan) yang konsisten
     *   qty, omzet, laba semua dalam satuan yang sama → BENAR
     *
     * STRATEGI 2 TAHAP (tetap dipertahankan untuk performa):
     *   Tahap 1 → agregasi ringan di 3 tabel inti, GROUP BY good_unit_id
     *   Tahap 2 → ambil nama produk/satuan/kategori/brand hanya untuk
     *             good_unit_id hasil tahap 1
     */
    public function getTopSellingGoods($startDate = null, $endDate = null, $limit = 10)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        // ── TAHAP 1: agregasi per good_unit_id ────────────────────────────
        // Semua angka (qty, omzet, laba) konsisten dalam satuan yang sama
        // karena setiap good_unit_id merepresentasikan 1 kombinasi barang+satuan
        $aggQuery = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                // Kunci grup: per unit, bukan per produk
                'transaction_details.good_unit_id',
 
                // Harga dicatat saat transaksi (snapshot) → pakai kolom di transaction_details,
                // BUKAN dari good_units, karena harga bisa berubah setelah transaksi
                DB::raw('SUM(transaction_details.quantity)                               AS total_qty'),
                DB::raw('SUM(transaction_details.sum_price)                              AS total_omzet'),
 
                // Laba per baris = (harga jual snapshot - harga beli snapshot) × qty
                DB::raw('SUM(
                            (transaction_details.selling_price - transaction_details.buy_price)
                            * transaction_details.quantity
                         )                                                                AS total_laba'),
 
                // Harga jual rata-rata aktual dari transaksi (bukan dari master)
                DB::raw('AVG(transaction_details.selling_price)                          AS harga_jual_rata'),
                DB::raw('AVG(transaction_details.buy_price)                              AS harga_beli_rata')
            );
 
        if ($start) {
            $aggQuery->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        $aggregated = $aggQuery
            ->groupBy('transaction_details.good_unit_id')
            ->orderBy('total_omzet', 'desc')
            ->limit($limit)
            ->get()
            ->keyBy('good_unit_id');
 
        if ($aggregated->isEmpty()) {
            return collect();
        }
 
        // ── TAHAP 2: master data hanya untuk good_unit_id yang relevan ────
        $goodUnitIds = $aggregated->keys()->all();
 
        // 1 query: ambil good_units + relasi unit, good, category, brand sekaligus
        $goodUnits = GoodUnit::whereIn('good_units.id', $goodUnitIds)
            ->join('units',  'units.id',  '=', 'good_units.unit_id')
            ->join('goods',  'goods.id',  '=', 'good_units.good_id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->select(
                'good_units.id        AS good_unit_id',
                'good_units.good_id',
                'goods.name           AS nama_produk',
                'goods.code           AS kode_produk',
                'units.id             AS unit_id',
                'units.name           AS satuan',
                'units.quantity       AS unit_qty_konversi',
                'categories.name      AS kategori',
                'brands.name          AS merk'
            )
            ->get()
            ->keyBy('good_unit_id');
 
        // ── Gabungkan: tiap baris = 1 produk + 1 satuan ───────────────────
        return $aggregated
            ->map(function ($agg) use ($goodUnits) {
                $gu = $goodUnits->get($agg->good_unit_id);
 
                $totalOmzet = (float) $agg->total_omzet;
                $totalLaba  = (float) $agg->total_laba;
                $margin     = $totalOmzet > 0
                    ? round($totalLaba / $totalOmzet * 100, 2)
                    : 0;
 
                return (object) [
                    'good_unit_id'      => $agg->good_unit_id,
                    'good_id'           => $gu->good_id,
                    'kode_produk'       => $gu->kode_produk   ?? '-',
                    'nama_produk'       => $gu->nama_produk   ?? '(dihapus)',
                    'satuan'            => $gu->satuan        ?? '-',
                    'unit_qty_konversi' => $gu->unit_qty_konversi ?? 1,
                    'kategori'          => $gu->kategori      ?? '-',
                    'merk'              => $gu->merk          ?? '-',
                    'total_qty'         => (float) $agg->total_qty,
                    'total_omzet'       => $totalOmzet,
                    'total_laba'        => $totalLaba,
                    'margin_pct'        => $margin,
                    'harga_jual_rata'   => (float) $agg->harga_jual_rata,
                    'harga_beli_rata'   => (float) $agg->harga_beli_rata,
                ];
            })
            ->sortByDesc('total_omzet')
            ->values();
    }
 
    /**
     * Rekap penjualan per produk (semua unit digabung dalam 1 produk,
     * dikonversi ke satuan terkecil lewat units.quantity).
     *
     * Digunakan untuk laporan ringkas "per barang" tanpa detail unit.
     * qty_konversi = qty × unit.quantity → semua dalam satuan dasar (Pcs/eceran)
     */
    public function getSalesPerGood($startDate = null, $endDate = null, $limit = 20)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        // Tahap 1: agregasi per good_id, konversi qty ke satuan dasar
        $aggQuery = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->join('units',      'units.id',      '=', 'good_units.unit_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                'good_units.good_id',
                // Qty dikonversi ke satuan dasar agar bisa dijumlah lintas unit
                DB::raw('SUM(transaction_details.quantity * CAST(units.quantity AS UNSIGNED)) AS total_qty_dasar'),
                DB::raw('SUM(transaction_details.sum_price)                                   AS total_omzet'),
                DB::raw('SUM(
                            (transaction_details.selling_price - transaction_details.buy_price)
                            * transaction_details.quantity
                         )                                                                     AS total_laba'),
                DB::raw('COUNT(DISTINCT transaction_details.good_unit_id)                     AS jumlah_varian_unit')
            );
 
        if ($start) {
            $aggQuery->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        $aggregated = $aggQuery
            ->groupBy('good_units.good_id')
            ->orderBy('total_omzet', 'desc')
            ->limit($limit)
            ->get()
            ->keyBy('good_id');
 
        if ($aggregated->isEmpty()) {
            return collect();
        }
 
        // Tahap 2: master data produk
        $goods = Good::whereIn('id', $aggregated->keys()->all())
            ->whereNull('goods.deleted_at')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->select(
                'goods.id',
                'goods.code       AS kode_produk',
                'goods.name       AS nama_produk',
                'categories.name  AS kategori',
                'brands.name      AS merk'
            )
            ->get()
            ->keyBy('id');
 
        // Ambil nama satuan dasar tiap produk (unit.quantity = 1 atau terkecil)
        $baseUnits = GoodUnit::join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereIn('good_units.good_id', $aggregated->keys()->all())
            ->whereNull('good_units.deleted_at')
            ->select('good_units.good_id', 'units.name AS satuan_dasar')
            ->orderByRaw('CAST(units.quantity AS UNSIGNED) ASC')
            ->get()
            ->unique('good_id')
            ->keyBy('good_id');
 
        return $aggregated->map(function ($agg) use ($goods, $baseUnits) {
            $good      = $goods->get($agg->good_id);
            $baseUnit  = $baseUnits->get($agg->good_id);
            $totalOmzet = (float) $agg->total_omzet;
            $totalLaba  = (float) $agg->total_laba;
 
            return (object) [
                'good_id'           => $agg->good_id,
                'kode_produk'       => $good->kode_produk  ?? '-',
                'nama_produk'       => $good->nama_produk  ?? '(dihapus)',
                'kategori'          => $good->kategori     ?? '-',
                'merk'              => $good->merk         ?? '-',
                'satuan_dasar'      => $baseUnit->satuan_dasar ?? '-',
                // qty sudah dikonversi ke satuan dasar — aman dijumlah
                'total_qty_dasar'   => (float) $agg->total_qty_dasar,
                'jumlah_varian_unit'=> (int)   $agg->jumlah_varian_unit,
                'total_omzet'       => $totalOmzet,
                'total_laba'        => $totalLaba,
                'margin_pct'        => $totalOmzet > 0
                    ? round($totalLaba / $totalOmzet * 100, 2) : 0,
            ];
        })->sortByDesc('total_omzet')->values();
    }
 
    /**
     * Performa penjualan per Kategori
     * ✅ Optimasi: GROUP BY hanya pada category_id (integer), bukan nama string
     */
    public function getSalesByCategory($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->join('goods',      'goods.id',      '=', 'good_units.good_id')
            ->join('categories', 'categories.id', '=', 'goods.category_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                'categories.id   AS category_id',
                'categories.name AS kategori',
                DB::raw('SUM(transaction_details.quantity)                          AS total_qty'),
                DB::raw('SUM(transaction_details.sum_price)                         AS total_omzet'),
                DB::raw('SUM((transaction_details.selling_price
                           - transaction_details.buy_price)
                           * transaction_details.quantity)                           AS total_laba'),
                DB::raw('COUNT(DISTINCT transactions.id)                            AS jumlah_transaksi')
            );
 
        if ($start) {
            $query->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        // GROUP BY PK integer + nama (diperlukan MySQL strict mode)
        return $query
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_omzet', 'desc')
            ->get();
    }
 
    /**
     * Performa penjualan per Merek/Brand
     * ✅ Optimasi: sama — GROUP BY integer PK dulu
     */
    public function getSalesByBrand($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->join('goods',      'goods.id',      '=', 'good_units.good_id')
            ->join('brands',     'brands.id',     '=', 'goods.brand_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                'brands.id   AS brand_id',
                'brands.name AS merk',
                DB::raw('SUM(transaction_details.quantity)                          AS total_qty'),
                DB::raw('SUM(transaction_details.sum_price)                         AS total_omzet'),
                DB::raw('SUM((transaction_details.selling_price
                           - transaction_details.buy_price)
                           * transaction_details.quantity)                           AS total_laba')
            );
 
        if ($start) {
            $query->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        return $query
            ->groupBy('brands.id', 'brands.name')
            ->orderBy('total_omzet', 'desc')
            ->get();
    }
 
    // =========================================================================
    // C. LAPORAN PELANGGAN (Customer Analysis)
    // =========================================================================
 
    /**
     * Pelanggan (Member) paling aktif berdasarkan omzet
     * ✅ Optimasi: GROUP BY integer PK saja, nama di-resolve setelah
     */
    public function getTopMembers($startDate = null, $endDate = null, $limit = 10)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = Transaction::whereNotNull('member_id')
            ->where('type', 'normal')
            ->whereNull('deleted_at')
            ->select(
                'member_id',
                DB::raw('COUNT(*)                                                   AS jumlah_transaksi'),
                DB::raw('COALESCE(SUM(total_sum_price), 0)                         AS total_omzet'),
                DB::raw('COALESCE(SUM(total_discount_price), 0)
                       + COALESCE(SUM(voucher_nominal), 0)                          AS total_diskon'),
                DB::raw('MAX(created_at)                                            AS transaksi_terakhir')
            );
 
        if ($start) {
            $query->whereBetween('created_at', [$start, $end]);
        }
 
        $aggregated = $query
            ->groupBy('member_id')
            ->orderBy('total_omzet', 'desc')
            ->limit($limit)
            ->get()
            ->keyBy('member_id');
 
        if ($aggregated->isEmpty()) {
            return collect();
        }
 
        // Ambil data member hanya untuk ID yang muncul
        $members = Member::whereIn('id', $aggregated->keys()->all())
            ->whereNull('deleted_at')
            ->select('id', 'name', 'store_name', 'phone_number')
            ->get()
            ->keyBy('id');
 
        return $aggregated->map(function ($row) use ($members) {
            $m = $members->get($row->member_id);
            return (object) [
                'member_id'          => $row->member_id,
                'nama_member'        => $m ? ($m->name ?? '(dihapus)') : '(dihapus)',
                'nama_toko'          => $m ? ($m->store_name ?? '-') : '-',
                'telepon'            => $m ? ($m->phone_number ?? '-') : '-',
                'jumlah_transaksi'   => $row->jumlah_transaksi,
                'total_omzet'        => $row->total_omzet,
                'total_diskon'       => $row->total_diskon,
                'transaksi_terakhir' => $row->transaksi_terakhir,
            ];
        })->sortByDesc('total_omzet')->values();
    }
 
    /**
     * Rekap piutang member
     * ✅ Optimasi: dua query terpisah → merge di PHP (lebih cepat dari subquery nested)
     */
    public function getMemberReceivables()
    {
        // Total tagihan kredit per member
        $tagihan = Transaction::whereNotNull('member_id')
            ->whereIn('type', ['normal', 'retur'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('payment', '!=', 'cash')
                  ->orWhereRaw('COALESCE(money_paid, 0) < total_sum_price');
            })
            ->select(
                'member_id',
                DB::raw('COALESCE(SUM(total_sum_price), 0) AS total_tagihan')
            )
            ->groupBy('member_id')
            ->get()
            ->keyBy('member_id');
 
        if ($tagihan->isEmpty()) {
            return collect();
        }
 
        // Total pembayaran piutang hanya untuk member yang punya tagihan
        $memberIds = $tagihan->keys()->all();
 
        $bayar = PiutangPayment::whereIn('member_id', $memberIds)
            ->whereNull('deleted_at')
            ->select('member_id', DB::raw('COALESCE(SUM(money), 0) AS total_pembayaran'))
            ->groupBy('member_id')
            ->get()
            ->keyBy('member_id');
 
        // Data member
        $members = Member::whereIn('id', $memberIds)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'phone_number')
            ->get()
            ->keyBy('id');
 
        return $tagihan->map(function ($t) use ($bayar, $members) {
            $m        = $members->get($t->member_id);
            $dibayar  = (float) ($bayar->get($t->member_id)->total_pembayaran ?? 0);
            $tagihan  = (float) $t->total_tagihan;
 
            return (object) [
                'member_id'        => $t->member_id,
                'nama_member'      => $m->name         ?? '(dihapus)',
                'telepon'          => $m->phone_number ?? '-',
                'total_tagihan'    => $tagihan,
                'total_pembayaran' => $dibayar,
                'sisa_piutang'     => $tagihan - $dibayar,
            ];
        })->sortByDesc('sisa_piutang')->values();
    }
 
    // =========================================================================
    // D. LAPORAN PEMBELIAN & STOK (Purchase & Stock)
    // =========================================================================
 
    /**
     * Rekap pembelian per distributor
     */
    public function getPurchaseSummaryByDistributor($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = GoodLoading::join(
                'distributors', 'distributors.id', '=', 'good_loadings.distributor_id'
            )
            ->whereNull('good_loadings.deleted_at')
            ->select(
                'distributors.id       AS distributor_id',
                'distributors.name     AS nama_distributor',
                'distributors.location AS lokasi',
                DB::raw('COUNT(good_loadings.id)               AS jumlah_loading'),
                DB::raw('COALESCE(SUM(good_loadings.total_item_price), 0) AS total_pembelian')
            );
 
        if ($start) {
            $query->whereBetween('good_loadings.loading_date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }
 
        return $query
            ->groupBy('distributors.id', 'distributors.name', 'distributors.location')
            ->orderBy('total_pembelian', 'desc')
            ->get();
    }
 
    /**
     * Valuasi stok saat ini — per produk, SATU baris per produk.
     *
     * LOGIKA STOK & UNIT:
     * ─────────────────────────────────────────────────────────────────
     * goods.last_stock  = stok dalam SATUAN TERKECIL (Pcs, eceran, dll)
     * units.quantity    = berapa satuan terkecil dalam 1 unit ini
     *                     → Pcs=1, Pack=12, Dus=24, Karton=48, dst
     *
     * Untuk valuasi yang benar harus pakai good_unit yang unitnya
     * memiliki quantity = 1 (satuan terkecil), karena:
     *   last_stock=120 Pcs × harga_beli_Pcs = nilai HPP yang benar ✅
     *   last_stock=120 Pcs × harga_beli_Dus = 120 × harga_dus → SALAH ❌
     *
     * Jika tidak ada unit dengan quantity=1, fallback ke unit dengan
     * quantity terkecil (CAST ke UNSIGNED agar sort numerik bukan string).
     *
     * STRATEGI SUBQUERY:
     *   Subquery pilih good_unit_id terkecil per good_id terlebih dahulu,
     *   lalu JOIN ke sana — sehingga tiap produk tetap 1 baris.
     */
    public function getCurrentStockValuation()
    {
        // Subquery: ambil 1 good_unit_id per good_id (unit dengan quantity terkecil)
        // Ini mereplikasi logika Good::getPcsSellingPrice()
        $baseUnitSub = DB::table('good_units')
            ->join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereNull('good_units.deleted_at')
            ->select(
                'good_units.good_id',
                DB::raw('MIN(good_units.id) AS good_unit_id'),   // tie-break: ambil id terkecil
                DB::raw('MIN(CAST(units.quantity AS UNSIGNED)) AS min_qty')
            )
            ->groupBy('good_units.good_id');
 
        // Subquery: dari good_unit_id yang quantity-nya = min_qty per good
        $pickedUnit = DB::table('good_units AS gu2')
            ->join('units AS u2', 'u2.id', '=', 'gu2.unit_id')
            ->joinSub($baseUnitSub, 'base', function ($join) {
                $join->on('gu2.good_id', '=', 'base.good_id')
                     ->whereRaw('CAST(u2.quantity AS UNSIGNED) = base.min_qty');
            })
            ->whereNull('gu2.deleted_at')
            ->select(
                'gu2.good_id',
                'gu2.id          AS good_unit_id',
                'u2.name         AS satuan',
                'u2.quantity     AS unit_qty',
                'gu2.buy_price',
                'gu2.selling_price'
            )
            // Jika ada lebih dari 1 unit dengan qty sama, ambil 1 saja (id terkecil)
            ->orderBy('gu2.id', 'asc');
 
        return Good::joinSub($pickedUnit, 'pu', 'pu.good_id', '=', 'goods.id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->whereNull('goods.deleted_at')
            ->select(
                'goods.id                  AS good_id',
                'goods.code                AS kode',
                'goods.name                AS nama_barang',
                'categories.name           AS kategori',
                'brands.name               AS merk',
 
                // Tampilkan stok dalam satuan terkecil (sesuai goods.last_stock)
                'pu.satuan                 AS satuan',
                'pu.unit_qty               AS unit_qty',
                'goods.last_stock          AS stok_akhir',   // satuan terkecil
 
                // Harga per satuan terkecil
                'pu.buy_price              AS harga_beli',
                'pu.selling_price          AS harga_jual',
 
                // Nilai = stok_terkecil × harga_satuan_terkecil → BENAR
                DB::raw('goods.last_stock * pu.buy_price      AS nilai_hpp'),
                DB::raw('goods.last_stock * pu.selling_price  AS nilai_jual'),
 
                // Potensi laba jika semua stok terjual
                DB::raw('goods.last_stock * (pu.selling_price - pu.buy_price) AS potensi_laba')
            )
            ->orderBy('nilai_hpp', 'desc')
            ->get();
    }
 
    /**
     * Barang retur ke distributor
     */
    public function getReturSummary($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = ReturItem::join('goods',        'goods.id',        '=', 'retur_items.good_id')
            ->join('good_units',  'good_units.id',  '=', 'retur_items.good_unit_id')
            ->join('units',       'units.id',        '=', 'good_units.unit_id')
            ->join('distributors','distributors.id', '=', 'retur_items.last_distributor_id')
            ->whereNull('retur_items.deleted_at')
            ->select(
                'distributors.name       AS distributor',
                'goods.name              AS nama_barang',
                'units.name              AS satuan',
                'retur_items.returned_type AS jenis_retur',
                'retur_items.returned_date',
                'good_units.buy_price    AS harga_beli'
            );
 
        if ($start) {
            $query->whereBetween('retur_items.returned_date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }
 
        return $query->orderBy('retur_items.returned_date', 'desc')->get();
    }
 
    // =========================================================================
    // E. LAPORAN KEUANGAN (Financial Reports)
    // =========================================================================
 
    /**
     * Ringkasan Laba Rugi
     * ✅ Optimasi: semua agregasi dilakukan di DB, tidak load collection
     */
    public function getProfitLossSummary($startDate, $endDate)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        // Penjualan normal
        $penjualan = Transaction::where('type', 'normal')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COALESCE(SUM(total_sum_price),       0) AS omzet'),
                DB::raw('COALESCE(SUM(total_discount_price),  0)
                       + COALESCE(SUM(voucher_nominal),       0) AS diskon')
            )
            ->first();
 
        // HPP
        $hpp = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(transaction_details.buy_price * transaction_details.quantity), 0) AS total_hpp')
            ->first();
 
        // Retur penjualan
        $retur = Transaction::where('type', 'retur')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(total_sum_price), 0) AS total_retur')
            ->first();
 
        // Pembelian (loading)
        $pembelian = GoodLoading::whereNull('deleted_at')
            ->whereBetween('loading_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(total_item_price), 0) AS total_pembelian')
            ->first();
 
        $omzetBruto     = (float) $penjualan->omzet;
        $totalDiskon    = (float) $penjualan->diskon;
        $totalRetur     = (float) $retur->total_retur;
        $omzetBersih    = $omzetBruto - $totalDiskon - $totalRetur;
        $totalHpp       = (float) $hpp->total_hpp;
        $labaKotor      = $omzetBersih - $totalHpp;
        $totalPembelian = (float) $pembelian->total_pembelian;
 
        return compact(
            'omzetBruto', 'totalDiskon', 'totalRetur',
            'omzetBersih', 'totalHpp', 'labaKotor', 'totalPembelian'
        );
    }
 
    /**
     * Metode pembayaran
     */
    public function getSalesByPaymentMethod($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = Transaction::join('accounts', 'accounts.code', '=', 'transactions.payment')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->select(
                'accounts.code AS kode_akun',
                'accounts.name AS metode_bayar',
                DB::raw('COUNT(transactions.id)                   AS jumlah_transaksi'),
                DB::raw('COALESCE(SUM(transactions.total_sum_price), 0) AS total_omzet')
            );
 
        if ($start) {
            $query->whereBetween('transactions.created_at', [$start, $end]);
        }
 
        return $query
            ->groupBy('accounts.code', 'accounts.name')
            ->orderBy('total_omzet', 'desc')
            ->get();
    }
 
    /**
     * Efektivitas voucher
     */
    public function getVoucherUsageReport($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = Transaction::whereNotNull('voucher')
            ->where('type', 'normal')
            ->whereNull('deleted_at')
            ->select(
                'voucher AS kode_voucher',
                DB::raw('COUNT(*)                                   AS jumlah_dipakai'),
                DB::raw('COALESCE(SUM(voucher_nominal), 0)          AS total_diskon_voucher'),
                DB::raw('COALESCE(SUM(total_sum_price), 0)          AS total_omzet_terkait')
            );
 
        if ($start) {
            $query->whereBetween('created_at', [$start, $end]);
        }
 
        return $query
            ->groupBy('voucher')
            ->orderBy('jumlah_dipakai', 'desc')
            ->get();
    }
 
    /**
     * Jurnal Umum
     */
    public function getGeneralJournal($startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
 
        $query = Journal::join('accounts AS debit_acc',  'debit_acc.id',  '=', 'journals.debit_account_id')
            ->join('accounts AS credit_acc', 'credit_acc.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->select(
                'journals.id',
                'journals.journal_date',
                'journals.name         AS keterangan',
                'journals.type         AS tipe',
                'debit_acc.code        AS kode_debit',
                'debit_acc.name        AS akun_debit',
                'journals.debit',
                'credit_acc.code       AS kode_kredit',
                'credit_acc.name       AS akun_kredit',
                'journals.credit'
            );
 
        if ($start) {
            $query->whereBetween('journals.journal_date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }
 
        return $query->orderBy('journals.journal_date', 'asc')->get();
    }
 
    /**
     * Neraca Saldo
     */
    public function getTrialBalance($scaleLedgerId = null)
    {
        $query = ScaleLedger::join('accounts', 'accounts.id', '=', 'scale_ledgers.account_id')
            ->whereNull('scale_ledgers.deleted_at')
            ->select(
                'accounts.code       AS kode_akun',
                'accounts.name       AS nama_akun',
                'accounts.type       AS tipe_akun',
                'accounts.group      AS kelompok',
                'scale_ledgers.start_date',
                'scale_ledgers.end_date',
                'scale_ledgers.initial  AS saldo_awal',
                'scale_ledgers.ongoing  AS mutasi',
                'scale_ledgers.current  AS saldo_akhir'
            );
 
        if ($scaleLedgerId) {
            $query->where('scale_ledgers.id', $scaleLedgerId);
        }
 
        return $query->orderBy('accounts.code', 'asc')->get();
    }
}
 