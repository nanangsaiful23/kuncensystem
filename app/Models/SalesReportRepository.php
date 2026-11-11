<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan Penjualan (menu "Laporan Penjualan").
 *
 * One definition everywhere:
 *  - a sale is a customer transaction of type 'normal' or 'retur' (a sale
 *    with returned items) that is not cancelled ('not valid', 'deleted') or
 *    deleted. Internal transactions (type = account code) are not sales.
 *  - sold lines are the details that are not 'retur'; returned lines are
 *    the details of type 'retur'.
 *  - profit of a line = sum_price (after the line discount) - buy_price x qty.
 * Checked against the journals: penjualan bersih (bruto - diskon - retur)
 * is within a few ten thousand rupiah of account 4101 per month (the rest
 * are cancellations booked on another day).
 *
 * Lines are selected through the id range of the period's transactions, so
 * MySQL reads only that part of transaction_details (there is no index on
 * transactions.created_at).
 */
class SalesReportRepository
{
    private array $idRanges = [];

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** [start, end] as Carbon, or [null, null] when a date is missing. */
    private function parseDateRange($startDate, $endDate): array
    {
        if (! $startDate || ! $endDate) {
            return [null, null];
        }

        return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
    }

    /** Same as Good::getFullName(): type + name. */
    private function formatGoodName(?string $tipe, ?string $nama): string
    {
        $tipe = trim((string) $tipe);
        $nama = (string) $nama;

        return ($tipe === '' || $tipe === '-') ? ucfirst($nama) : ucfirst(strtolower($tipe) . ' ' . $nama);
    }

    /** Lowest and highest transaction id created in the period. */
    private function idRange(Carbon $start, Carbon $end): array
    {
        $key = $start->toDateTimeString() . '|' . $end->toDateTimeString();
        if (! isset($this->idRanges[$key])) {
            $r = DB::table('transactions')->whereBetween('created_at', [$start, $end])->selectRaw('MIN(id) a, MAX(id) b')->first();
            $this->idRanges[$key] = [(int) ($r->a ?? 0), (int) ($r->b ?? 0)];
        }

        return $this->idRanges[$key];
    }

    /** Customer sales (see class comment), optionally in a period. */
    private function sales(?Carbon $start = null, ?Carbon $end = null)
    {
        $q = Transaction::query()
            ->whereIn('transactions.type', ['normal', 'retur'])
            ->whereNull('transactions.deleted_at');
        if ($start) {
            $q->whereBetween('transactions.id', $this->idRange($start, $end))
              ->whereBetween('transactions.created_at', [$start, $end]);
        }

        return $q;
    }

    /** Sold lines ($retur = false) or returned lines ($retur = true) of the sales. */
    private function lines(?Carbon $start, ?Carbon $end, bool $retur = false)
    {
        $q = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereIn('transactions.type', ['normal', 'retur'])
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at');
        $retur
            ? $q->where('transaction_details.type', 'retur')
            : $q->whereRaw("COALESCE(transaction_details.type, '') <> 'retur'");
        if ($start) {
            $q->whereBetween('transaction_details.transaction_id', $this->idRange($start, $end))
              ->whereBetween('transactions.created_at', [$start, $end]);
        }

        return $q;
    }

    private const PROFIT = 'transaction_details.sum_price - transaction_details.buy_price * transaction_details.quantity';
    private const HPP = 'transaction_details.buy_price * transaction_details.quantity';

