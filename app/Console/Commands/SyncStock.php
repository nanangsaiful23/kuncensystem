<?php

namespace App\Console\Commands;

use App\Models\Good;
use App\Services\StockService;
use Illuminate\Console\Command;

/**
 * Recalculate goods.total_loading / total_transaction / last_stock from the
 * loading and transaction history (the same numbers getStock() shows).
 *
 *   php artisan stock:sync --dry-run   show what would change
 *   php artisan stock:sync             write it
 */
class SyncStock extends Command
{
    protected $signature = 'stock:sync {--dry-run : Only list the differences} {--good=* : Only these good IDs}';

    protected $description = 'Samakan stok di tabel goods dengan riwayat loading/transaksi';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $query = Good::query()->orderBy('id');

        if ($ids = $this->option('good')) {
            $query->whereIn('id', $ids);
        }

        $rows = [];
        $checked = 0;

        $query->chunkById(500, function ($goods) use ($dry, &$rows, &$checked) {
            foreach ($goods as $good) {
                $checked++;
                $new = StockService::counters($good);
                $old = round((float) $good->last_stock, 2);

                if ($old == $new['last_stock']
                    && round((float) $good->total_loading, 2) == $new['total_loading']
                    && round((float) $good->total_transaction, 2) == $new['total_transaction']) {
                    continue;
                }

                $rows[] = [$good->id, mb_strimwidth($good->name, 0, 40, '…'), $old, $new['last_stock'], round($new['last_stock'] - $old, 2)];

                if (! $dry) {
                    StockService::sync($good->id);
                }
            }
        });

        if ($rows) {
            $this->table(['ID', 'Barang', 'last_stock lama', 'dari riwayat', 'selisih'], $rows);
        }

        $this->info(sprintf('%d barang dicek, %d berbeda%s.', $checked, count($rows), $dry ? ' (dry-run, belum diubah)' : ', sudah disamakan'));

        return self::SUCCESS;
    }
}
