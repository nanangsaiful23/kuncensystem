<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

use App\Models\Journal;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\GoodLoading;

class FinancialReportRepository
{
    // =========================================================================
    // A. AKUN — Diambil dinamis dari DB, otomatis ikut jika ada tambah akun
    // =========================================================================

    /**
     * Ambil semua akun aktif, dikelompokkan untuk laporan.
     * Struktur: [group => [activa => [accounts...], pasiva => [...]]]
     */
    public function getAllAccounts(): Collection
    {
        return Account::whereNull('deleted_at')
            ->orderBy('code')
            ->get();
    }

    /**
     * Akun dikelompokkan per tipe untuk sidebar / header laporan
     */
    public function getAccountsGrouped(): array
    {
        $accounts = $this->getAllAccounts();

        $result = [
            'neraca_aktiva'  => collect(),
            'neraca_pasiva'  => collect(),
            'laba_rugi'      => collect(),
            'lainnya'        => collect(),
        ];

        foreach ($accounts as $acc) {
            $group  = strtolower(trim($acc->group  ?? ''));
            $activa = strtolower(trim($acc->activa ?? ''));

            if ($group === 'neraca') {
                if (in_array($activa, ['aktiva', 'active'])) {
                    $result['neraca_aktiva']->push($acc);
                } else {
                    $result['neraca_pasiva']->push($acc);
                }
            } elseif (str_contains($group, 'laba')) {
                $result['laba_rugi']->push($acc);
            } else {
                $result['lainnya']->push($acc);
            }
        }

        return $result;
    }

    // =========================================================================
    // B. JURNAL UMUM — Dinamis, semua akun otomatis masuk
    // =========================================================================

    // =========================================================================
    // C. BUKU BESAR — Saldo per akun, dinamis
    // =========================================================================

    // =========================================================================
    // D. LAPORAN LABA RUGI
    // =========================================================================

    /**
     * Debit and credit per account in a period, without the year-end closing
     * journals (they only move the profit to Laba Ditahan).
     *
     * @return \Illuminate\Support\Collection<int, object{id,code,name,color,debit,credit}>
     */
    public function accountTotals(string $codeLike, string $start, string $end): Collection
    {
        $side = function (string $column, string $accountColumn) use ($codeLike, $start, $end) {
            return DB::table('journals')
                ->join('accounts', 'accounts.id', '=', 'journals.' . $accountColumn)
                ->whereNull('journals.deleted_at')
                ->whereRaw("COALESCE(journals.type, '') <> 'closing'")
                ->where('accounts.code', 'like', $codeLike)
                ->whereBetween('journals.journal_date', [$start, $end])
                ->groupBy('accounts.id')
                ->selectRaw("accounts.id AS id, COALESCE(SUM(journals.$column), 0) AS total")
                ->pluck('total', 'id');
        };
        $debit = $side('debit', 'debit_account_id');
        $credit = $side('credit', 'credit_account_id');
        $ids = $debit->keys()->merge($credit->keys())->unique()->all();

        return Account::whereIn('id', $ids)->orderBy('code')->get()->map(fn ($a) => (object) [
            'id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'color' => $a->color,
            'debit' => (float) ($debit[$a->id] ?? 0), 'credit' => (float) ($credit[$a->id] ?? 0),
        ]);
    }

