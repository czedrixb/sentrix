<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| The Vue SPA owns client-side routing, so every non-API path returns the same
| shell and the router decides what to render. Anything under api/ or storage/
| is excluded so those keep their real handlers.
|
*/

Route::view('/{any?}', 'app')
    ->where('any', '^(?!api|storage|up|sanctum).*$')
    ->name('spa');