    /** Money and HPP of sales grouped by an expression of transactions.created_at (or one total). */
    private function salesTotals(?Carbon $start, ?Carbon $end, ?string $groupExpr = null): Collection
    {
        $g = $groupExpr ?? "'all'";
        $sold = $this->lines($start, $end)->selectRaw("$g AS k, SUM(transaction_details.sum_price) omzet, SUM(" . self::HPP . ") hpp")
            ->groupByRaw($g)->get()->keyBy('k');
        $ret = $this->lines($start, $end, true)->selectRaw("$g AS k, SUM(transaction_details.sum_price) retur, SUM(" . self::HPP . ") hpp_retur")
            ->groupByRaw($g)->get()->keyBy('k');
        $trx = $this->sales($start, $end)->selectRaw("$g AS k, COUNT(*) n,
                SUM(COALESCE(transactions.total_discount_price, 0) + COALESCE(transactions.voucher_nominal, 0)) diskon")
            ->groupByRaw($g)->get()->keyBy('k');

        return $sold->keys()->merge($ret->keys())->merge($trx->keys())->unique()->sort()->values()->map(function ($k) use ($sold, $ret, $trx) {
            $omzet = (float) ($sold[$k]->omzet ?? 0);
            $diskon = (float) ($trx[$k]->diskon ?? 0);
            $retur = (float) ($ret[$k]->retur ?? 0);
            $hpp = (float) ($sold[$k]->hpp ?? 0) - (float) ($ret[$k]->hpp_retur ?? 0);

            return (object) [
                'k' => $k, 'omzet' => $omzet, 'diskon' => $diskon, 'retur' => $retur,
                'bersih' => $omzet - $diskon - $retur, 'hpp' => $hpp,
                'laba' => $omzet - $diskon - $retur - $hpp, 'n' => (int) ($trx[$k]->n ?? 0),
            ];
        });
    }

    // =========================================================================
    // A. PENJUALAN
    // =========================================================================

    /** Totals of the period. totalLaba = laba kotor (penjualan bersih - HPP). */
    public function getSalesSummary($startDate = null, $endDate = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
        $t = $this->salesTotals($start, $end)->first();

        $totalOmzet     = $t->omzet ?? 0.0;
        $totalDiskon    = $t->diskon ?? 0.0;
        $totalRetur     = $t->retur ?? 0.0;
        $omzetBersih    = $t->bersih ?? 0.0;
        $totalHpp       = $t->hpp ?? 0.0;
        $totalLaba      = $t->laba ?? 0.0;
        $totalTransaksi = $t->n ?? 0;
        $rataRata       = $totalTransaksi > 0 ? $omzetBersih / $totalTransaksi : 0;

        return compact('totalOmzet', 'totalDiskon', 'totalRetur', 'omzetBersih', 'totalHpp', 'totalLaba', 'totalTransaksi', 'rataRata');
    }

    /** Per day: omzet (bruto), total_diskon (diskon + voucher + retur), omzet_bersih, laba. */
    public function getDailySalesTrend($startDate, $endDate): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        return $this->salesTotals($start, $end, 'DATE(transactions.created_at)')->map(fn ($r) => (object) [
            'tanggal' => $r->k, 'omzet' => $r->omzet, 'total_diskon' => $r->diskon + $r->retur,
            'omzet_bersih' => $r->bersih, 'laba' => $r->laba, 'jumlah_transaksi' => $r->n,
        ]);
    }

    /** Per month of a year, same columns as the daily trend. */
    public function getMonthlySalesTrend($year = null): Collection
    {
        $year = (int) ($year ?? now()->year);
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        return $this->salesTotals($start, $end, 'MONTH(transactions.created_at)')->map(fn ($r) => (object) [
            'tahun' => $year, 'bulan' => (int) $r->k, 'omzet' => $r->omzet, 'total_diskon' => $r->diskon + $r->retur,
            'omzet_bersih' => $r->bersih, 'laba' => $r->laba, 'jumlah_transaksi' => $r->n,
        ]);
    }

