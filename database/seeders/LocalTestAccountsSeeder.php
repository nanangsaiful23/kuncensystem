<?php

namespace Database\Seeders;

use App\Admin;
use App\Cashier;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates (or resets) the local test logins `test_admin` and `test_kasir`.
 *
 * Only for a local copy of the database. Passwords come from .env:
 *   TEST_ADMIN_PASSWORD=...
 *   TEST_CASHIER_PASSWORD=...
 *
 *   php artisan db:seed --class=Database\\Seeders\\LocalTestAccountsSeeder
 *
 * The login controllers append a pepper to the typed password before
 * checking it, so the same pepper is added here.
 */
class LocalTestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('LocalTestAccountsSeeder only runs when APP_ENV is local or testing.');
        }

        $adminPassword = env('TEST_ADMIN_PASSWORD');
        $cashierPassword = env('TEST_CASHIER_PASSWORD');

        if (! $adminPassword || ! $cashierPassword) {
            throw new RuntimeException('Set TEST_ADMIN_PASSWORD and TEST_CASHIER_PASSWORD in .env first.');
        }

        Admin::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'test_admin'],
            [
                'name' => 'Test Admin (local)',
                'password' => bcrypt($adminPassword.'<4d[M!n}'),
                'is_active' => 1,
                'role' => 'admin',
            ]
        );

        Cashier::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'test_kasir'],
            [
                'name' => 'Test Kasir (local)',
                'password' => bcrypt($cashierPassword.'k&4z~1e1R*'),
                'is_active' => 1,
            ]
        );
    }
}
