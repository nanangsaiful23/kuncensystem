<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        using: function () {
            // Same route groups the old RouteServiceProvider registered.
            // Routes still use 'Controller@method' strings, so each group keeps
            // its controller namespace.
            Route::prefix('api')
                ->middleware('api')
                ->namespace('App\Http\Controllers')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace('App\Http\Controllers')
                ->group(base_path('routes/web.php'));

            Route::middleware(['web', 'cashier', 'auth:cashier'])
                ->prefix('cashier')
                ->as('cashier.')
                ->namespace('App\Http\Controllers\Cashier')
                ->group(base_path('routes/cashier.php'));

            Route::middleware(['web', 'admin', 'auth:admin'])
                ->prefix('admin')
                ->as('admin.')
                ->namespace('App\Http\Controllers\Admin')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'cashier' => \App\Http\Middleware\RedirectIfNotCashier::class,
            'cashier.guest' => \App\Http\Middleware\RedirectIfCashier::class,
            'admin' => \App\Http\Middleware\RedirectIfNotAdmin::class,
            'admin.guest' => \App\Http\Middleware\RedirectIfAdmin::class,
        ]);

        // Old App\Http\Kernel: the api group was throttled to 60 requests/minute.
        $middleware->prependToGroup('api', ThrottleRequests::class.':60,1');

        // Guests hitting a protected page go to the matching login page.
        // (Both login routes are named 'login', so route('login') was ambiguous.)
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('cashier', 'cashier/*') ? '/cashier/login' : '/admin/login'
        );
        $middleware->redirectUsersTo('/home');

        // Every POST/PUT/DELETE is all-or-nothing (see AtomicRequest).
        $middleware->appendToGroup('web', \App\Http\Middleware\AtomicRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
