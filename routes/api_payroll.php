<?php

use App\Http\Controllers\Api\Hrms\PayrollApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('payroll')
    ->name('api.payroll.')
    ->group(function () {
        Route::get('dashboard', [PayrollApiController::class, 'dashboard'])
            ->middleware('permission:payroll.view')
            ->name('dashboard');

        Route::get('runs', [PayrollApiController::class, 'runs'])
            ->middleware('permission:payroll.view')
            ->name('runs.index');
        Route::get('runs/{run}', [PayrollApiController::class, 'showRun'])
            ->middleware('permission:payroll.view')
            ->name('runs.show');
        Route::post('runs/{run}/pay', [PayrollApiController::class, 'markPaid'])
            ->middleware('permission:payroll.pay,payroll.manage')
            ->name('runs.pay');

        Route::get('assignments', [PayrollApiController::class, 'assignments'])
            ->middleware('permission:payroll.view')
            ->name('assignments.index');
        Route::get('employees/{employee}/revisions', [PayrollApiController::class, 'revisions'])
            ->middleware('permission:payroll.view')
            ->name('revisions.index');

        Route::get('adjustments', [PayrollApiController::class, 'adjustments'])
            ->middleware('permission:payroll.view')
            ->name('adjustments.index');
        Route::post('adjustments', [PayrollApiController::class, 'storeAdjustment'])
            ->middleware('permission:payroll.adjustment.manage')
            ->name('adjustments.store');

        Route::get('payslips', [PayrollApiController::class, 'payslips'])
            ->middleware('permission:payslip.view')
            ->name('payslips.index');

        Route::get('bank-exports', [PayrollApiController::class, 'bankExports'])
            ->middleware('permission:payroll.bank.export')
            ->name('bank-exports.index');
    });
