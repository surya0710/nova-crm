<?php

use App\Http\Controllers\Api\Export\ExportApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('exports')
    ->name('api.exports.')
    ->group(function () {
        Route::get('catalog', [ExportApiController::class, 'catalog'])->name('catalog');
        Route::get('history', [ExportApiController::class, 'history'])->name('history');
        Route::post('generate', [ExportApiController::class, 'generate'])->name('generate');
        Route::get('sessions/{session}', [ExportApiController::class, 'show'])->name('show');
        Route::get('sessions/{session}/download', [ExportApiController::class, 'download'])->name('download');
        Route::delete('sessions/{session}', [ExportApiController::class, 'destroy'])->name('destroy');
    });