    /**
     * Laba Rugi from the journals, per account, as debit and credit together:
     *  - 4xxx pendapatan    = kredit - debit (cancelled sales are debited back)
     *  - 5xxx beban (5101 HPP shown apart) = debit - kredit (e.g. a price
     *    increase is credited to 5215 and lowers the cost)
     *  - 6xxx pendapatan lain = kredit - debit
     * Before, only one side was counted, which overstated sales, HPP and
     * costs (October 2026: profit 0,77 juta instead of 21,9 juta).
     */
    public function getProfitLossReport($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end   = Carbon::parse($endDate)->toDateString();

        $rows = function (string $like, bool $creditPositive) use ($start, $end) {
            return $this->accountTotals($like, $start, $end)->map(function ($a) use ($creditPositive) {
                $a->total = round($creditPositive ? $a->credit - $a->debit : $a->debit - $a->credit, 2);
                return $a;
            })->filter(fn ($a) => abs($a->total) >= 0.5)->values();
        };

        $pendapatan     = $rows('4%', true);
        $beban          = $rows('5%', false);
        $pendapatanLain = $rows('6%', true);

        $totalPendapatan     = (float) $pendapatan->sum('total');
        $totalPendapatanLain = (float) $pendapatanLain->sum('total');
        $hpp        = (float) $beban->where('code', '5101')->sum('total');
        $labaKotor  = $totalPendapatan - $hpp;
        $bebanUsaha = (float) $beban->where('code', '!=', '5101')->sum('total');
        $labaBersih = $labaKotor - $bebanUsaha + $totalPendapatanLain;

        return [
            'pendapatan'            => $pendapatan,
            'beban'                 => $beban,
            'pendapatan_lain'       => $pendapatanLain,
            'total_pendapatan'      => $totalPendapatan,
            'hpp'                   => $hpp,
            'laba_kotor'            => $labaKotor,
            'beban_usaha'           => $beban->where('code', '!=', '5101')->values(),
            'total_beban_usaha'     => $bebanUsaha,
            'total_pendapatan_lain' => $totalPendapatanLain,
            'laba_bersih'           => $labaBersih,
        ];
    }

    // =========================================================================
    // F. RINGKASAN KAS — Semua akun kas (kode 111x) dalam periode
    // =========================================================================

    /**
     * Mutasi semua akun kas dalam periode. The opening balance of the account
     * (accounts.balance) is part of the balance; before it was left out, so
     * e.g. Kas di Nanang showed -25,7 juta instead of 12,97 juta.
     */
    public function getCashFlowSummary($startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end   = Carbon::parse($endDate)->toDateString();
        $before = Carbon::parse($start)->subDay()->toDateString();

        $kasAccounts = Account::whereNull('deleted_at')
            ->where('code', 'like', '111%')
            ->orderBy('code')
            ->get();

        return $kasAccounts->map(function ($acc) use ($start, $end, $before) {
            $masuk = (float) DB::table('journals')->whereNull('deleted_at')
                ->where('debit_account_id', $acc->id)->whereBetween('journal_date', [$start, $end])->sum('debit');
            $keluar = (float) DB::table('journals')->whereNull('deleted_at')
                ->where('credit_account_id', $acc->id)->whereBetween('journal_date', [$start, $end])->sum('credit');
            $saldoAwal = $this->getAccountBalanceByCode($acc->code, $before);

            return (object) [
                'account_id'  => $acc->id,
                'kode'        => $acc->code,
                'nama'        => $acc->name,
                'color'       => $acc->color ?? '#94a3b8',
                'saldo_awal'  => $saldoAwal,
                'masuk'       => $masuk,
                'keluar'      => $keluar,
                'saldo_akhir' => $saldoAwal + $masuk - $keluar,
            ];
        });
    }

    // =========================================================================
    // G. REKONSILIASI PERSEDIAAN — Cocokkan nilai stok fisik vs saldo jurnal
    // =========================================================================

