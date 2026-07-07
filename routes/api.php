<?php

use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'set.organization', 'ensure.organization'])->group(function () {
    Route::middleware('permission:api.access')->group(function () {
        Route::get('leads', [ApiLeadController::class, 'index']);
        Route::post('leads', [ApiLeadController::class, 'store'])
            ->middleware('throttle:api-lead-intake');
        Route::get('leads/{lead}', [ApiLeadController::class, 'show']);

        Route::get('customers', [ApiCustomerController::class, 'index']);
        Route::get('customers/{customer}', [ApiCustomerController::class, 'show']);
    });
});
