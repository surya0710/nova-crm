<?php

use App\Http\Controllers\Api\Hrms\IncomeTaxApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('tax')
    ->name('api.tax.')
    ->group(function () {
        Route::get('dashboard', [IncomeTaxApiController::class, 'dashboard'])
            ->middleware('permission:tax.view')
            ->name('dashboard');

        Route::get('financial-years', [IncomeTaxApiController::class, 'financialYears'])
            ->middleware('permission:tax.view')
            ->name('financial-years.index');
        Route::post('financial-years', [IncomeTaxApiController::class, 'storeFinancialYear'])
            ->middleware('permission:tax.manage')
            ->name('financial-years.store');

        Route::get('regimes', [IncomeTaxApiController::class, 'regimes'])
            ->middleware('permission:tax.view')
            ->name('regimes.index');
        Route::post('regimes', [IncomeTaxApiController::class, 'selectRegime'])
            ->middleware('permission:tax.manage')
            ->name('regimes.store');

        Route::get('projections', [IncomeTaxApiController::class, 'projections'])
            ->middleware('permission:tax.view')
            ->name('projections.index');
        Route::post('projections/calculate', [IncomeTaxApiController::class, 'calculateProjection'])
            ->middleware('permission:tax.calculate')
            ->name('projections.calculate');

        Route::get('declarations', [IncomeTaxApiController::class, 'declarations'])
            ->middleware('permission:tax.view')
            ->name('declarations.index');
        Route::post('declarations', [IncomeTaxApiController::class, 'storeDeclaration'])
            ->middleware('permission:tax.manage')
            ->name('declarations.store');
        Route::post('declarations/{declaration}/submit', [IncomeTaxApiController::class, 'submitDeclaration'])
            ->middleware('permission:tax.manage')
            ->name('declarations.submit');
        Route::post('declarations/{declaration}/verify', [IncomeTaxApiController::class, 'verifyDeclaration'])
            ->middleware('permission:tax.verify')
            ->name('declarations.verify');
        Route::post('declarations/{declaration}/reject', [IncomeTaxApiController::class, 'rejectDeclaration'])
            ->middleware('permission:tax.verify')
            ->name('declarations.reject');

        Route::get('proofs', [IncomeTaxApiController::class, 'proofs'])
            ->middleware('permission:tax.view')
            ->name('proofs.index');
        Route::post('proofs', [IncomeTaxApiController::class, 'storeProof'])
            ->middleware('permission:tax.manage')
            ->name('proofs.store');
        Route::post('proofs/{proof}/verify', [IncomeTaxApiController::class, 'verifyProof'])
            ->middleware('permission:tax.verify')
            ->name('proofs.verify');

        Route::get('tds', [IncomeTaxApiController::class, 'tds'])
            ->middleware('permission:tax.view')
            ->name('tds.index');

        Route::get('reports', [IncomeTaxApiController::class, 'reports'])
            ->middleware('permission:tax.view')
            ->name('reports.index');
        Route::get('reports/export', [IncomeTaxApiController::class, 'exportReport'])
            ->middleware('permission:tax.view')
            ->name('reports.export');

        Route::get('form16', [IncomeTaxApiController::class, 'form16Index'])
            ->middleware('permission:tax.view')
            ->name('form16.index');
        Route::post('form16', [IncomeTaxApiController::class, 'generateForm16'])
            ->middleware('permission:form16.generate')
            ->name('form16.store');
    });
