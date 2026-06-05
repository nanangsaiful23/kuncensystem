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
     * Produk terlaris berdasarkan qty & omzet
     *
     * ✅ STRATEGI OPTIMASI — 2 TAHAP:
     *
     *   Tahap 1 → query RINGAN hanya di 3 tabel inti (transaction_details,
     *             transactions, good_units).  Hasilkan top-N good_id beserta
     *             angka agregasinya.  Jumlah baris yang diproses MySQL jauh
     *             lebih kecil karena tidak ada LEFT JOIN ke goods/categories/
     *             brands dulu.
     *
     *   Tahap 2 → ambil detail (nama barang, kategori, merek) hanya untuk
     *             N good_id hasil tahap 1, bukan seluruh tabel.
     *
     * Hasilnya identik dengan query JOIN besar, tapi jauh lebih cepat.
     */
    public function getTopSellingGoods($startDate = null, $endDate = null, $limit = 10)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        // ── TAHAP 1: agregasi di tabel transaksi saja ─────────────────────
        $aggQuery = TransactionDetail::join(
                'transactions', 'transactions.id', '=', 'transaction_details.transaction_id'
            )
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('transactions.type', 'normal')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->select(
                'good_units.good_id',
                DB::raw('SUM(transaction_details.quantity)                                   AS total_qty'),
                DB::raw('SUM(transaction_details.sum_price)                                  AS total_omzet'),
                DB::raw('SUM((transaction_details.selling_price
                           - transaction_details.buy_price)
                           * transaction_details.quantity)                                   AS total_laba'),
                DB::raw('AVG(transaction_details.selling_price)                              AS harga_rata')
            );

        if ($start) {
            $aggQuery->whereBetween('transactions.created_at', [$start, $end]);
        }

        $aggregated = $aggQuery
            ->groupBy('good_units.good_id')
            ->orderBy('total_omzet', 'desc')
            ->limit($limit)
            ->get()
            ->keyBy('good_id');        // [good_id => row]  O(1) lookup

        if ($aggregated->isEmpty()) {
            return collect();
        }

        // ── TAHAP 2: ambil master data hanya untuk good_id yang relevan ───
        $goodIds = $aggregated->keys()->all();

        $goods = Good::whereIn('id', $goodIds)
            ->whereNull('deleted_at')
            ->with([
                // eager-load relasi; Laravel auto-batches menjadi 1 query per relasi
                'category:id,name',
                'brand:id,name',
                'good_units' => function ($q) {
                    $q->whereNull('deleted_at')
                      ->with('unit:id,name')
                      ->orderBy('selling_price', 'asc')
                      ->limit(1);   // ambil satuan dasar (harga terendah)
                },
            ])
            ->get()
            ->keyBy('id');

        // ── Gabungkan hasil kedua tahap ────────────────────────────────────
        return $aggregated
            ->map(function ($agg) use ($goods) {
                $good     = $goods->get($agg->good_id);
                $goodUnit = $good ? $good->good_units->first() : null;

                return (object) [
                    'good_id'      => $agg->good_id,
                    'nama_produk'  => $good ? $good->name : '(dihapus)',
                    'satuan'       => ($goodUnit && $goodUnit->unit) ? $goodUnit->unit->name : '-',
                    'kategori'     => ($good && $good->category) ? $good->category->name : '-',
                    'merk'         => ($good && $good->brand) ? $good->brand->name : '-',
                    'total_qty'    => $agg->total_qty,
                    'total_omzet'  => $agg->total_omzet,
                    'total_laba'   => $agg->total_laba,
                    'harga_rata'   => $agg->harga_rata,
                ];
            })
            ->sortByDesc('total_omzet')
            ->values();
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
                'nama_member'        => $m ? $m->name : '(dihapus)',
                'nama_toko'          => $m ? $m->store_name : '-',
                'telepon'            => $m ? $m->phone_number : '-',
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
            $b        = $bayar->get($t->member_id);
            $dibayar  = (float) ($b ? $b->total_pembayaran : 0);
            $tagihan  = (float) $t->total_tagihan;

            return (object) [
                'member_id'        => $t->member_id,
                'nama_member'      => $m ? $m->name : '(dihapus)',
                'telepon'          => $m ? $m->phone_number : '-',
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
     * Valuasi stok saat ini
     * ✅ Optimasi: select kolom minimum, tambah chunk jika data besar
     */
    public function getCurrentStockValuation()
    {
        return Good::join('good_units', 'good_units.good_id', '=', 'goods.id')
            ->join('units',     'units.id',      '=', 'good_units.unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->whereNull('goods.deleted_at')
            ->whereNull('good_units.deleted_at')
            ->select(
                'goods.id            AS good_id',
                'goods.code          AS kode',
                'goods.name          AS nama_barang',
                'categories.name     AS kategori',
                'brands.name         AS merk',
                'units.name          AS satuan',
                'goods.last_stock    AS stok_akhir',
                'good_units.buy_price     AS harga_beli',
                'good_units.selling_price AS harga_jual',
                DB::raw('goods.last_stock * good_units.buy_price     AS nilai_hpp'),
                DB::raw('goods.last_stock * good_units.selling_price AS nilai_jual')
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