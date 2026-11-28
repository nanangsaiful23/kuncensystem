<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Good;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\Season;
use App\Models\TransactionDetail;
use App\Services\SkemaCekObral;

class GoodMovementRepository
{
    // =========================================================================
    // KONFIGURASI THRESHOLD
    // =========================================================================

    /**
     * Fast moving = minimal FAST_MOVING_MIN_TRX transaksi per FAST_BASE_DAYS
     * hari, disesuaikan dengan panjang periode filter (periode 30 hari butuh
     * 3 transaksi, 1 tahun butuh 41). Dulu angkanya mutlak 10 transaksi, jadi
     * arti "fast" berubah-ubah mengikuti panjang periode yang dipilih.
     */
    const FAST_MOVING_MIN_TRX  = 10;
    const FAST_BASE_DAYS       = 90;
    const FAST_MIN_TRX_FLOOR   = 2;
    const ACTIVE_LAST_TRX_DAYS = 30;
    const DEAD_STOCK_DAYS      = 90;
    const REORDER_STOCK_MIN    = 10;

    /** Barang yang di-loading kurang dari sekian hari lalu dan belum laku sesudahnya: "Baru Masuk", belum bisa dinilai. */
    const NEW_GOOD_DAYS        = 30;

    /**
     * ── ANTI SALAH-DISCONTINUE ──────────────────────────────────────────
     * Klasifikasi "dead" tidak boleh hanya berdasarkan "belum ada transaksi
     * di periode filter": barang yang historisnya laku tapi sedang kehabisan
     * stok bisa kelihatan "mati". Karena itu dihitung juga riwayat
     * LONG_TERM_MONTHS bulan terakhir (independen dari filter). Barang yang
     * rata-rata laku minimal sekali sebulan (SAFE_LIFETIME_TRX transaksi dalam
     * jendela itu) masuk "Perlu Dicek", bukan Dead Stock.
     *
     * Dulu batasnya 5 transaksi setahun: barang yang laku 5 kali setahun lalu
     * lalu mati tetap terlindungi. Barang musiman sekarang ditangani lewat
     * menu Musim (dan saran "Kandidat Musiman"), bukan lewat batas ini.
     */
    const LONG_TERM_MONTHS     = 12;
    const SAFE_LIFETIME_TRX    = 12;

    /**
     * Saran "Kandidat Musiman": barang dengan minimal SEASON_MIN_TRX transaksi
     * dalam 12 bulan yang SEASON_PEAK_SHARE atau lebih penjualannya (qty)
     * terkumpul dalam SEASON_PEAK_MONTHS bulan berurutan. Tidak dihitung:
     * - puncak yang memuat bulan ini atau bulan lalu (musimnya belum selesai,
     *   atau barangnya baru mulai dijual);
     * - barang yang sesudah puncaknya tidak laku sama sekali DAN stoknya habis:
     *   itu tanda kehabisan stok, bukan musim (di data toko, ±40% kandidat
     *   dengan aturan longgar adalah barang harian yang stoknya habis).
     */
    const SEASON_MIN_TRX       = 8;
    const SEASON_PEAK_SHARE    = 0.7;
    const SEASON_PEAK_MONTHS   = 2;

    /**
     * Tanda "✔ dicek" di baris barang: stok fisiknya dihitung (Sidak Stok /
     * Stock Opname) dalam CHECKED_SHOW_DAYS hari terakhir. Barang yang belum
     * dicek CHECKED_FRESH_DAYS hari diberi peringatan saat mau diobral, dan
     * tidak dimasukkan lagi ke "Sidak dead stock" kalau sudah dicek.
     */
    const CHECKED_SHOW_DAYS    = 90;
    const CHECKED_FRESH_DAYS   = 30;

    /** Status yang bisa difilter lewat pill (selain 'all', 'discontinued', 'kandidat_musim'). */
    const STATUSES = ['fast', 'slow', 'dead', 'at_risk', 'kosong', 'baru', 'musiman', 'obral'];

    // =========================================================================
    // QUERY UTAMA
    // =========================================================================

