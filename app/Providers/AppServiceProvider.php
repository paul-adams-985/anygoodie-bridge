<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure JSON:API version globally
        JsonApiResource::configure(version: '1.0');

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

        RateLimiter::for('api-auth', function (Request $request) {
            return app()->isProduction()
                ? Limit::perMinute(10)->by($request->ip())
                : Limit::none();
        });

        RateLimiter::for('api-public', function (Request $request) {
            return app()->isProduction()
                ? Limit::perMinute(60)->by($request->ip())
                : Limit::none();
        });
    }
}
