<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Basic checks that the app boots on Laravel 13 and that pages which need
 * no database render (these use the LaravelCollective Form:: builder).
 */
class SmokeTest extends TestCase
{
    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="password"', false);
    }

    public function test_cashier_login_page_renders(): void
    {
        $this->get('/cashier/login')
            ->assertOk()
            ->assertSee('name="password"', false);
    }

    #[DataProvider('protectedPages')]
    public function test_protected_pages_redirect_guests_to_their_login(string $url, string $login): void
    {
        $this->get($url)->assertRedirect($login);
    }

    public static function protectedPages(): array
    {
        return [
            'admin home' => ['/admin', '/admin/login'],
            'admin report' => ['/admin/reports/sales', '/admin/login'],
            'cashier home' => ['/cashier', '/cashier/login'],
        ];
    }

    public function test_route_groups_keep_their_names_and_middleware(): void
    {
        $admin = Route::getRoutes()->getByName('admin.account.store');
        $this->assertNotNull($admin);
        $this->assertSame('admin/account/store', $admin->uri());
        $this->assertContains('auth:admin', $admin->gatherMiddleware());

        $cashier = Route::getRoutes()->getByName('cashier.good.store');
        $this->assertNotNull($cashier);
        $this->assertContains('auth:cashier', $cashier->gatherMiddleware());

        $api = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'api/chatbot/search');
        $this->assertNotNull($api);
        $this->assertContains('api', $api->gatherMiddleware());
    }
}