    /**
     * Ambil data pergerakan barang dalam periode.
     *
     * Status filter: all | fast | slow | dead | at_risk | kosong | baru |
     * musiman | obral | kandidat_musim | discontinued
     *
     * Barang dengan is_discontinued=1 HANYA muncul saat status='discontinued'.
     * Ringkasan (jumlah per status) selalu dihitung dari SEMUA barang, bukan
     * hanya status yang sedang dibuka.
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
        // Jumlah hari inklusif. Carbon 3 mengembalikan pecahan (90.99999...).
        $days  = max(1, (int) round($start->diffInDays($end)));
        $now   = Carbon::now();

        if ($status === 'discontinued') {
            return $this->getDiscontinuedData($kategori, $sortBy, $sortDir);
        }

        $adaKolomBaru = Season::tabelAda();
        $minFast      = $this->minFastTrx($days);

        // ── TAHAP 1: penjualan per good_id dalam periode ──────────────────────
        // Penjualan = transaksi 'normal' dan 'retur' (penjualan yang juga ada
        // barang returnya) yang tidak dibatalkan; baris retur tidak dihitung.
        // Qty dijumlah dalam satuan terkecil (real_quantity) lalu dibagi isi
        // satuan dasar barang, supaya sama satuannya dengan goods.last_stock.
        $salesAgg = $this->salesPerGood($start, $end)
            ->selectRaw('MAX(transactions.created_at) AS last_transaction_at')
            ->get()->keyBy('good_id');

        // ── TAHAP 1a: riwayat 12 bulan per bulan (independen dari filter) ─────
        //    untuk "riwayat bagus", tanggal laku terakhir, dan pola musiman.
        $longTerm = $this->longTermPerGood($now);

        // ── TAHAP 2: barang aktif (belum discontinued) dengan satuan dasarnya ─
        $goodsRaw = $this->activeGoods($kategori, $adaKolomBaru);

        // ── TAHAP 2a: obral — terjual sejak obral dimulai ─────────────────────
        $obralSold = $adaKolomBaru ? $this->clearanceSales($goodsRaw, $now) : collect();

        // ── TAHAP 2a': kapan stok fisiknya terakhir dihitung ─────────────────
        $dicek = $this->lastCheckedPerGood($now->copy()->subDays(self::CHECKED_SHOW_DAYS));

        // ── TAHAP 2b: musim — status tiap musim hari ini, dan penjualan musim
        //    lalu untuk musim yang sebentar lagi mulai (acuan siapkan stok) ──
        $musim         = $adaKolomBaru ? Season::statusSemua($now) : collect();
        $musimLaluSold = [];
        foreach ($musim as $sid => $m) {
            if ($m['status'] === Season::PERSIAPAN && $m['lalu']) {
                $musimLaluSold[$sid] = $this->salesPerGood($m['lalu'][0], $m['lalu'][1])->get()->keyBy('good_id');
            }
        }

        // ── TAHAP 3: Gabungkan & klasifikasi ──────────────────────────────────
        $all = [];

        foreach ($goodsRaw as $good) {
            $gid        = $good->good_id;
            $agg        = $salesAgg->get($gid);
            $lt         = $longTerm[$gid] ?? null;
            $baseQty    = (float) $good->base_qty > 0 ? (float) $good->base_qty : 1;
            $totalTrx   = $agg ? (int)   $agg->total_transaksi   : 0;
            $totalQty   = $agg ? round((float) $agg->total_qty_terjual / $baseQty, 2) : 0;
            $totalOmzet = $agg ? (float) $agg->total_omzet        : 0;
            $totalLaba  = $agg ? (float) $agg->total_laba         : 0;
            $totalTrxLT = $lt ? $lt['trx'] : 0;
            $totalQtyLT = $lt ? round($lt['qty'] / $baseQty, 2) : 0;

            // Laku terakhir yang sebenarnya (bukan hanya di dalam periode filter),
            // lalu goods.last_transaction sebagai cadangan (kolom itu ikut berubah
            // karena transaksi internal/retur, jadi hanya dipakai kalau tidak ada data).
            $lastTrxAt = $this->latest($agg->last_transaction_at ?? null, $lt['last'] ?? null)
                      ?? $good->last_transaction_goods;
            $stok      = (float) $good->stok_sekarang;
            $hargaBeli = (float) ($good->harga_beli  ?? 0);
            $hargaJual = (float) ($good->harga_jual  ?? 0);

            $daysSinceTrx = $lastTrxAt
                ? (int) Carbon::parse($lastTrxAt)->diffInDays($now)
                : 999;
            $daysSinceLoading = $good->last_loading_goods
                ? (int) Carbon::parse($good->last_loading_goods)->startOfDay()->diffInDays($now->copy()->startOfDay())
                : null;

            $avgQtyPerDay = $days > 0 ? round($totalQty / $days, 2) : 0;
            $daysOfStock  = ($avgQtyPerDay > 0 && $stok > 0)
                ? (int) round($stok / $avgQtyPerDay)
                : ($stok > 0 ? 9999 : 0);

            // Stok minus tidak menahan modal: dihitung 0 (dulu ikut mengurangi total).
            $nilaiStok = max(0, $stok) * $hargaBeli;
            $margin    = $hargaJual > 0
                ? round(($hargaJual - $hargaBeli) / $hargaJual * 100, 1)
                : 0;

            $isObral  = $adaKolomBaru && (int) $good->is_clearance === 1;
            $seasonId = $adaKolomBaru ? Season::efektif($good->season_good, $good->season_type) : null;
            $ms       = $seasonId ? $musim->get($seasonId) : null;

            $classification = $this->classify($totalTrx, $daysSinceTrx, $stok, $totalTrxLT, $minFast, $daysSinceLoading);
            $recommendation = $this->recommend($classification, $stok, $daysOfStock, $totalTrx, $margin);
            $musimLaluQty   = null;

            if ($isObral) {
                $classification = ['status' => 'obral', 'label' => 'Sedang Obral', 'color' => 'pink'];
                $recommendation = ['action' => 'clearance_run', 'label' => 'Habiskan, jangan order', 'color' => 'pink', 'icon' => '🏷️', 'urgency' => 0];
            } elseif ($ms && in_array($ms['status'], [Season::LUAR, Season::PERSIAPAN], true)
                      && $classification['status'] !== 'fast') {
                // Barang musiman di luar musimnya tidak dinilai slow/dead. Yang
                // tetap laris (fast) walau di luar musim dinilai seperti biasa.
                if ($ms['status'] === Season::PERSIAPAN) {
                    $lalu         = ($musimLaluSold[$seasonId] ?? collect())->get($gid);
                    $musimLaluQty = $lalu ? round((float) $lalu->total_qty_terjual / $baseQty, 2) : 0;
                    $classification = ['status' => 'musiman', 'label' => 'Musim Segera Tiba', 'color' => 'teal'];
                    $recommendation = ['action' => 'season_prep', 'label' => 'Siapkan Stok Musim', 'color' => 'teal', 'icon' => '🗓️', 'urgency' => 2];
                } else {
                    $classification = ['status' => 'musiman', 'label' => 'Musiman – Di Luar Musim', 'color' => 'teal'];
                    $recommendation = ['action' => 'season_hold', 'label' => 'Simpan, jangan order', 'color' => 'teal', 'icon' => '🧊', 'urgency' => 0];
                }
            }

            $kandidat = (! $seasonId && ! $isObral && $lt && ! ($adaKolomBaru && $good->season_good !== null && (int) $good->season_good === 0))
                ? $this->seasonalPattern($lt['bulan'], $lt['trx'], $now, $stok)
                : null;

            $obral = $isObral ? $obralSold->get($gid) : null;

            $all[] = (object) [
                'good_id'          => $gid,
                'kode'             => $good->kode,
                // "Tipe Nama" (mis. "Kaleng Sarden ABC"), sama dengan Good::getFullName().
                'nama'             => $this->formatGoodName($good->tipe ?? null, $good->nama),
                'tipe'             => $good->tipe ?? null,
                'type_id'          => $good->type_id,
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
                // Riwayat 12 bulan terakhir (independen dari filter)
                'total_transaksi_lt' => $totalTrxLT,
                'total_qty_lt'       => $totalQtyLT,
                'avg_qty_per_day'  => $avgQtyPerDay,
                'days_of_stock'    => $daysOfStock,
                'days_since_trx'   => $daysSinceTrx,
                'days_since_loading' => $daysSinceLoading,
                'last_trx_at'      => $lastTrxAt,
                'status'           => $classification['status'],
                'status_label'     => $classification['label'],
                'status_color'     => $classification['color'],
                'recommendation'   => $recommendation['action'],
                'rec_label'        => $recommendation['label'],
                'rec_color'        => $recommendation['color'],
                'rec_icon'         => $recommendation['icon'],
                'urgency'          => $recommendation['urgency'],
                // Musim
                'season_id'        => $seasonId,
                'season_good'      => $adaKolomBaru ? $good->season_good : null,
                'musim_nama'       => $ms ? $ms['season']->name : null,
                'musim_info'       => $ms ? $ms['label'] : null,
                'musim_sumber'     => $seasonId ? ($good->season_good !== null ? 'barang' : 'jenis') : null,
                'musim_lalu_qty'   => $musimLaluQty,
                'kandidat_musim'   => $kandidat,
                // Obral
                'obral_mulai'      => $isObral ? $good->clearance_at : null,
                'obral_oleh'       => $isObral ? ($good->clearance_by_name ?? '-') : null,
                'obral_alasan'     => $isObral ? $good->clearance_reason : null,
                'obral_stok_awal'  => $isObral ? (float) $good->clearance_stock : null,
                'obral_terjual'    => $isObral ? ($obral ? round((float) $obral->total_qty_terjual / $baseQty, 2) : 0) : null,
                'obral_hari'       => $isObral && $good->clearance_at ? (int) Carbon::parse($good->clearance_at)->diffInDays($now) : null,
                // Stok fisik terakhir dihitung (Sidak / Stock Opname)
                'dicek_at'         => $dicek[$gid] ?? null,
                'dicek_hari'       => isset($dicek[$gid]) ? (int) Carbon::parse($dicek[$gid])->startOfDay()->diffInDays($now->copy()->startOfDay()) : null,
                'is_discontinued'  => false,
            ];
        }

        $summary = $this->buildSummary($all, $days, $minFast);

        if ($status === 'kandidat_musim') {
            $result = array_values(array_filter($all, fn ($g) => $g->kandidat_musim !== null));
        } elseif ($status && $status !== 'all') {
            $result = array_values(array_filter($all, fn ($g) => $g->status === $status));
        } else {
            $result = $all;
        }

        usort($result, function ($a, $b) use ($sortBy, $sortDir) {
            $va = $a->{$sortBy} ?? 0;
            $vb = $b->{$sortBy} ?? 0;
            return $sortDir === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });

        return [
            'goods'   => collect($result),
            'summary' => $summary,
            'days'    => $days,
        ];
    }

    /** Batas transaksi Fast Moving untuk periode $days hari. */
    public function minFastTrx(int $days): int
    {
        // round, bukan ceil: periode bawaan (90 hari lalu s/d hari ini = 91 hari) tetap 10 transaksi.
        return max(self::FAST_MIN_TRX_FLOOR, (int) round(self::FAST_MOVING_MIN_TRX * $days / self::FAST_BASE_DAYS));
    }