    /**
     * Total Nilai Persediaan Barang Dagang (FISIK) — dihitung dari
     * goods.last_stock (satuan terkecil) x harga beli satuan terkecil.
     *
     * PENTING: goods.last_stock disimpan dalam SATUAN TERKECIL (pcs/eceran),
     * jadi harga beli yang dipakai WAJIB dari good_unit dengan quantity
     * terkecil juga (base_unit_id, fallback ke unit qty terkecil kalau
     * base_unit_id kosong/terhapus). Kalau salah pilih unit, nilai yang
     * dihasilkan tidak akan pernah cocok dengan saldo jurnal walau data
     * sebenarnya benar.
     *
     * Barang dengan stok MINUS tetap diikutkan dalam total (supaya total
     * fisik = apa adanya di sistem), tapi juga dikembalikan terpisah di
     * 'barang_minus' karena stok minus biasanya menandakan ada kesalahan
     * input transaksi/loading yang perlu dicek manual — bukan aset negatif
     * yang benar-benar nyata.
     *
     * @return array{
     *   total_nilai_persediaan: float,
     *   jumlah_barang: int,
     *   jumlah_barang_minus: int,
     *   total_nilai_minus: float,
     *   barang_minus: Collection
     * }
     */
    public function getInventoryAssetTotal(): array
    {
        // Subquery fallback: pilih 1 good_unit_id per good_id dengan
        // quantity satuan PALING KECIL, dipakai kalau goods.base_unit_id
        // kosong atau unitnya sudah soft-deleted.
        $fallbackUnit = DB::table('good_units AS gu_min')
            ->join('units AS u_min', 'u_min.id', '=', 'gu_min.unit_id')
            ->whereNull('gu_min.deleted_at')
            ->select(
                'gu_min.good_id',
                DB::raw('SUBSTRING_INDEX(
                            GROUP_CONCAT(gu_min.id ORDER BY CAST(u_min.quantity AS UNSIGNED) ASC, gu_min.id ASC),
                            ",", 1
                         ) AS good_unit_id')
            )
            ->groupBy('gu_min.good_id');

        $rows = DB::table('goods')
            ->leftJoin('good_units AS base_gu', function ($j) {
                $j->on('base_gu.id', '=', 'goods.base_unit_id')
                  ->whereNull('base_gu.deleted_at');
            })
            ->leftJoinSub($fallbackUnit, 'fb', 'fb.good_id', '=', 'goods.id')
            ->leftJoin('good_units AS fallback_gu', function ($j) {
                $j->on('fallback_gu.id', '=', 'fb.good_unit_id')
                  ->whereNull('fallback_gu.deleted_at');
            })
            ->whereNull('goods.deleted_at')
            ->select(
                'goods.id',
                'goods.code',
                'goods.name',
                'goods.last_stock',
                DB::raw('COALESCE(base_gu.buy_price, fallback_gu.buy_price, 0) AS harga_beli')
            )
            ->get();

        $totalNilai      = 0.0;
        $totalNilaiMinus = 0.0;
        $barangMinus     = collect();

        foreach ($rows as $r) {
            $nilai = (float) $r->last_stock * (float) $r->harga_beli;
            $totalNilai += $nilai;

            if ($r->last_stock < 0) {
                $totalNilaiMinus += $nilai; // bernilai negatif, menekan total
                $barangMinus->push((object) [
                    'good_id'    => $r->id,
                    'kode'       => $r->code,
                    'nama'       => $r->name,
                    'stok'       => $r->last_stock,
                    'harga_beli' => $r->harga_beli,
                    'nilai'      => $nilai,
                ]);
            }
        }

        // Goods in the retur box (damaged, waiting to go back to the
        // distributor) are out of the shelf stock but still owned: the
        // journal keeps them in Persediaan until the distributor pays or
        // replaces them, so they count here too.
        $nilaiKotakRetur = (float) DB::table('retur_items')
            ->join('good_units', 'good_units.id', '=', 'retur_items.good_unit_id')
            ->whereNull('retur_items.returned_date')
            ->whereNull('retur_items.deleted_at')
            ->sum('good_units.buy_price');

        return [
            'total_nilai_persediaan' => $totalNilai + $nilaiKotakRetur,
            'nilai_rak'              => $totalNilai,
            'nilai_kotak_retur'      => $nilaiKotakRetur,
            'jumlah_barang'          => $rows->count(),
            'jumlah_barang_minus'    => $barangMinus->count(),
            'total_nilai_minus'      => $totalNilaiMinus,
            'barang_minus'           => $barangMinus->sortBy('nilai')->values(),
        ];
    }

