<?php

namespace App\Console\Commands;

use App\Models\FinancialReportRepository;
use App\Models\Good;
use App\Services\CekKecocokan;
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

        // Same rules as the daily check after Tutup Kasir (App\Services\CekKecocokan).
        foreach (CekKecocokan::selisihJurnal($from, $to) as $r) {
            $this->add($r['tanggal'], $r['cek'], $r['seharusnya'] ?? '-', $r['aktual'], $r['keterangan']);
        }

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
            $counters = StockService::countersMany($goods->pluck('id')->all());
            foreach ($goods as $good) {
                if (round((float) $good->last_stock, 2) != $counters[$good->id]['last_stock']) {
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
