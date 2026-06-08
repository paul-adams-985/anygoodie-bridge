<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override the core package's `Route::model` bindings (registered in
        // CoreServiceProvider): the recipient routes only ever forward the raw
        // identifier onward to the tenant, so resolving an Eloquent model for
        // it is unnecessary and would needlessly 404 on unknown identifiers.
        //
        // `Route::bind` is keyed by parameter name across the whole router, so
        // this isn't scoped to RecipientController by Laravel itself - it's
        // scoped in practice because `voucher_share` and `recipient_voucher`
        // are unique to RecipientController's two routes (verified via
        // `route:list -v`). If either name is ever reused by an unrelated
        // route, that route would silently inherit this pass-through too.
        Route::bind('voucher_share', fn (string $value): string => $value);
        Route::bind('recipient_voucher', fn (string $value): string => $value);
    }
}