    /**
     * Sales per good in a period (types 'normal' and 'retur', not cancelled,
     * returned lines left out). Quantity in the smallest unit; profit after
     * the line discount. The id range of the period's transactions keeps
     * MySQL on the transaction_id index (transactions.created_at has none).
     */
    public function salesPerGood(Carbon $start, Carbon $end)
    {
        $range = DB::table('transactions')->whereBetween('created_at', [$start, $end])->selectRaw('MIN(id) a, MAX(id) b')->first();

        return TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->whereIn('transactions.type', ['normal', 'retur'])
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->whereRaw("COALESCE(transaction_details.type, '') <> 'retur'")
            ->whereBetween('transaction_details.transaction_id', [(int) ($range->a ?? 0), (int) ($range->b ?? 0)])
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('good_units.good_id')
            ->select('good_units.good_id')
            ->selectRaw('COALESCE(SUM(transaction_details.real_quantity), 0) AS total_qty_terjual')
            ->selectRaw('COUNT(DISTINCT transactions.id) AS total_transaksi')
            ->selectRaw('COALESCE(SUM(transaction_details.sum_price), 0) AS total_omzet')
            ->selectRaw('COALESCE(SUM(transaction_details.sum_price - transaction_details.buy_price * transaction_details.quantity), 0) AS total_laba');
    }

    /**
     * Riwayat penjualan LONG_TERM_MONTHS bulan terakhir per barang, dipecah per
     * bulan kalender: ['trx' => int, 'qty' => float (satuan terkecil),
     * 'last' => datetime, 'bulan' => ['2026-03' => qty, ...]].
     */
    private function longTermPerGood(Carbon $now): array
    {
        $bulanExpr = "DATE_FORMAT(transactions.created_at, '%Y-%m')";
        $rows = $this->salesPerGood($now->copy()->subMonths(self::LONG_TERM_MONTHS), $now->copy())
            ->groupByRaw($bulanExpr)
            ->selectRaw("$bulanExpr AS bulan")
            ->selectRaw('MAX(transactions.created_at) AS last_at')
            ->toBase()
            ->get();

        $hasil = [];
        foreach ($rows as $r) {
            $h = $hasil[$r->good_id] ?? ['trx' => 0, 'qty' => 0.0, 'last' => null, 'bulan' => []];
            // Satu transaksi hanya ada di satu bulan, jadi jumlah per bulan = total.
            $h['trx']  += (int) $r->total_transaksi;
            $h['qty']  += (float) $r->total_qty_terjual;
            $h['last']  = $this->latest($h['last'], $r->last_at);
            $h['bulan'][$r->bulan] = (float) $r->total_qty_terjual;
            $hasil[$r->good_id] = $h;
        }

        return $hasil;
    }