    /**
     * Saldo akun tertentu (misal: Persediaan Barang Dagang) dari jurnal,
     * dihitung kumulatif sampai tanggal tertentu.
     *
     * Standar akuntansi akun aktiva: debit menambah saldo, kredit mengurangi.
     *
     * PENTING: setiap akun punya nilai awal (kolom `balance` di tabel
     * `accounts`) yang HARUS ditambahkan dulu sebagai titik awal, sebelum
     * mutasi debit/kredit dari jurnal dihitung. Tanpa ini, akun yang baru
     * dipakai (belum ada histori jurnal, tapi sudah punya saldo awal saat
     * dibuat) akan selalu tampil bersaldo 0 di rekonsiliasi.
     *
     * @param  string      $accountCode  Kode akun di tabel accounts (mis. '1141')
     * @param  string|null $upToDate     Batas tanggal (default: hari ini)
     */
    public function getAccountBalanceByCode(string $accountCode, ?string $upToDate = null): float
    {
        $upTo = $upToDate ?? date('Y-m-d');

        $account = Account::whereNull('deleted_at')
            ->where('code', $accountCode)
            ->first();

        if (!$account) {
            return 0.0;
        }

        $saldoAwal = (float) $account->balance;

        $debit = DB::table('journals')
            ->whereNull('deleted_at')
            ->where('debit_account_id', $account->id)
            ->where('journal_date', '<=', $upTo)
            ->sum('debit');

        $credit = DB::table('journals')
            ->whereNull('deleted_at')
            ->where('credit_account_id', $account->id)
            ->where('journal_date', '<=', $upTo)
            ->sum('credit');

        return $saldoAwal + (float) $debit - (float) $credit;
    }

    // =========================================================================
    // H. NERACA BULANAN: saldo akhir tiap bulan dalam satu tahun
    // =========================================================================

