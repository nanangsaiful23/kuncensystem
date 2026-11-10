<?php

namespace App\Console\Commands;

use App\Models\FinancialReportRepository;
use App\Models\Good;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only check of sales, HPP and stock, day by day. It replaces comparing
 * the day's sales journal with the transactions by hand. Nothing is changed.
 *
 *   php artisan kuncen:audit                       last 30 days
 *   php artisan kuncen:audit --from=2026-11-01 --to=2026-11-09
 */
class AuditJournals extends Command
{
    protected $signature = 'kuncen:audit {--from= : Tanggal awal (Y-m-d)} {--to= : Tanggal akhir (Y-m-d)}';

    protected $description = 'Cek jurnal penjualan, HPP, retur dan stok terhadap data transaksi (hanya membaca)';

    private array $problems = [];

    public function handle(): int
    {
        $to   = $this->option('to') ?: date('Y-m-d');
        $from = $this->option('from') ?: date('Y-m-d', strtotime($to . ' -29 days'));

        $acc = DB::table('accounts')->whereNull('deleted_at')->pluck('id', 'code');

        $this->checkTransactionsWithoutDetails($from, $to);
        $this->checkDailySales($from, $to, $acc);
        $this->checkDailyHpp($from, $to, $acc);
        $this->checkRetur($from, $to, $acc);
        $this->checkReversals($from, $to, $acc);

        if ($this->problems) {
            $this->table(['Tanggal', 'Cek', 'Seharusnya', 'Di jurnal / data', 'Keterangan'], $this->problems);
        } else {
            $this->info("Tidak ada selisih penjualan/HPP/retur antara $from dan $to.");
        }

        $this->checkClosedYears();
        $this->checkStock();
        $this->checkInventory();

        return self::SUCCESS;
    }

    private function add($date, $check, $expected, $actual, $note = ''): void
    {
        $fmt = fn ($v) => is_numeric($v) ? number_format((float) $v, 0, ',', '.') : $v;
        $this->problems[] = [$date, $check, $fmt($expected), $fmt($actual), $note];
    }