    /** Per payment method (cash = Tunai, transfer = Transfer). total_omzet = what the customer paid for. */
    public function getSalesByPaymentMethod($startDate = null, $endDate = null): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);
        $names = DB::table('accounts')->pluck('name', 'code');

        return $this->sales($start, $end)
            ->selectRaw('transactions.payment, COUNT(*) jumlah_transaksi, COALESCE(SUM(transactions.total_sum_price), 0) total_omzet')
            ->groupBy('transactions.payment')->orderByDesc('total_omzet')->get()
            ->map(fn ($r) => (object) [
                'kode_akun' => $r->payment,
                'metode_bayar' => ['cash' => 'Tunai', 'transfer' => 'Transfer'][$r->payment] ?? ($names[$r->payment] ?? $r->payment),
                'jumlah_transaksi' => (int) $r->jumlah_transaksi,
                'total_omzet' => (float) $r->total_omzet,
            ]);
    }

    public function getVoucherUsageReport($startDate = null, $endDate = null): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        return $this->sales($start, $end)
            ->whereNotNull('transactions.voucher')->where('transactions.voucher', '<>', '')
            ->selectRaw('transactions.voucher kode_voucher, COUNT(*) jumlah_dipakai,
                COALESCE(SUM(transactions.voucher_nominal), 0) total_diskon_voucher,
                COALESCE(SUM(transactions.total_sum_price), 0) total_omzet_terkait')
            ->groupBy('transactions.voucher')->orderByDesc('jumlah_dipakai')->get();
    }

    // =========================================================================
    // B. PRODUK
    // =========================================================================

    /** Best sellers per good + unit (qty in that unit). */
    public function getTopSellingGoods($startDate = null, $endDate = null, $limit = 10): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $agg = $this->lines($start, $end)
            ->selectRaw('transaction_details.good_unit_id, SUM(transaction_details.quantity) total_qty,
                SUM(transaction_details.sum_price) total_omzet, SUM(' . self::PROFIT . ') total_laba,
                AVG(transaction_details.selling_price) harga_jual_rata, AVG(transaction_details.buy_price) harga_beli_rata')
            ->groupBy('transaction_details.good_unit_id')->orderByDesc('total_omzet')->limit($limit)->get();

        $units = GoodUnit::withTrashed()->whereIn('good_units.id', $agg->pluck('good_unit_id'))
            ->join('units', 'units.id', '=', 'good_units.unit_id')
            ->join('goods', 'goods.id', '=', 'good_units.good_id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->select('good_units.id AS good_unit_id', 'good_units.good_id', 'goods.name AS nama', 'types.name AS tipe', 'goods.code',
                'units.name AS satuan', 'units.quantity AS unit_qty', 'categories.name AS kategori', 'brands.name AS merk')
            ->get()->keyBy('good_unit_id');

        return $agg->map(function ($a) use ($units) {
            $u = $units->get($a->good_unit_id);
            $omzet = (float) $a->total_omzet;
            $laba = (float) $a->total_laba;

            return (object) [
                'good_unit_id' => $a->good_unit_id, 'good_id' => $u->good_id ?? null,
                'kode_produk' => $u->code ?? '-', 'nama_produk' => $u ? $this->formatGoodName($u->tipe, $u->nama) : '(dihapus)',
                'satuan' => $u->satuan ?? '-', 'unit_qty_konversi' => $u->unit_qty ?? 1,
                'kategori' => $u->kategori ?? '-', 'merk' => $u->merk ?? '-',
                'total_qty' => (float) $a->total_qty, 'total_omzet' => $omzet, 'total_laba' => $laba,
                'margin_pct' => $omzet > 0 ? round($laba / $omzet * 100, 2) : 0,
                'harga_jual_rata' => (float) $a->harga_jual_rata, 'harga_beli_rata' => (float) $a->harga_beli_rata,
            ];
        })->values();
    }

    /** Per good, all units together; qty in the smallest unit (real_quantity). */
    public function getSalesPerGood($startDate = null, $endDate = null, $limit = 20): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $agg = $this->lines($start, $end)
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->selectRaw('good_units.good_id, SUM(transaction_details.real_quantity) total_qty_dasar,
                SUM(transaction_details.sum_price) total_omzet, SUM(' . self::PROFIT . ') total_laba,
                COUNT(DISTINCT transaction_details.good_unit_id) jumlah_varian_unit')
            ->groupBy('good_units.good_id')->orderByDesc('total_omzet')->limit($limit)->get();

        $goods = Good::withTrashed()->whereIn('goods.id', $agg->pluck('good_id'))
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->select('goods.id', 'goods.code', 'goods.name', 'types.name AS tipe', 'categories.name AS kategori', 'brands.name AS merk')
            ->get()->keyBy('id');
        $smallest = GoodUnit::join('units', 'units.id', '=', 'good_units.unit_id')
            ->whereIn('good_units.good_id', $agg->pluck('good_id'))
            ->orderByRaw('units.quantity + 0')->get(['good_units.good_id', 'units.name'])
            ->unique('good_id')->keyBy('good_id');

        return $agg->map(function ($a) use ($goods, $smallest) {
            $g = $goods->get($a->good_id);
            $omzet = (float) $a->total_omzet;
            $laba = (float) $a->total_laba;

            return (object) [
                'good_id' => $a->good_id, 'kode_produk' => $g->code ?? '-',
                'nama_produk' => $g ? $this->formatGoodName($g->tipe, $g->name) : '(dihapus)',
                'kategori' => $g->kategori ?? '-', 'merk' => $g->merk ?? '-',
                'satuan_dasar' => $smallest->get($a->good_id)->name ?? '-',
                'total_qty_dasar' => (float) $a->total_qty_dasar, 'jumlah_varian_unit' => (int) $a->jumlah_varian_unit,
                'total_omzet' => $omzet, 'total_laba' => $laba,
                'margin_pct' => $omzet > 0 ? round($laba / $omzet * 100, 2) : 0,
            ];
        })->values();
    }

    public function getSalesByCategory($startDate = null, $endDate = null): Collection
    {
        return $this->salesByGoodColumn($startDate, $endDate, 'categories', 'goods.category_id', 'category_id', 'kategori');
    }

    public function getSalesByBrand($startDate = null, $endDate = null): Collection
    {
        return $this->salesByGoodColumn($startDate, $endDate, 'brands', 'goods.brand_id', 'brand_id', 'merk');
    }

    private function salesByGoodColumn($startDate, $endDate, string $table, string $fk, string $idAlias, string $nameAlias): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $agg = $this->lines($start, $end)
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->join('goods', 'goods.id', '=', 'good_units.good_id')
            ->selectRaw("$fk AS ref_id, SUM(transaction_details.quantity) total_qty, SUM(transaction_details.sum_price) total_omzet,
                SUM(" . self::PROFIT . ") total_laba, COUNT(DISTINCT transaction_details.transaction_id) jumlah_transaksi")
            ->groupBy($fk)->orderByDesc('total_omzet')->get();
        $names = DB::table($table)->whereIn('id', $agg->pluck('ref_id')->filter())->pluck('name', 'id');

        return $agg->map(fn ($a) => (object) [
            $idAlias => $a->ref_id, $nameAlias => $names[$a->ref_id] ?? '(tanpa ' . $nameAlias . ')',
            'total_qty' => (float) $a->total_qty, 'total_omzet' => (float) $a->total_omzet,
            'total_laba' => (float) $a->total_laba, 'jumlah_transaksi' => (int) $a->jumlah_transaksi,
        ]);
    }

    // =========================================================================
    // C. PELANGGAN
    // =========================================================================

    /** Members with the highest sales; "Non Member" (id 1) is left out. */
    public function getTopMembers($startDate = null, $endDate = null, $limit = 10): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $agg = $this->sales($start, $end)
            ->whereNotNull('transactions.member_id')->where('transactions.member_id', '<>', 1)
            ->selectRaw('transactions.member_id, COUNT(*) jumlah_transaksi, COALESCE(SUM(transactions.total_sum_price), 0) total_omzet,
                COALESCE(SUM(COALESCE(transactions.total_discount_price, 0) + COALESCE(transactions.voucher_nominal, 0)), 0) total_diskon,
                MAX(transactions.created_at) transaksi_terakhir')
            ->groupBy('transactions.member_id')->orderByDesc('total_omzet')->limit($limit)->get();
        $members = Member::withTrashed()->whereIn('id', $agg->pluck('member_id'))->get(['id', 'name', 'store_name', 'phone_number'])->keyBy('id');

        return $agg->map(function ($a) use ($members) {
            $m = $members->get($a->member_id);

            return (object) [
                'member_id' => $a->member_id, 'nama_member' => $m->name ?? '(dihapus)', 'nama_toko' => $m->store_name ?? '-',
                'telepon' => $m->phone_number ?? '-', 'jumlah_transaksi' => (int) $a->jumlah_transaksi,
                'total_omzet' => (float) $a->total_omzet, 'total_diskon' => (float) $a->total_diskon,
                'transaksi_terakhir' => $a->transaksi_terakhir,
            ];
        });
    }

    /**
     * Piutang per member: credit sales (paid less than the total) minus the
     * payments (the down payment of a credit sale is also a payment).
     */
    public function getMemberReceivables(): Collection
    {
        $tagihan = $this->sales()
            ->whereNotNull('transactions.member_id')
            ->where(function ($q) {
                $q->whereNotIn('transactions.payment', ['cash', 'transfer'])
                  ->orWhereRaw('COALESCE(transactions.money_paid, 0) < transactions.total_sum_price');
            })
            ->selectRaw('transactions.member_id, COALESCE(SUM(transactions.total_sum_price), 0) total_tagihan')
            ->groupBy('transactions.member_id')->get()->keyBy('member_id');
        if ($tagihan->isEmpty()) {
            return collect();
        }

        $bayar = PiutangPayment::whereIn('member_id', $tagihan->keys())->whereNull('deleted_at')
            ->selectRaw('member_id, COALESCE(SUM(money), 0) total')->groupBy('member_id')->pluck('total', 'member_id');
        $members = Member::withTrashed()->whereIn('id', $tagihan->keys())->get(['id', 'name', 'phone_number'])->keyBy('id');

        return $tagihan->map(function ($t) use ($bayar, $members) {
            $m = $members->get($t->member_id);
            $dibayar = (float) ($bayar[$t->member_id] ?? 0);

            return (object) [
                'member_id' => $t->member_id, 'nama_member' => $m->name ?? '(dihapus)', 'telepon' => $m->phone_number ?? '-',
                'total_tagihan' => (float) $t->total_tagihan, 'total_pembayaran' => $dibayar,
                'sisa_piutang' => (float) $t->total_tagihan - $dibayar,
            ];
        })->sortByDesc('sisa_piutang')->values();
    }

    // =========================================================================
    // D. PEMBELIAN & STOK
    // =========================================================================

    public function getPurchaseSummaryByDistributor($startDate = null, $endDate = null): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $q = GoodLoading::join('distributors', 'distributors.id', '=', 'good_loadings.distributor_id')
            ->whereNull('good_loadings.deleted_at')
            ->selectRaw('distributors.id distributor_id, distributors.name nama_distributor, distributors.location lokasi,
                COUNT(good_loadings.id) jumlah_loading, COALESCE(SUM(good_loadings.total_item_price), 0) total_pembelian');
        if ($start) {
            $q->whereBetween('good_loadings.loading_date', [$start->toDateString(), $end->toDateString()]);
        }

        return $q->groupBy('distributors.id', 'distributors.name', 'distributors.location')->orderByDesc('total_pembelian')->get();
    }

    /**
     * Stock value per good, as the inventory value elsewhere (Neraca, audit):
     * goods.last_stock is in the base unit (goods.base_unit_id), so it is
     * valued with the price of that unit. Before, the price of the smallest
     * unit was used, wrong for the ~300 goods whose base unit is a pack.
     */
    public function getCurrentStockValuation(): Collection
    {
        return Good::join('good_units AS gu', 'gu.id', '=', 'goods.base_unit_id')
            ->join('units AS u', 'u.id', '=', 'gu.unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'goods.brand_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.last_stock', '<>', 0)
            ->selectRaw('goods.id good_id, goods.code kode, goods.name nama_barang, types.name tipe, categories.name kategori, brands.name merk,
                u.name satuan, goods.last_stock stok_akhir, gu.buy_price harga_beli, gu.selling_price harga_jual,
                goods.last_stock * gu.buy_price nilai_hpp, goods.last_stock * gu.selling_price nilai_jual,
                goods.last_stock * (gu.selling_price - gu.buy_price) potensi_laba')
            ->orderByDesc('nilai_hpp')->get()
            ->map(function ($r) {
                $r->nama_barang = $this->formatGoodName($r->tipe, $r->nama_barang);
                return $r;
            });
    }

    /** Goods returned to distributors (retur_items) in the period. */
    public function getReturSummary($startDate = null, $endDate = null): Collection
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $q = ReturItem::join('goods', 'goods.id', '=', 'retur_items.good_id')
            ->join('good_units', 'good_units.id', '=', 'retur_items.good_unit_id')
            ->join('units', 'units.id', '=', 'good_units.unit_id')
            ->leftJoin('distributors', 'distributors.id', '=', 'retur_items.last_distributor_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->whereNull('retur_items.deleted_at')
            ->select('distributors.name AS distributor', 'goods.name AS nama_barang', 'types.name AS tipe', 'units.name AS satuan',
                'retur_items.returned_type AS jenis_retur', 'retur_items.returned_date', 'good_units.buy_price AS harga_beli');
        if ($start) {
            $q->whereBetween('retur_items.returned_date', [$start->toDateString(), $end->toDateString()]);
        }

        return $q->orderByDesc('retur_items.returned_date')->get()->map(function ($r) {
            $r->nama_barang = $this->formatGoodName($r->tipe, $r->nama_barang);
            return $r;
        });
    }
}
