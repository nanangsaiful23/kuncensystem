<?php

namespace App\Console\Commands;

use App\Services\PeriodLock;
use App\Services\YearClosing;
use Illuminate\Console\Command;

/**
 *   php artisan kuncen:close-year 2025            tutup buku 2025 (ulang bila sudah)
 *   php artisan kuncen:close-year 2025 --reopen   buka kembali 2025 (dan tahun sesudahnya)
 */
class CloseYear extends Command
{
    protected $signature = 'kuncen:close-year {year : Tahun yang ditutup} {--reopen : Buka kembali tahun itu}';

    protected $description = 'Tutup buku tahunan: pindahkan laba rugi ke Laba Ditahan dan kunci periodenya';

    public function handle(): int
    {
        $year = (int) $this->argument('year');

        if ($this->option('reopen')) {
            $n = YearClosing::reopen($year);
            $this->info($n ? "Tahun $year dibuka kembali ($n tahun)." : "Tahun $year belum ditutup.");
        } else {
            $profit = YearClosing::close($year);
            $this->info("Tutup buku $year selesai. Laba dipindah ke Laba Ditahan: " . number_format($profit, 0, ',', '.'));
        }

        $until = PeriodLock::lockedUntil();
        $this->line($until ? "Terkunci sampai: $until" : 'Tidak ada periode terkunci.');

        return self::SUCCESS;
    }
}