    private function checkTransactionsWithoutDetails(string $from, string $to): void
    {
        $rows = DB::select("select t.id, date(t.created_at) d, t.total_sum_price from transactions t
            where t.deleted_at is null and t.type in ('normal','retur') and date(t.created_at) between ? and ?
              and not exists (select 1 from transaction_details d where d.transaction_id = t.id and d.deleted_at is null)", [$from, $to]);

        foreach ($rows as $r) {
            $this->add($r->d, 'Transaksi tanpa detail', '-', $r->total_sum_price, 'ID transaksi ' . $r->id);
        }
    }

    /** Money journaled at the sale: sold lines - discount - voucher. */
    private function saleMoneySql(): string
    {
        return "(select coalesce(sum(d.sum_price),0) from transaction_details d where d.transaction_id = t.id and d.deleted_at is null and coalesce(d.type,'') <> 'retur')
                - coalesce(t.total_discount_price,0) - coalesce(t.voucher_nominal,0)";
    }

    private function checkDailySales(string $from, string $to, $acc): void
    {
        // transactions still counted as made that day (also the ones cancelled later:
        // the cancel has its own journal)
        $expected = collect(DB::select("select date(t.created_at) d, sum(" . $this->saleMoneySql() . ") v from transactions t
            where t.deleted_at is null and t.payment = 'cash' and t.type in ('normal','retur','not valid','deleted')
              and date(t.created_at) between ? and ? group by 1", [$from, $to]))->pluck('v', 'd');

        $journal = collect(DB::select("select journal_date d, sum(debit) v from journals where deleted_at is null and type = 'transaction'
            and debit_account_id = ? and credit_account_id = ? and type_id is null and journal_date between ? and ? group by 1",
            [$acc['1111'], $acc['4101'], $from, $to]))->pluck('v', 'd');

        foreach ($expected->keys()->merge($journal->keys())->unique()->sort() as $d) {
            $e = (float) ($expected[$d] ?? 0);
            $j = (float) ($journal[$d] ?? 0);
            if (abs($e - $j) >= 1) {
                $this->add($d, 'Penjualan tunai', $e, $j, 'selisih ' . number_format($j - $e, 0, ',', '.'));
            }
        }

        $transfers = DB::select("select t.id, date(t.created_at) d, " . $this->saleMoneySql() . " v,
                (select sum(j.debit) from journals j where j.deleted_at is null and j.type = 'transaction' and j.type_id = t.id) j
            from transactions t where t.deleted_at is null and t.payment = 'transfer' and t.type in ('normal','retur','not valid','deleted')
              and date(t.created_at) between ? and ?", [$from, $to]);

        foreach ($transfers as $r) {
            if (abs((float) $r->v - (float) $r->j) >= 1) {
                $this->add($r->d, 'Penjualan transfer', $r->v, (float) $r->j, 'ID transaksi ' . $r->id);
            }
        }
    }

    private function checkDailyHpp(string $from, string $to, $acc): void
    {
        $expected = collect(DB::select("select date(t.created_at) d, sum(d.quantity * d.buy_price) v
            from transaction_details d join transactions t on t.id = d.transaction_id
            where t.deleted_at is null and d.deleted_at is null and coalesce(d.type,'') <> 'retur'
              and t.payment in ('cash','transfer') and t.type in ('normal','retur','not valid','deleted') and date(t.created_at) between ? and ? group by 1", [$from, $to]))->pluck('v', 'd');

        $journal = collect(DB::select("select journal_date d, sum(credit) v from journals where deleted_at is null and type = 'hpp'
            and debit_account_id = ? and credit_account_id = ? and name like 'Penjualan tanggal %' and journal_date between ? and ? group by 1",
            [$acc['5101'], $acc['1141'], $from, $to]))->pluck('v', 'd');

        $wrongWay = collect(DB::select("select journal_date d, sum(debit) v from journals where deleted_at is null and type = 'hpp'
            and debit_account_id = ? and (name like 'Penjualan reverse%' or name like 'Penjualan delete%')
            and journal_date between ? and ? group by 1", [$acc['1141'], $from, $to]))->pluck('v', 'd');

        foreach ($expected->keys()->merge($journal->keys())->unique()->sort() as $d) {
            $e = (float) ($expected[$d] ?? 0);
            $j = (float) ($journal[$d] ?? 0);
            if (abs($e - $j) >= 1) {
                $note = $j == 0 && $e > 0 && isset($wrongWay[$d]) ? 'HPP hari ini kemungkinan tercatat di jurnal reverse (arah terbalik)' : 'selisih ' . number_format($j - $e, 0, ',', '.');
                $this->add($d, 'HPP penjualan', $e, $j, $note);
            }
        }
    }

    private function checkRetur(string $from, string $to, $acc): void
    {
        $rows = DB::select("select t.id, date(t.created_at) d, sum(d.quantity * d.buy_price) hpp,
                (select count(*) from journals j where j.deleted_at is null and j.type = 'hpp' and j.name like concat('HPP retur barang (ID transaksi ', t.id, ')%')) n_journal
            from transactions t join transaction_details d on d.transaction_id = t.id and d.type = 'retur' and d.deleted_at is null
            where t.deleted_at is null and t.type = 'retur' and date(t.created_at) between ? and ? group by t.id, date(t.created_at)", [$from, $to]);

        foreach ($rows as $r) {
            if ($r->n_journal == 0) {
                $this->add($r->d, 'Retur tanpa jurnal HPP', $r->hpp, 0, 'ID transaksi ' . $r->id . ' (stok/loading retur perlu dicek)');
            }
        }
    }

    private function checkReversals(string $from, string $to, $acc): void
    {
        $rows = DB::select("select t.id, date(t.updated_at) d,
                (select coalesce(sum(d.quantity * d.buy_price),0) from transaction_details d where d.transaction_id = t.id and coalesce(d.type,'') <> 'retur') hpp,
                (select count(*) from journals j where j.deleted_at is null and j.type = 'hpp'
                   and (j.name like concat('Penjualan reverse transaction ID ', t.id, ' %') or j.name like concat('Penjualan delete transaction ID ', t.id, ' %'))) n_journal
            from transactions t where t.deleted_at is null and t.type in ('not valid','deleted') and t.payment in ('cash','transfer')
              and date(t.updated_at) between ? and ?", [$from, $to]);

        foreach ($rows as $r) {
            if ($r->n_journal == 0 && $r->hpp > 0) {
                $this->add($r->d, 'Batal transaksi tanpa jurnal HPP', $r->hpp, 0, 'ID transaksi ' . $r->id);
            }
        }
    }

    /** A closed year changed afterwards (super admin): its closing must be done again. */
    private function checkClosedYears(): void
    {
        foreach (DB::table('journals')->whereNull('deleted_at')->where('type', 'closing')->distinct()->pluck('type_id') as $year) {
            $left = array_sum(array_map(fn ($a) => -$a->debit_minus_credit, \App\Services\YearClosing::profitLossBalances("$year-12-31")));
            if (abs($left) >= 1) {
                $this->warn("Tahun $year berubah setelah tutup buku (laba rugi tersisa " . number_format($left, 0, ',', '.')
                    . "). Jalankan: php artisan kuncen:close-year $year");
            }
        }
    }

    private function checkStock(): void
    {
        $bad = 0;
        Good::query()->chunkById(500, function ($goods) use (&$bad) {
            foreach ($goods as $good) {
                if (round((float) $good->last_stock, 2) != StockService::counters($good)['last_stock']) {
                    $bad++;
                }
            }
        });

        $bad
            ? $this->warn("$bad barang: last_stock berbeda dengan riwayat. Jalankan: php artisan stock:sync --dry-run")
            : $this->info('Stok semua barang sama dengan riwayat loading/transaksi.');
    }

    private function checkInventory(): void
    {
        $repo   = new FinancialReportRepository();
        $total  = $repo->getInventoryAssetTotal();
        $fisik  = $total['total_nilai_persediaan'];
        $jurnal = $repo->getAccountBalanceByCode('1141');

        $this->line(sprintf('Nilai persediaan fisik: %s (rak %s + kotak retur %s) | jurnal 1141: %s | selisih: %s',
            number_format($fisik, 0, ',', '.'), number_format($total['nilai_rak'], 0, ',', '.'),
            number_format($total['nilai_kotak_retur'], 0, ',', '.'), number_format($jurnal, 0, ',', '.'),
            number_format($fisik - $jurnal, 0, ',', '.')));
    }
}