    /** Barang aktif (belum discontinued) dengan satuan dasar, harga, jenis, dan data obral/musim. */
    private function activeGoods(?string $kategori, bool $adaKolomBaru)
    {
        $q = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->leftJoin('types',      'types.id',      '=', 'goods.type_id')
            ->leftJoin('good_units AS gu', 'gu.id', '=', 'goods.base_unit_id')
            ->leftJoin('units AS u', 'u.id', '=', 'gu.unit_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0)
            ->select(
                'goods.id                AS good_id',
                'goods.code              AS kode',
                'goods.name              AS nama',
                'goods.type_id           AS type_id',
                'types.name              AS tipe',
                'goods.last_stock        AS stok_sekarang',
                'goods.last_transaction  AS last_transaction_goods',
                'goods.last_loading      AS last_loading_goods',
                'categories.id           AS category_id',
                'categories.name         AS kategori',
                'brands.name             AS merk',
                'u.name                  AS satuan',
                'u.quantity              AS base_qty',
                'gu.buy_price            AS harga_beli',
                'gu.selling_price        AS harga_jual'
            );

        if ($adaKolomBaru) {
            $q->leftJoin('admins AS clr', 'clr.id', '=', 'goods.clearance_by')
              ->addSelect(
                  'goods.is_clearance',
                  'goods.clearance_at',
                  'goods.clearance_reason',
                  'goods.clearance_stock',
                  'clr.name          AS clearance_by_name',
                  'goods.season_id   AS season_good',
                  'types.season_id   AS season_type'
              );
        }

        if ($kategori) {
            $q->where('categories.id', $kategori);
        }

        return $q->orderBy('goods.id')->toBase()->get()->keyBy('good_id');
    }

    /** Terjual sejak obral dimulai, per barang obral (satuan terkecil). */
    private function clearanceSales($goodsRaw, Carbon $now)
    {
        $mulai = $goodsRaw->where('is_clearance', 1)->pluck('clearance_at')->filter()->min();
        if (! $mulai) {
            return collect();
        }

        return $this->salesPerGood(Carbon::parse($mulai), $now->copy())
            ->join('goods AS gc', 'gc.id', '=', 'good_units.good_id')
            ->where('gc.is_clearance', 1)
            ->whereColumn('transactions.created_at', '>=', 'gc.clearance_at')
            ->toBase()
            ->get()->keyBy('good_id');
    }

    /**
     * Pola musiman dari penjualan per bulan, atau null.
     *
     * @return array{label: string, persen: int}|null  mis. ['label' => 'Mar–Apr 2026', 'persen' => 78]
     */
    private function seasonalPattern(array $perBulan, int $trxLT, Carbon $now, float $stok): ?array
    {
        $total = array_sum($perBulan);
        if ($trxLT < self::SEASON_MIN_TRX || $total <= 0) {
            return null;
        }

        $bulan = [];
        for ($c = $now->copy()->subMonths(self::LONG_TERM_MONTHS)->startOfMonth(); $c->lte($now); $c->addMonthNoOverflow()) {
            $bulan[] = $c->format('Y-m');
        }
        $abaikan = [$now->format('Y-m'), $now->copy()->startOfMonth()->subMonthNoOverflow()->format('Y-m')];

        $best = null;
        for ($i = 0; $i + self::SEASON_PEAK_MONTHS <= count($bulan); $i++) {
            $win = array_slice($bulan, $i, self::SEASON_PEAK_MONTHS);
            if (array_intersect($win, $abaikan)) {
                continue;
            }
            $sum = 0;
            foreach ($win as $k) {
                $sum += $perBulan[$k] ?? 0;
            }
            if ($best === null || $sum > $best['sum']) {
                $best = ['sum' => $sum, 'win' => $win];
            }
        }

        if ($best === null || $best['sum'] / $total < self::SEASON_PEAK_SHARE) {
            return null;
        }

        // Sesudah puncak tidak laku sama sekali dan stok habis → kehabisan stok, bukan musim.
        $sesudah = 0;
        foreach (array_slice($bulan, array_search(end($best['win']), $bulan) + 1) as $k) {
            $sesudah += $perBulan[$k] ?? 0;
        }
        if ($sesudah <= 0 && $stok <= 0) {
            return null;
        }

        $awal  = Carbon::createFromFormat('Y-m-d', $best['win'][0] . '-01');
        $akhir = Carbon::createFromFormat('Y-m-d', end($best['win']) . '-01');

        return [
            'label'  => $awal->isoFormat('MMM') . '–' . $akhir->isoFormat('MMM Y'),
            'persen' => (int) round($best['sum'] / $total * 100),
        ];
    }

    /** Tanggal yang paling akhir dari dua tanggal (null diabaikan). */
    private function latest($a, $b)
    {
        if (! $a) {
            return $b ?: null;
        }
        if (! $b) {
            return $a;
        }

        return Carbon::parse($a)->gte(Carbon::parse($b)) ? $a : $b;
    }

    /**
     * Ambil daftar barang yang sudah di-discontinue.
     * Ditampilkan terpisah dengan info kapan & oleh siapa.
     */
    public function getDiscontinuedData(?string $kategori = null, string $sortBy = 'discontinued_at', string $sortDir = 'desc'): array
    {
        // discontinued_by berisi id admin (auth:admin), bukan id tabel users.
        $query = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->leftJoin('types',      'types.id',      '=', 'goods.type_id')
            ->leftJoin('admins',     'admins.id',     '=', 'goods.discontinued_by')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 1)
            ->select(
                'goods.id                 AS good_id',
                'goods.code               AS kode',
                'goods.name               AS nama',
                'types.name               AS tipe',
                'goods.last_stock         AS stok_sekarang',
                'goods.discontinued_at    AS discontinued_at',
                'goods.discontinued_reason AS discontinued_reason',
                'categories.id            AS category_id',
                'categories.name          AS kategori',
                'brands.name              AS merk',
                'admins.name              AS discontinued_by_name'
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
                    'nama'               => $this->formatGoodName($g->tipe ?? null, $g->nama),
                    'kategori'           => $g->kategori           ?? '-',
                    'category_id'        => $g->category_id,
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

        $summary = $this->emptySummary();
        $summary['total']             = $goods->count();
        $summary['discontinuedCount'] = $goods->count();
        $summary['discontinueCount']  = $goods->count();

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
     */
    public function markDiscontinued(int $goodId, string $reason, int $userId): bool
    {
        $this->restoreClearancePrices([$goodId], 'Obral selesai (di-discontinue): harga kembali normal', $userId);

        return (bool) Good::where('id', $goodId)
            ->whereNull('deleted_at')
            ->update($this->discontinuedFields($reason, $userId));
    }

    /**
     * Batalkan status discontinued — barang aktif kembali.
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

    /**
     * Discontinue BANYAK barang sekaligus (checklist), dengan SATU alasan
     * yang sama untuk semua barang terpilih.
     *
     * @return int jumlah barang yang berhasil di-discontinue
     */
    public function markDiscontinuedBulk(array $goodIds, string $reason, int $userId): int
    {
        $this->restoreClearancePrices($goodIds, 'Obral selesai (di-discontinue): harga kembali normal', $userId);

        $count = 0;
        foreach (array_chunk($this->ids($goodIds), 500) as $chunk) {
            $count += (int) Good::whereIn('id', $chunk)
                ->whereNull('deleted_at')
                ->where('is_discontinued', 0)
                ->update($this->discontinuedFields($reason, $userId));
        }

        return $count;
    }

    private function discontinuedFields(string $reason, int $userId): array
    {
        $fields = [
            'is_discontinued'     => 1,
            'discontinued_at'     => Carbon::now(),
            'discontinued_reason' => trim($reason),
            'discontinued_by'     => $userId,
        ];
        // Barang obral yang di-discontinue manual: obralnya ikut selesai.
        if (Season::tabelAda()) {
            $fields['is_clearance'] = 0;
        }

        return $fields;
    }

    // =========================================================================
    // AKSI OBRAL
    // =========================================================================
    // Barang obral = sisa stok dihabiskan, tidak diorder lagi. Tidak dinilai
    // fast/slow/dead (penjualan obral yang ramai tidak boleh membuatnya jadi
    // "Fast Moving" lalu disarankan reorder), tidak muncul di Rekomendasi
    // Reorder, dan otomatis jadi discontinued saat stoknya habis.

    /**
     * Tandai barang sebagai obral. Hanya barang aktif yang stoknya masih ada.
     *
     * @return int jumlah barang yang berhasil ditandai
     */
    public function markClearance(array $goodIds, string $reason, int $userId): int
    {
        if (! Season::tabelAda()) {
            return 0;
        }

        $count = 0;
        foreach (array_chunk($this->ids($goodIds), 500) as $chunk) {
            $count += (int) Good::whereIn('id', $chunk)
                ->whereNull('deleted_at')
                ->where('is_discontinued', 0)
                ->where('is_clearance', 0)
                ->where('last_stock', '>', 0)
                ->update([
                    'is_clearance'     => 1,
                    'clearance_at'     => Carbon::now(),
                    'clearance_reason' => trim($reason),
                    'clearance_by'     => $userId,
                    'clearance_stock'  => DB::raw('last_stock'),
                ]);
        }

        return $count;
    }

    /** Batalkan obral: harga jual kembali normal, barang dinilai seperti biasa lagi. */
    public function cancelClearance(int $goodId, ?int $userId = null): bool
    {
        if (! Season::tabelAda()) {
            return false;
        }
        if (! Good::where('id', $goodId)->where('is_clearance', 1)->exists()) {
            return false;
        }

        $this->restoreClearancePrices([$goodId], 'Batal obral: harga kembali normal', $userId);

        return (bool) Good::where('id', $goodId)
            ->where('is_clearance', 1)
            ->update([
                'is_clearance'     => 0,
                'clearance_at'     => null,
                'clearance_reason' => null,
                'clearance_by'     => null,
                'clearance_stock'  => null,
            ]);
    }

    /**
     * Barang obral yang stoknya sudah habis → discontinued ("Habis obral").
     * Dipanggil saat menu Perputaran / Reorder dibuka.
     *
     * @return int jumlah barang yang diselesaikan
     */
    public function finishSoldOutClearance(): int
    {
        if (! Season::tabelAda()) {
            return 0;
        }

        $habis = fn () => DB::table('goods')
            ->whereNull('deleted_at')
            ->where('is_discontinued', 0)
            ->where('is_clearance', 1)
            ->where('last_stock', '<=', 0);

        // Harga kembali normal, supaya kalau barangnya diaktifkan lagi tidak
        // terjual dengan harga obral.
        $ids = $habis()->pluck('id')->all();
        if (! $ids) {
            return 0;
        }
        $this->restoreClearancePrices($ids, 'Obral selesai (stok habis): harga kembali normal', null);

        return (int) $habis()
            ->update([
                'is_discontinued'     => 1,
                'is_clearance'        => 0,
                'discontinued_at'     => Carbon::now(),
                'discontinued_by'     => DB::raw('clearance_by'),
                'discontinued_reason' => DB::raw("CONCAT('Habis obral (mulai ', DATE_FORMAT(clearance_at, '%d/%m/%Y'), ')', IF(COALESCE(clearance_reason, '') = '', '', CONCAT(': ', clearance_reason)))"),
                'updated_at'          => Carbon::now(),
            ]);
    }

    public function countClearance(): int
    {
        return Season::tabelAda()
            ? Good::whereNull('deleted_at')->where('is_discontinued', 0)->where('is_clearance', 1)->count()
            : 0;
    }

    // =========================================================================
    // HARGA OBRAL
    // =========================================================================
    // Harga obral = harga jual satuan diubah langsung (tercatat di riwayat
    // harga), jadi Kasir HP/PC menerima harga baru saat sinkron. Harga
    // sebelumnya disimpan di good_units.clearance_normal_price untuk harga
    // coret di label/teks WA dan untuk dikembalikan saat obral dibatalkan,
    // stoknya habis, atau barangnya di-discontinue. Harga obral paling rendah
    // sama dengan harga beli (modal), tidak boleh di bawahnya.

    /**
     * Data barang untuk jendela obral, label obral, dan teks WA, urut sesuai
     * $goodIds. Satuan diurutkan dari isi terkecil.
     *
     * @return array<int, array> tiap barang: good_id, nama, kategori, stok, satuan,
     *   is_obral, dicek_hari, units[good_unit_id, unit, isi, buy_price, selling_price, normal_price]
     */
    public function clearanceGoods(array $goodIds): array
    {
        $ids = $this->ids($goodIds);
        if (! $ids) {
            return [];
        }
        $adaObral = Season::tabelAda();
        $adaHarga = SkemaCekObral::hargaObralAda();
        $dicek    = $this->lastCheckedPerGood(Carbon::now()->subDays(self::CHECKED_SHOW_DAYS));
        $now      = Carbon::now()->startOfDay();

        $goods = collect();
        $units = collect();
        foreach (array_chunk($ids, 500) as $chunk) {
            $goods = $goods->merge(DB::table('goods')
                ->leftJoin('types', 'types.id', '=', 'goods.type_id')
                ->leftJoin('categories', 'categories.id', '=', 'goods.category_id')
                ->leftJoin('good_units AS bu', 'bu.id', '=', 'goods.base_unit_id')
                ->leftJoin('units AS bun', 'bun.id', '=', 'bu.unit_id')
                ->whereIn('goods.id', $chunk)
                ->whereNull('goods.deleted_at')
                ->get(array_merge(
                    ['goods.id', 'goods.name', 'types.name AS tipe', 'categories.name AS kategori', 'goods.last_stock', 'bun.name AS satuan'],
                    $adaObral ? ['goods.is_clearance'] : []
                )));
            $units = $units->merge(DB::table('good_units')
                ->join('units', 'units.id', '=', 'good_units.unit_id')
                ->whereIn('good_units.good_id', $chunk)
                ->whereNull('good_units.deleted_at')
                ->get(array_merge(
                    ['good_units.id', 'good_units.good_id', 'good_units.buy_price', 'good_units.selling_price', 'units.name AS unit', 'units.quantity AS isi'],
                    $adaHarga ? ['good_units.clearance_normal_price'] : []
                )));
        }
        $goods = $goods->keyBy('id');
        $units = $units->groupBy('good_id');

        $hasil = [];
        foreach ($ids as $id) {
            $g = $goods->get($id);
            if (! $g) {
                continue;
            }
            $hasil[] = [
                'good_id'    => (int) $g->id,
                'nama'       => $this->formatGoodName($g->tipe, $g->name),
                'kategori'   => $g->kategori ?? '-',
                'stok'       => (float) $g->last_stock,
                'satuan'     => $g->satuan ?? '-',
                'is_obral'   => $adaObral && (int) $g->is_clearance === 1,
                'dicek_hari' => isset($dicek[$id]) ? (int) Carbon::parse($dicek[$id])->startOfDay()->diffInDays($now) : null,
                'units'      => ($units->get($id) ?? collect())
                    ->sortBy(fn ($u) => (float) $u->isi)
                    ->map(fn ($u) => [
                        'good_unit_id'  => (int) $u->id,
                        'unit'          => $u->unit,
                        'isi'           => (float) $u->isi,
                        'buy_price'     => (float) $u->buy_price,
                        'selling_price' => (float) $u->selling_price,
                        'normal_price'  => $adaHarga && $u->clearance_normal_price !== null ? (float) $u->clearance_normal_price : null,
                    ])->values()->all(),
            ];
        }

        return $hasil;
    }

    /**
     * Periksa harga obral sebelum disimpan. $prices = [good_unit_id => harga];
     * harga kosong atau sama dengan harga sekarang = satuan itu tidak diubah.
     *
     * @return string[] pesan kesalahan, kosong = boleh disimpan
     */
    public function clearancePriceErrors(array $goodIds, array $prices, bool $tampilkanModal): array
    {
        $errors = [];
        foreach ($this->clearancePriceRows($goodIds, $prices) as [$u, $harga, $label]) {
            $jual  = round((float) ($u->clearance_normal_price ?? $u->selling_price), 2);
            $modal = round((float) $u->buy_price, 2);

            if ($harga <= 0) {
                $errors[] = $label . ': harga obral harus lebih dari 0.';
            } elseif ($modal <= 0) {
                $errors[] = $label . ': harga beli masih kosong, isi dulu lewat Ubah Harga.';
            } elseif ($harga < $modal) {
                $errors[] = $label . ': harga obral ' . $this->rupiah($harga) . ' di bawah modal'
                          . ($tampilkanModal ? ' ' . $this->rupiah($modal) : '') . '. Paling rendah sama dengan modal.';
            } elseif ($harga > $jual) {
                $errors[] = $label . ': harga obral ' . $this->rupiah($harga) . ' lebih tinggi dari harga jual ' . $this->rupiah($jual) . '.';
            }
        }

        return $errors;
    }

    /**
     * Pasang harga obral pada barang yang sedang obral. Periksa dulu dengan
     * clearancePriceErrors().
     *
     * @return int jumlah satuan yang harganya diubah
     */
    public function applyClearancePrices(array $goodIds, array $prices, string $reason, int $userId): int
    {
        if (! SkemaCekObral::hargaObralAda() || ! Season::tabelAda()) {
            return 0;
        }
        $obral = Good::whereIn('id', $this->ids($goodIds) ?: [0])->where('is_clearance', 1)->pluck('id')->all();

        $n = 0;
        foreach ($this->clearancePriceRows($obral, $prices) as [$u, $harga]) {
            $unit = GoodUnit::find($u->id);
            GoodPrice::create([
                'role' => 'admin', 'role_id' => $userId, 'good_unit_id' => $unit->id,
                'old_buy_price' => $unit->buy_price, 'recent_buy_price' => $unit->buy_price,
                'old_price' => $unit->selling_price, 'recent_price' => $harga,
                'reason' => mb_substr('Obral: ' . trim($reason), 0, 255),
            ]);
            $unit->forceFill([
                'selling_price'          => $harga,
                'clearance_normal_price' => $unit->clearance_normal_price ?? $unit->selling_price,
            ])->save();
            $n++;
        }

        return $n;
    }

    /**
     * Kembalikan harga jual normal (sebelum obral) semua satuan barang ini,
     * dicatat di riwayat harga. $userId null = dicatat atas nama yang
     * memulai obral.
     *
     * @return int jumlah satuan yang harganya dikembalikan
     */
    public function restoreClearancePrices(array $goodIds, string $reason, ?int $userId): int
    {
        if (! SkemaCekObral::hargaObralAda() || ! Season::tabelAda()) {
            return 0;
        }

        $n = 0;
        foreach (array_chunk($this->ids($goodIds), 500) as $chunk) {
            $units = GoodUnit::whereIn('good_id', $chunk)->whereNotNull('clearance_normal_price')->get();
            if ($units->isEmpty()) {
                continue;
            }
            $oleh = Good::whereIn('id', $units->pluck('good_id')->unique()->all())->pluck('clearance_by', 'id');

            foreach ($units as $unit) {
                $normal = (float) $unit->clearance_normal_price;
                GoodPrice::create([
                    'role' => 'admin',
                    'role_id' => $userId ?? $oleh[$unit->good_id] ?? auth('admin')->id() ?? DB::table('admins')->min('id'),
                    'good_unit_id' => $unit->id,
                    'old_buy_price' => $unit->buy_price, 'recent_buy_price' => $unit->buy_price,
                    'old_price' => $unit->selling_price, 'recent_price' => $normal,
                    'reason' => $reason,
                ]);
                $unit->forceFill(['selling_price' => $normal, 'clearance_normal_price' => null])->save();
                $n++;
            }
        }

        return $n;
    }

    /**
     * Satuan yang harganya benar-benar berubah, milik barang $goodIds:
     * [baris good_unit, harga obral, "Nama (SATUAN)"].
     */
    private function clearancePriceRows(array $goodIds, array $prices): array
    {
        $ids = $this->ids($goodIds);
        $guIds = $this->ids(array_keys(array_filter($prices, fn ($v) => $v !== null && $v !== '')));
        if (! $ids || ! $guIds) {
            return [];
        }
        $adaHarga = SkemaCekObral::hargaObralAda();

        $rows = [];
        foreach (array_chunk($guIds, 500) as $chunk) {
            $units = DB::table('good_units')
                ->join('goods', 'goods.id', '=', 'good_units.good_id')
                ->leftJoin('types', 'types.id', '=', 'goods.type_id')
                ->join('units', 'units.id', '=', 'good_units.unit_id')
                ->whereIn('good_units.id', $chunk)
                ->whereNull('good_units.deleted_at')
                ->get(array_merge(
                    ['good_units.id', 'good_units.good_id', 'good_units.buy_price', 'good_units.selling_price',
                     'goods.name', 'types.name AS tipe', 'units.name AS unit'],
                    $adaHarga ? ['good_units.clearance_normal_price'] : []
                ));

            foreach ($units as $u) {
                if (! in_array((int) $u->good_id, $ids, true)) {
                    continue;
                }
                $u->clearance_normal_price = $u->clearance_normal_price ?? null;
                $harga = round((float) $prices[$u->id], 2);
                if (abs($harga - round((float) $u->selling_price, 2)) < 0.005) {
                    continue; // tidak diubah
                }
                $rows[] = [$u, $harga, $this->formatGoodName($u->tipe, $u->name) . ' (' . mb_strtoupper((string) $u->unit) . ')'];
            }
        }

        return $rows;
    }

    private function rupiah(float $v): string
    {
        return 'Rp' . number_format($v, 0, ',', '.');
    }

    // =========================================================================
    // CEK FISIK TERAKHIR
    // =========================================================================

    /**
     * Kapan stok fisik barang terakhir dihitung (Sidak Stok atau Stock Opname)
     * sejak $since.
     *
     * @return array<int, string> good_id => tanggal-waktu
     */
    public function lastCheckedPerGood(Carbon $since): array
    {
        $hasil = [];
        $catat = function ($rows) use (&$hasil) {
            foreach ($rows as $r) {
                $hasil[(int) $r->good_id] = $this->latest($hasil[(int) $r->good_id] ?? null, $r->at);
            }
        };

        if (Schema::hasTable('stock_inspection_details')) {
            $catat(DB::table('stock_inspection_details AS d')
                ->join('stock_inspections AS s', 's.id', '=', 'd.stock_inspection_id')
                ->whereNull('s.deleted_at')
                ->where('d.updated_at', '>=', $since)
                ->groupBy('d.good_id')
                ->selectRaw('d.good_id, MAX(d.updated_at) AS at')
                ->get());
        }

        $catat(DB::table('stock_opname_details AS d')
            ->join('stock_opnames AS so', 'so.id', '=', 'd.stock_opname_id')
            ->join('good_units AS gu', 'gu.id', '=', 'd.good_unit_id')
            ->whereNull('so.deleted_at')
            ->whereNull('d.deleted_at')
            ->where('d.created_at', '>=', $since)
            ->groupBy('gu.good_id')
            ->selectRaw('gu.good_id, MAX(d.created_at) AS at')
            ->get());

        return $hasil;
    }

    // =========================================================================
    // AKSI MUSIM
    // =========================================================================

    /**
     * Atur musim sebuah barang.
     *
     * $scope 'jenis'  : musim untuk seluruh jenis barang ini (types.season_id);
     *                   pengecualian di barangnya dihapus supaya ikut jenis.
     * $scope 'barang' : hanya barang ini. $seasonId null = ikut jenis,
     *                   0 = bukan musiman, > 0 = musim itu.
     */
    public function setSeason(int $goodId, string $scope, ?int $seasonId): bool
    {
        if (! Season::tabelAda()) {
            return false;
        }
        $good = Good::whereNull('deleted_at')->find($goodId);
        if (! $good) {
            return false;
        }

        if ($scope === 'jenis') {
            if (! $good->type_id) {
                return false;
            }
            DB::table('types')->where('id', $good->type_id)->update(['season_id' => $seasonId ?: null, 'updated_at' => Carbon::now()]);
            DB::table('goods')->where('id', $goodId)->update(['season_id' => null]);

            return true;
        }

        DB::table('goods')->where('id', $goodId)->update(['season_id' => $seasonId]);

        return true;
    }

    // =========================================================================
    // BERES-BERES DATA: STOK MINUS
    // =========================================================================
    // Stok minus biasanya muncul karena kesalahan input transaksi/loading di
    // masa lalu. Ini murni masalah KERAPIAN DATA, bukan masalah pergerakan
    // barang — dipisah dari klasifikasi supaya tidak mengotori keputusan
    // reorder/discontinue. Perbaikannya lewat Stock Opname.

    /**
     * Daftar barang dengan stok minus (data tidak konsisten, perlu stock opname).
     */
    public function getNegativeStockGoods(int $limit = 50): \Illuminate\Support\Collection
    {
        $goods = Good::leftJoin('categories', 'categories.id', '=', 'goods.category_id')
            ->leftJoin('brands',     'brands.id',     '=', 'goods.brand_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.last_stock', '<', 0)
            ->select(
                'goods.id          AS good_id',
                'goods.code        AS kode',
                'goods.name        AS nama',
                'goods.last_stock  AS stok',
                'categories.name   AS kategori',
                'brands.name       AS merk'
            )
            ->orderBy('goods.last_stock', 'asc') // paling minus duluan
            ->limit($limit)
            ->get();

        // Akun yang membuat stoknya minus, kalau tercatat (StockMinusLog).
        $penyebab = StockMinusLog::terbukaUntuk($goods->pluck('good_id')->all());
        foreach ($goods as $g) {
            $log = $penyebab[$g->good_id] ?? null;
            $g->penyebab     = $log ? $log->teks() : null;
            $g->penyebab_url = $log ? $log->url() : null;
        }

        return $goods;
    }

    public function countNegativeStock(): int
    {
        return Good::whereNull('deleted_at')->where('last_stock', '<', 0)->count();
    }

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    /**
     * Nama tampilan "Tipe nama" — aturannya satu tempat di Good::formatName;
     * di sini cuma tambah placeholder untuk data lama yang namanya kosong.
     */
    private function formatGoodName(?string $tipe, ?string $nama): string
    {
        if (trim((string) $nama) === '') {
            $nama = '(tanpa nama)';
        }

        return Good::formatName($tipe, $nama);
    }

    private function ids(array $goodIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $goodIds))));
    }

    private function classify(int $totalTrx, int $daysSinceTrx, float $stok, int $totalTrxLT, int $minFast, ?int $daysSinceLoading): array
    {
        if ($totalTrx >= $minFast && $daysSinceTrx <= self::ACTIVE_LAST_TRX_DAYS) {
            return ['status' => 'fast', 'label' => 'Fast Moving', 'color' => 'green'];
        }

        $looksInactive = $daysSinceTrx >= self::DEAD_STOCK_DAYS || $totalTrx === 0;

        if (! $looksInactive) {
            return ['status' => 'slow', 'label' => 'Slow Moving', 'color' => 'orange'];
        }

        // Baru di-loading dan belum laku sesudahnya: belum bisa dinilai.
        if ($stok > 0 && $daysSinceLoading !== null
            && $daysSinceLoading < self::NEW_GOOD_DAYS && $daysSinceLoading < $daysSinceTrx) {
            return ['status' => 'baru', 'label' => 'Baru Masuk', 'color' => 'blue'];
        }

        // Historisnya (12 bulan) laku → jangan dicap mati, cek dulu
        // (biasanya stok habis / lupa order).
        if ($totalTrxLT >= self::SAFE_LIFETIME_TRX) {
            return ['status' => 'at_risk', 'label' => 'Perlu Dicek (Riwayat Bagus)', 'color' => 'purple'];
        }

        // Stok sudah habis: tidak ada modal yang tertahan, jadi bukan dead stock.
        if ($stok <= 0) {
            return ['status' => 'kosong', 'label' => 'Kosong / Tidak Aktif', 'color' => 'gray'];
        }

        return ['status' => 'dead', 'label' => 'Dead Stock', 'color' => 'red'];
    }

    private function recommend(array $classification, float $stok, int $daysOfStock, int $totalTrx, float $margin): array
    {
        $status = $classification['status'];

        if ($status === 'at_risk') {
            if ($stok <= 0) {
                return ['action' => 'restock_check', 'label' => 'Cek Stok & Restock', 'color' => 'purple', 'icon' => '🔎', 'urgency' => 2];
            }
            return ['action' => 'review_stuck', 'label' => 'Stok Ada, Cek Kenapa Tidak Laku', 'color' => 'purple', 'icon' => '❓', 'urgency' => 1];
        }

        if ($status === 'kosong') {
            return ['action' => 'discontinue', 'label' => 'Discontinue (bersihkan data)', 'color' => 'gray', 'icon' => '🗑️', 'urgency' => 1];
        }

        if ($status === 'baru') {
            return ['action' => 'wait', 'label' => 'Tunggu, belum bisa dinilai', 'color' => 'blue', 'icon' => '⏳', 'urgency' => 0];
        }

        if ($status === 'dead') {
            if ($margin < 5) {
                return ['action' => 'clearance', 'label' => 'Obral / Clearance', 'color' => 'red',    'icon' => '🏷️', 'urgency' => 3];
            }
            return     ['action' => 'review',    'label' => 'Perlu Review',      'color' => 'orange', 'icon' => '⚠️', 'urgency' => 2];
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

    private function emptySummary(): array
    {
        return [
            'total' => 0, 'fastCount' => 0, 'slowCount' => 0, 'deadCount' => 0, 'atRiskCount' => 0,
            'kosongCount' => 0, 'baruCount' => 0, 'musimanCount' => 0, 'obralCount' => 0,
            'kandidatMusimCount' => 0, 'discontinuedCount' => 0,
            'totalOmzet' => 0, 'totalLaba' => 0, 'totalNilaiStok' => 0,
            'nilaiDead' => 0, 'nilaiSlow' => 0, 'nilaiMusiman' => 0, 'nilaiObral' => 0, 'modalTertahan' => 0,
            'reorderCount' => 0, 'discontinueCount' => 0, 'reviewCount' => 0, 'seasonPrepCount' => 0,
            'days' => 0, 'minFast' => 0,
        ];
    }

    private function buildSummary(array $goods, int $days, int $minFast): array
    {
        $s = $this->emptySummary();
        $s['total']   = count($goods);
        $s['days']    = $days;
        $s['minFast'] = $minFast;

        $countKey = [
            'fast' => 'fastCount', 'slow' => 'slowCount', 'dead' => 'deadCount', 'at_risk' => 'atRiskCount',
            'kosong' => 'kosongCount', 'baru' => 'baruCount', 'musiman' => 'musimanCount', 'obral' => 'obralCount',
        ];

        foreach ($goods as $g) {
            $s[$countKey[$g->status]]++;
            if ($g->kandidat_musim !== null) $s['kandidatMusimCount']++;

            $s['totalOmzet']     += $g->total_omzet;
            $s['totalLaba']      += $g->total_laba;
            $s['totalNilaiStok'] += $g->nilai_stok;

            if ($g->status === 'dead')    $s['nilaiDead']    += $g->nilai_stok;
            if ($g->status === 'slow')    $s['nilaiSlow']    += $g->nilai_stok;
            if ($g->status === 'musiman') $s['nilaiMusiman'] += $g->nilai_stok;
            if ($g->status === 'obral')   $s['nilaiObral']   += $g->nilai_stok;

            if (in_array($g->recommendation, ['reorder', 'reorder_urgent'])) $s['reorderCount']++;
            if ($g->recommendation === 'discontinue')                         $s['discontinueCount']++;
            if (in_array($g->recommendation, ['review', 'clearance']))        $s['reviewCount']++;
            if ($g->recommendation === 'season_prep')                         $s['seasonPrepCount']++;
        }

        $s['modalTertahan'] = $s['nilaiDead'] + $s['nilaiSlow'];

        return $s;
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
