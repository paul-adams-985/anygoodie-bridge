<?php

use App\Http\Controllers\Api\V1\AppVersionController;
use App\Http\Controllers\Api\V1\UserExistsController;
use Illuminate\Support\Facades\Route;
use PaulAdams985\Core\Http\Middleware\VerifyTenantSecret;

Route::prefix('v1')->group(function () {
    Route::prefix('app')->name('api.v1.app.')->group(function () {
        Route::get('/version', AppVersionController::class)
            ->middleware(['throttle:api-public', VerifyTenantSecret::class])
            ->name('version');
    });

    Route::prefix('recipient')->name('api.v1.recipient.')->group(function () {
        Route::post('/user-exists', UserExistsController::class)
            ->middleware(['throttle:api-auth', VerifyTenantSecret::class])
            ->name('user-exists');
    });
});
