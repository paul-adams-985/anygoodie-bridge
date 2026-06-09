<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $homePage = env('HOME_PAGE');

    if (! $homePage) {
        abort(404, 'homepage not defined');
    }

    return redirect($homePage);
});