    /**
     * Balance sheet at the end of each month of $year.
     *
     * It counts the opening balance of the accounts
     * (accounts.balance), keeps the sign of every balance (a negative cash
     * account shows as negative) and puts the profit into equity:
     *   - "Laba belum ditutup": profit of earlier years that were not closed
     *   - "Laba tahun berjalan": profit from 1 January to the end of the month
     * The closing journals of $year itself are left out, so December still
     * shows the profit of the year (the closing only moves it inside equity).
     * Accounts outside 1xxx-6xxx are listed apart ("Akun lain").
     */
    public function getMonthlyBalanceSheet(int $year): array
    {
        $start = "$year-01-01";
        $end   = "$year-12-31";
        $currentYear = (int) date('Y');
        $lastMonth = $year < $currentYear ? 12 : ($year === $currentYear ? (int) date('n') : 0);

        $accounts = DB::table('accounts')->get()->keyBy('id');

        // movement (debit - credit) per account; month 0 = before this year
        $movement = [];
        foreach (['debit' => 'debit_account_id', 'credit' => 'credit_account_id'] as $column => $accountColumn) {
            $rows = DB::select("select $accountColumn account_id, case when journal_date < ? then 0 else month(journal_date) end m, sum($column) v
                from journals where deleted_at is null and journal_date <= ? and not (coalesce(type, '') = 'closing' and coalesce(type_id, 0) = ?)
                group by 1, 2", [$start, $end, $year]);
            foreach ($rows as $r) {
                $movement[$r->account_id][$r->m] = ($movement[$r->account_id][$r->m] ?? 0) + ($column === 'debit' ? 1 : -1) * (float) $r->v;
            }
        }

        $months = [];
        foreach (range(1, 12) as $m) {
            $months[$m] = [
                'label'  => Carbon::create($year, $m, 1)->locale('id')->isoFormat('MMM'),
                'end'    => Carbon::create($year, $m, 1)->endOfMonth()->toDateString(),
                'filled' => $m <= $lastMonth,
            ];
        }

        $sections = ['aktiva' => [], 'kewajiban' => [], 'ekuitas' => [], 'lainnya' => []];
        $profitBefore = 0.0;                                // credit positive
        $profitMonth  = array_fill(1, 12, 0.0);             // credit positive, movement in month
        $openingImbalance = 0.0;

        foreach ($accounts->keys()->merge(array_keys($movement))->unique() as $id) {
            $acc = $accounts->get($id);
            $code = (string) ($acc->code ?? '');
            $opening = $acc ? \App\Services\YearClosing::signedOpening($acc) : 0.0;
            $openingImbalance += $opening;
            $mv = $movement[$id] ?? [];

            if (\App\Services\YearClosing::isProfitLossCode($code)) {
                $profitBefore -= $opening + ($mv[0] ?? 0);
                foreach (range(1, 12) as $m) {
                    $profitMonth[$m] -= $mv[$m] ?? 0;
                }
                continue;
            }

            $section = ['1' => 'aktiva', '2' => 'kewajiban', '3' => 'ekuitas'][substr($code, 0, 1)] ?? 'lainnya';
            $sign = in_array($section, ['kewajiban', 'ekuitas'], true) ? -1 : 1;

            $running = $opening + ($mv[0] ?? 0);
            $values = [];
            $nonZero = abs($running) >= 0.5;
            foreach (range(1, 12) as $m) {
                $running += $mv[$m] ?? 0;
                $values[$m] = $months[$m]['filled'] ? round($sign * $running, 2) : null;
                $nonZero = $nonZero || abs($mv[$m] ?? 0) >= 0.5;
            }

            if ($nonZero) {
                $sections[$section][] = (object) ['kode' => $code ?: '-', 'nama' => $acc->name ?? 'Akun #' . $id, 'values' => $values];
            }
        }

        // profit in equity
        $running = 0.0;
        $before = $berjalan = [];
        foreach (range(1, 12) as $m) {
            $running += $profitMonth[$m];
            $before[$m]   = $months[$m]['filled'] ? round($profitBefore, 2) : null;
            $berjalan[$m] = $months[$m]['filled'] ? round($running, 2) : null;
        }
        if (abs($profitBefore) >= 0.5) {
            $sections['ekuitas'][] = (object) ['kode' => '', 'nama' => 'Laba tahun-tahun lalu (belum tutup buku)', 'values' => $before, 'computed' => true];
        }
        $sections['ekuitas'][] = (object) ['kode' => '', 'nama' => 'Laba tahun berjalan ' . $year, 'values' => $berjalan, 'computed' => true];

        foreach ($sections as $key => $rows) {
            usort($rows, fn ($a, $b) => [!empty($a->computed), $a->kode] <=> [!empty($b->computed), $b->kode]);
            $sections[$key] = $rows;
        }

        $total = fn (string $key) => array_map(
            fn ($m) => $months[$m]['filled'] ? round(array_sum(array_map(fn ($r) => $r->values[$m] ?? 0, $sections[$key])), 2) : null,
            array_combine(range(1, 12), range(1, 12))
        );
        $totals = ['aktiva' => $total('aktiva'), 'kewajiban' => $total('kewajiban'), 'ekuitas' => $total('ekuitas'), 'lainnya' => $total('lainnya')];

        $difference = [];
        foreach (range(1, 12) as $m) {
            $difference[$m] = $months[$m]['filled']
                ? round($totals['aktiva'][$m] + $totals['lainnya'][$m] - $totals['kewajiban'][$m] - $totals['ekuitas'][$m], 2)
                : null;
        }

        $firstYear = (int) (DB::table('journals')->whereNull('deleted_at')->min(DB::raw('year(journal_date)')) ?: $currentYear);

        return [
            'year'             => $year,
            'years'            => range($currentYear, min($firstYear, $year)),
            'months'           => $months,
            'sections'         => $sections,
            'totals'           => $totals,
            'difference'       => $difference,
            'openingImbalance' => round($openingImbalance, 2),
            'closed'           => \App\Services\YearClosing::isClosed($year),
            'lockedUntil'      => \App\Services\PeriodLock::lockedUntil(),
            'canClose'         => $year < $currentYear,
        ];
    }
}
