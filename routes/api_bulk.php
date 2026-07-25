<?php

use App\Http\Controllers\Api\Bulk\BulkApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('bulk')
    ->name('api.bulk.')
    ->group(function () {
        Route::get('actions/{entity}', [BulkApiController::class, 'actions'])->name('actions');
        Route::get('history', [BulkApiController::class, 'history'])->name('history');
        Route::post('execute', [BulkApiController::class, 'execute'])->name('execute');
        Route::get('operations/{operation}', [BulkApiController::class, 'show'])->name('show');
        Route::get('operations/{operation}/errors', [BulkApiController::class, 'errors'])->name('errors');
    });
