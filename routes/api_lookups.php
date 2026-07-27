<?php

use App\Http\Controllers\Api\Lookup\LookupApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('lookups')
    ->name('api.lookups.')
    ->group(function () {
        Route::get('{entity}', [LookupApiController::class, 'search'])->name('search');
    });
