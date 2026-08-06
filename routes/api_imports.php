<?php

use App\Http\Controllers\Api\Import\ImportApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('imports')
    ->name('api.imports.')
    ->group(function () {
        Route::get('catalog', [ImportApiController::class, 'catalog'])->name('catalog');
        Route::get('history', [ImportApiController::class, 'history'])->name('history');

        Route::get('sessions/{session}', [ImportApiController::class, 'show'])->name('show');
        Route::post('sessions/{session}/validate', [ImportApiController::class, 'validateSession'])->name('validate');
        Route::get('sessions/{session}/preview', [ImportApiController::class, 'preview'])->name('preview');
        Route::post('sessions/{session}/map', [ImportApiController::class, 'map'])->name('map');
        Route::post('sessions/{session}/execute', [ImportApiController::class, 'execute'])->name('execute');
        Route::get('sessions/{session}/errors', [ImportApiController::class, 'errors'])->name('errors');

        Route::post('{entity}/upload', [ImportApiController::class, 'upload'])->name('upload');
    });
