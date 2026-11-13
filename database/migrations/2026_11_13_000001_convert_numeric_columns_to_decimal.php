<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Number columns that were stored as varchar become DECIMAL.
 *
 * As text, nothing stopped "12,500", "12.500,50" or a value 100x too large
 * from being saved (see docs/sql/2026-11-11-koreksi-harga-100x.sql).
 *
 * Nothing is converted blindly: the migration first checks every column and
 * stops, changing nothing, if any value is not a plain number ("12500",
 * "12500.50", "-3.5") or has more decimals than the new column keeps.
 * Empty strings and the text 'NULL' are turned into real NULL.
 * Run docs/sql/2026-11-13-cek-data-sebelum-decimal.sql to see the rows.
 */
return new class extends Migration
{
    private const PLAIN_NUMBER = '^-?[0-9]+([.][0-9]+)?$';

    /** [table, column, decimal places kept] */
    private function columns(): array
    {
        return [
            ['accounts', 'balance', 2],
            ['delivery_fees', 'fee', 2],
            ['good_loading_details', 'price', 2],
            ['good_loading_details', 'selling_price', 2],
            ['good_loadings', 'total_item_price', 2],
            ['good_prices', 'old_price', 2],
            ['good_prices', 'recent_price', 2],
            ['good_prices', 'old_buy_price', 2],
            ['good_prices', 'recent_buy_price', 2],
            ['piutang_payments', 'money', 2],
            ['transactions', 'voucher_nominal', 2],
            ['vouchers', 'nominal', 2],
            // quantities and stock keep 4 decimals
            ['good_loading_details', 'last_stock', 4],
            ['stock_opname_details', 'old_stock', 4],
            ['stock_opname_details', 'new_stock', 4],
            ['stock_opname_details', 'total', 4],
            ['stock_opnames', 'total', 4],
            ['units', 'quantity', 4],
        ];
    }

    private function type(int $places): string
    {
        return $places === 2 ? 'DECIMAL(14,2)' : 'DECIMAL(14,4)';
    }

    public function up(): void
    {
        // A value that does not fit must raise an error, not become 0.
        DB::statement("SET SESSION sql_mode = CONCAT(@@sql_mode, ',STRICT_ALL_TABLES')");

        $problems = [];

        foreach ($this->columns() as [$table, $column, $places]) {
            if (! $this->isText($table, $column)) {
                continue; // already converted
            }

            $blank = "TRIM(`$column`) IN ('', 'NULL', 'null')";
            $dirty = DB::table($table)
                ->whereNotNull($column)
                ->whereRaw("NOT ($blank)")
                ->whereRaw("TRIM(`$column`) NOT REGEXP ?", [self::PLAIN_NUMBER])
                ->count();
            $tooLong = DB::table($table)
                ->whereNotNull($column)
                ->whereRaw("TRIM(`$column`) REGEXP ?", [self::PLAIN_NUMBER])
                ->whereRaw("TRIM(`$column`) REGEXP ?", ['[.][0-9]{' . ($places + 1) . ',}'])
                ->whereRaw("TRIM(`$column`) NOT REGEXP ?", ['[.][0-9]{' . $places . '}0*$'])
                ->count();

            if ($dirty > 0) {
                $problems[] = "$table.$column: $dirty bukan angka murni";
            }
            if ($tooLong > 0) {
                $problems[] = "$table.$column: $tooLong punya lebih dari $places desimal";
            }
        }

        if ($problems) {
            throw new RuntimeException(
                "Migrasi dihentikan, tidak ada yang diubah. Bersihkan dulu data berikut "
                . "(lihat docs/sql/2026-11-13-cek-data-sebelum-decimal.sql):\n - "
                . implode("\n - ", $problems)
            );
        }

        foreach ($this->columns() as [$table, $column, $places]) {
            if (! $this->isText($table, $column)) {
                continue;
            }

            DB::statement("UPDATE `$table` SET `$column` = NULL WHERE TRIM(`$column`) IN ('', 'NULL', 'null')");
            DB::statement("UPDATE `$table` SET `$column` = TRIM(`$column`) WHERE `$column` <> TRIM(`$column`)");
            DB::statement("ALTER TABLE `$table` MODIFY `$column` " . $this->type($places) . ' NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        foreach ($this->columns() as [$table, $column]) {
            DB::statement("ALTER TABLE `$table` MODIFY `$column` VARCHAR(255) NULL DEFAULT NULL");
        }
    }

    private function isText(string $table, string $column): bool
    {
        $type = DB::table('information_schema.columns')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('data_type');

        return in_array(strtolower((string) $type), ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true);
    }
};
