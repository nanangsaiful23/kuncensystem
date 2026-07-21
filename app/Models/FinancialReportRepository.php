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

    /**
     * Jurnal umum lengkap dalam periode.
     * Otomatis menampilkan semua akun yang ada di journals,
     * termasuk akun baru yang ditambahkan.
     */
    public function getGeneralJournal($startDate = null, $endDate = null): Collection
    {
        $query = Journal::join('accounts AS da', 'da.id', '=', 'journals.debit_account_id')
            ->join('accounts AS ca', 'ca.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->select(
                'journals.id',
                'journals.journal_date',
                'journals.name         AS keterangan',
                'journals.type         AS tipe',
                'da.code               AS kode_debit',
                'da.name               AS akun_debit',
                'da.color              AS color_debit',
                'journals.debit',
                'ca.code               AS kode_kredit',
                'ca.name               AS akun_kredit',
                'ca.color              AS color_kredit',
                'journals.credit'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('journals.journal_date', [$startDate, $endDate]);
        }

        return $query->orderBy('journals.journal_date', 'asc')
                     ->orderBy('journals.id', 'asc')
                     ->get();
    }

    // =========================================================================
    // C. BUKU BESAR — Saldo per akun, dinamis
    // =========================================================================

    /**
     * Rekap mutasi & saldo per akun dalam periode.
     * DINAMIS: semua akun yang punya transaksi jurnal akan muncul otomatis.
     */
    public function getLedgerByAccount($startDate = null, $endDate = null): Collection
    {
        // Saldo debit per akun
        $debitQuery = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.debit_account_id')
            ->whereNull('journals.deleted_at')
            ->select(
                'accounts.id   AS account_id',
                'accounts.code AS kode',
                'accounts.name AS nama',
                'accounts.type AS tipe',
                'accounts.group AS kelompok',
                'accounts.activa AS aktiva_pasiva',
                'accounts.color AS color',
                DB::raw('COALESCE(SUM(journals.debit), 0)  AS total_debit'),
                DB::raw('0                                  AS total_kredit')
            )
            ->groupBy(
                'accounts.id', 'accounts.code', 'accounts.name',
                'accounts.type', 'accounts.group', 'accounts.activa', 'accounts.color'
            );

        // Saldo kredit per akun
        $creditQuery = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->select(
                'accounts.id   AS account_id',
                'accounts.code AS kode',
                'accounts.name AS nama',
                'accounts.type AS tipe',
                'accounts.group AS kelompok',
                'accounts.activa AS aktiva_pasiva',
                'accounts.color AS color',
                DB::raw('0                                   AS total_debit'),
                DB::raw('COALESCE(SUM(journals.credit), 0)  AS total_kredit')
            )
            ->groupBy(
                'accounts.id', 'accounts.code', 'accounts.name',
                'accounts.type', 'accounts.group', 'accounts.activa', 'accounts.color'
            );

        if ($startDate && $endDate) {
            $debitQuery->whereBetween('journals.journal_date', [$startDate, $endDate]);
            $creditQuery->whereBetween('journals.journal_date', [$startDate, $endDate]);
        }

        // Union dan agregasi di PHP (lebih kompatibel dengan Laravel 6 + MySQL)
        $debits  = $debitQuery->get()->keyBy('account_id');
        $credits = $creditQuery->get()->keyBy('account_id');

        // Gabungkan semua account_id dari kedua sisi
        $allIds = $debits->keys()->merge($credits->keys())->unique();

        return $allIds->map(function ($id) use ($debits, $credits) {
            $d = $debits->get($id);
            $c = $credits->get($id);
            $base = $d ?? $c;

            $totalDebit  = (float) ($d->total_debit  ?? 0);
            $totalKredit = (float) ($c->total_kredit ?? 0);
            $saldo       = $totalDebit - $totalKredit;

            return (object) [
                'account_id'    => $base->account_id,
                'kode'          => $base->kode,
                'nama'          => $base->nama,
                'tipe'          => $base->tipe,
                'kelompok'      => $base->kelompok,
                'aktiva_pasiva' => $base->aktiva_pasiva,
                'color'         => $base->color,
                'total_debit'   => $totalDebit,
                'total_kredit'  => $totalKredit,
                'saldo'         => $saldo,  // positif = debit lebih besar
            ];
        })->sortBy('kode')->values();
    }

    // =========================================================================
    // D. LAPORAN LABA RUGI — Dinamis berbasis akun Laba Rugi + kode 5xxx/4xxx
    // =========================================================================

    /**
     * Laba Rugi dihitung dari jurnal berdasarkan kode akun:
     *  - 4xxx (Pendapatan)   → masuk sisi kredit  (menambah laba)
     *  - 5xxx (Beban/Biaya)  → masuk sisi debit   (mengurangi laba)
     *
     * DINAMIS: jika ada akun 5225, 5226 baru, otomatis masuk ke perhitungan.
     */
    public function getProfitLossReport($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        // ── Pendapatan: semua akun grup 4xxx (kredit) ─────────────────────
        $pendapatan = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->where('accounts.code', 'like', '4%')
            ->whereBetween('journals.journal_date', [
                $start->toDateString(), $end->toDateString()
            ])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.color',
                DB::raw('COALESCE(SUM(journals.credit), 0) AS total')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.color')
            ->orderBy('accounts.code')
            ->get();

        // ── Beban/Biaya: semua akun grup 5xxx (debit) ─────────────────────
        $beban = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.debit_account_id')
            ->whereNull('journals.deleted_at')
            ->where('accounts.code', 'like', '5%')
            ->whereBetween('journals.journal_date', [
                $start->toDateString(), $end->toDateString()
            ])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.color',
                DB::raw('COALESCE(SUM(journals.debit), 0) AS total')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.color')
            ->orderBy('accounts.code')
            ->get();

        // ── Pendapatan lain-lain: akun 6xxx (kredit) ──────────────────────
        $pendapatanLain = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->where('accounts.code', 'like', '6%')
            ->whereBetween('journals.journal_date', [
                $start->toDateString(), $end->toDateString()
            ])
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.color',
                DB::raw('COALESCE(SUM(journals.credit), 0) AS total')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.color')
            ->orderBy('accounts.code')
            ->get();

        $totalPendapatan     = (float) $pendapatan->sum('total');
        $totalBeban          = (float) $beban->sum('total');
        $totalPendapatanLain = (float) $pendapatanLain->sum('total');

        // HPP dipisah dari beban lain (kode 5101)
        $hpp = $beban->where('code', '5101')->sum('total');
        $labaKotor  = $totalPendapatan - $hpp;
        $bebanUsaha = $beban->where('code', '!=', '5101')->sum('total');
        $labaBersih = $labaKotor - $bebanUsaha + $totalPendapatanLain;

        return [
            'pendapatan'          => $pendapatan,
            'beban'               => $beban,
            'pendapatan_lain'     => $pendapatanLain,
            'total_pendapatan'    => $totalPendapatan,
            'hpp'                 => $hpp,
            'laba_kotor'          => $labaKotor,
            'beban_usaha'         => $beban->where('code', '!=', '5101')->values(),
            'total_beban_usaha'   => $bebanUsaha,
            'total_pendapatan_lain'=> $totalPendapatanLain,
            'laba_bersih'         => $labaBersih,
        ];
    }

    // =========================================================================
    // E. NERACA — Dinamis berbasis akun 1xxx (Aktiva) & 2xxx/3xxx (Pasiva)
    // =========================================================================

    /**
     * Neraca dihitung dari saldo kumulatif jurnal per akun neraca.
     * DINAMIS: akun baru 1xxx/2xxx/3xxx otomatis masuk.
     */
    public function getBalanceSheet($upToDate = null): array
    {
        $upTo = $upToDate ? Carbon::parse($upToDate)->endOfDay()->toDateString() : date('Y-m-d');

        // Saldo debit kumulatif per akun sampai tanggal tertentu
        $debitSaldo = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.debit_account_id')
            ->whereNull('journals.deleted_at')
            ->where('journals.journal_date', '<=', $upTo)
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.group',
                'accounts.activa',
                'accounts.color',
                DB::raw('COALESCE(SUM(journals.debit), 0)  AS saldo_debit'),
                DB::raw('0                                  AS saldo_kredit')
            )
            ->groupBy('accounts.id','accounts.code','accounts.name',
                      'accounts.type','accounts.group','accounts.activa','accounts.color')
            ->get()->keyBy('id');

        $creditSaldo = DB::table('journals')
            ->join('accounts', 'accounts.id', '=', 'journals.credit_account_id')
            ->whereNull('journals.deleted_at')
            ->where('journals.journal_date', '<=', $upTo)
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.group',
                'accounts.activa',
                'accounts.color',
                DB::raw('0                                   AS saldo_debit'),
                DB::raw('COALESCE(SUM(journals.credit), 0)  AS saldo_kredit')
            )
            ->groupBy('accounts.id','accounts.code','accounts.name',
                      'accounts.type','accounts.group','accounts.activa','accounts.color')
            ->get()->keyBy('id');

        $allIds = $debitSaldo->keys()->merge($creditSaldo->keys())->unique();

        $aktiva  = collect();
        $pasiva  = collect();
        $ekuitas = collect();

        foreach ($allIds as $id) {
            $d    = $debitSaldo->get($id);
            $c    = $creditSaldo->get($id);
            $base = $d ?? $c;

            $code   = $base->code ?? '';
            $prefix = substr($code, 0, 1);

            // Hanya akun neraca (1xxx, 2xxx, 3xxx)
            if (!in_array($prefix, ['1', '2', '3'])) continue;

            $debit  = (float)($d->saldo_debit  ?? 0);
            $kredit = (float)($c->saldo_kredit ?? 0);
            $saldo  = $debit - $kredit;

            $item = (object) [
                'account_id' => $id,
                'kode'       => $code,
                'nama'       => $base->name,
                'tipe'       => $base->type,
                'aktiva_p'   => strtolower(trim($base->activa ?? '')),
                'color'      => $base->color,
                'saldo'      => abs($saldo),
            ];

            if ($prefix === '1') {
                $aktiva->push($item);
            } elseif ($prefix === '2') {
                $pasiva->push($item);
            } else {
                $ekuitas->push($item);
            }
        }

        $aktiva  = $aktiva->sortBy('kode')->values();
        $pasiva  = $pasiva->sortBy('kode')->values();
        $ekuitas = $ekuitas->sortBy('kode')->values();

        $totalAktiva  = $aktiva->sum('saldo');
        $totalPasiva  = $pasiva->sum('saldo');
        $totalEkuitas = $ekuitas->sum('saldo');

        return compact(
            'aktiva', 'pasiva', 'ekuitas',
            'totalAktiva', 'totalPasiva', 'totalEkuitas',
            'upTo'
        );
    }

    // =========================================================================
    // F. RINGKASAN KAS — Semua akun kas (kode 111x) dalam periode
    // =========================================================================

    /**
     * Mutasi semua akun kas dalam periode.
     * DINAMIS: akun kas baru (1113, 1114, 1116, dst) otomatis masuk.
     */
    public function getCashFlowSummary($startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end   = Carbon::parse($endDate)->toDateString();

        $kasAccounts = Account::whereNull('deleted_at')
            ->where('code', 'like', '111%')
            ->orderBy('code')
            ->get();

        return $kasAccounts->map(function ($acc) use ($start, $end) {
            $masuk = DB::table('journals')
                ->whereNull('deleted_at')
                ->where('debit_account_id', $acc->id)
                ->whereBetween('journal_date', [$start, $end])
                ->sum('debit');

            $keluar = DB::table('journals')
                ->whereNull('deleted_at')
                ->where('credit_account_id', $acc->id)
                ->whereBetween('journal_date', [$start, $end])
                ->sum('credit');

            // Saldo awal (sebelum periode)
            $saldoAwalDebit = DB::table('journals')
                ->whereNull('deleted_at')
                ->where('debit_account_id', $acc->id)
                ->where('journal_date', '<', $start)
                ->sum('debit');

            $saldoAwalKredit = DB::table('journals')
                ->whereNull('deleted_at')
                ->where('credit_account_id', $acc->id)
                ->where('journal_date', '<', $start)
                ->sum('credit');

            $saldoAwal  = (float)$saldoAwalDebit - (float)$saldoAwalKredit;
            $saldoAkhir = $saldoAwal + (float)$masuk - (float)$keluar;

            return (object) [
                'account_id'  => $acc->id,
                'kode'        => $acc->code,
                'nama'        => $acc->name,
                'color'       => $acc->color ?? '#94a3b8',
                'saldo_awal'  => $saldoAwal,
                'masuk'       => (float) $masuk,
                'keluar'      => (float) $keluar,
                'saldo_akhir' => $saldoAkhir,
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

        return [
            'total_nilai_persediaan' => $totalNilai,
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
     * @param  string      $accountCode  Kode akun di tabel accounts (mis. '1141')
     * @param  string|null $upToDate     Batas tanggal (default: hari ini)
     */
    public function getAccountBalanceByCode(string $accountCode, ?string $upToDate = null): float
    {
        $upTo = $upToDate ?? date('Y-m-d');

        $accountId = Account::whereNull('deleted_at')
            ->where('code', $accountCode)
            ->value('id');

        if (!$accountId) {
            return 0.0;
        }

        $debit = DB::table('journals')
            ->whereNull('deleted_at')
            ->where('debit_account_id', $accountId)
            ->where('journal_date', '<=', $upTo)
            ->sum('debit');

        $credit = DB::table('journals')
            ->whereNull('deleted_at')
            ->where('credit_account_id', $accountId)
            ->where('journal_date', '<=', $upTo)
            ->sum('credit');

        return (float) $debit - (float) $credit;
    }
}