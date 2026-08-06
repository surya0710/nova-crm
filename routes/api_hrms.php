<?php

use App\Http\Controllers\Api\Hrms\AttendanceMeApiController;
use App\Http\Controllers\Api\Hrms\AuthApiController;
use App\Http\Controllers\Api\Hrms\DeviceApiController;
use App\Http\Controllers\Api\Hrms\DocumentMeApiController;
use App\Http\Controllers\Api\Hrms\EmployeeMeApiController;
use App\Http\Controllers\Api\Hrms\HrDashboardApiController;
use App\Http\Controllers\Api\Hrms\LeaveMeApiController;
use App\Http\Controllers\Api\Hrms\ManagerApiController;
use App\Http\Controllers\Api\Hrms\MeDashboardApiController;
use App\Http\Controllers\Api\Hrms\NotificationApiController;
use App\Http\Controllers\Api\Hrms\PayrollMeApiController;
use App\Http\Controllers\Api\Hrms\RecruitmentHrmsApiController;
use App\Http\Controllers\Api\Hrms\TaxMeApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRMS Mobile API — /api/v1/hrms
|--------------------------------------------------------------------------
*/

Route::prefix('hrms')->name('api.hrms.')->group(function () {
    Route::middleware('throttle:hrms-mobile-auth')->group(function () {
        Route::post('auth/login', [AuthApiController::class, 'login'])->name('auth.login');
        Route::post('auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('auth.reset-password');
        Route::post('auth/refresh', [AuthApiController::class, 'refresh'])->name('auth.refresh');
    });

    Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
        ->group(function () {
            Route::post('auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');
            Route::post('auth/change-password', [AuthApiController::class, 'changePassword'])->name('auth.change-password');

            Route::post('devices', [DeviceApiController::class, 'store'])->name('devices.store');
            Route::delete('devices/{device}', [DeviceApiController::class, 'destroy'])->name('devices.destroy');

            Route::middleware('permission:ess.access')->prefix('me')->name('me.')->group(function () {
                Route::get('dashboard', MeDashboardApiController::class)->name('dashboard');

                Route::get('profile', [EmployeeMeApiController::class, 'show'])->name('profile.show');
                Route::put('profile', [EmployeeMeApiController::class, 'update'])->name('profile.update');
                Route::patch('profile', [EmployeeMeApiController::class, 'update'])->name('profile.patch');

                Route::prefix('attendance')->name('attendance.')->group(function () {
                    Route::get('summary', [AttendanceMeApiController::class, 'summary'])->name('summary');
                    Route::get('history', [AttendanceMeApiController::class, 'history'])->name('history');
                    Route::get('calendar', [AttendanceMeApiController::class, 'calendar'])->name('calendar');
                    Route::post('clock-in', [AttendanceMeApiController::class, 'clockIn'])->name('clock-in');
                    Route::post('clock-out', [AttendanceMeApiController::class, 'clockOut'])->name('clock-out');
                    Route::get('corrections', [AttendanceMeApiController::class, 'corrections'])->name('corrections.index');
                    Route::post('corrections', [AttendanceMeApiController::class, 'storeCorrection'])->name('corrections.store');
                });

                Route::prefix('leave')->name('leave.')->group(function () {
                    Route::get('balances', [LeaveMeApiController::class, 'balances'])->name('balances');
                    Route::get('types', [LeaveMeApiController::class, 'types'])->name('types');
                    Route::get('history', [LeaveMeApiController::class, 'history'])->name('history');
                    Route::post('/', [LeaveMeApiController::class, 'store'])->name('store');
                    Route::delete('{application}', [LeaveMeApiController::class, 'cancel'])->name('cancel');
                });

                Route::prefix('payroll')->name('payroll.')->group(function () {
                    Route::get('payslips', [PayrollMeApiController::class, 'payslips'])->name('payslips.index');
                    Route::get('payslips/{payslip}', [PayrollMeApiController::class, 'showPayslip'])->name('payslips.show');
                    Route::get('payslips/{payslip}/download', [PayrollMeApiController::class, 'downloadPayslip'])->name('payslips.download');
                    Route::get('salary-structure', [PayrollMeApiController::class, 'salaryStructure'])->name('salary-structure');
                });

                Route::prefix('tax')->name('tax.')->group(function () {
                    Route::get('dashboard', [TaxMeApiController::class, 'dashboard'])->name('dashboard');
                    Route::get('regimes', [TaxMeApiController::class, 'regimes'])->name('regimes.index');
                    Route::post('regimes', [TaxMeApiController::class, 'selectRegime'])->name('regimes.select');
                    Route::get('projections', [TaxMeApiController::class, 'projections'])->name('projections.index');
                    Route::post('projections', [TaxMeApiController::class, 'calculateProjection'])->name('projections.calculate');
                    Route::get('declarations', [TaxMeApiController::class, 'declarations'])->name('declarations.index');
                    Route::post('declarations', [TaxMeApiController::class, 'storeDeclaration'])->name('declarations.store');
                    Route::post('declarations/{declaration}/submit', [TaxMeApiController::class, 'submitDeclaration'])->name('declarations.submit');
                    Route::get('proofs', [TaxMeApiController::class, 'proofs'])->name('proofs.index');
                    Route::post('proofs', [TaxMeApiController::class, 'storeProof'])->name('proofs.store');
                });

                Route::prefix('documents')->name('documents.')->group(function () {
                    Route::get('/', [DocumentMeApiController::class, 'index'])->name('index');
                    Route::post('/', [DocumentMeApiController::class, 'store'])->name('store');
                    Route::get('{document}', [DocumentMeApiController::class, 'show'])->name('show');
                    Route::get('{document}/download', [DocumentMeApiController::class, 'download'])->name('download');
                });

                Route::prefix('notifications')->name('notifications.')->group(function () {
                    Route::get('/', [NotificationApiController::class, 'index'])->name('index');
                    Route::get('count', [NotificationApiController::class, 'count'])->name('count');
                    Route::post('read-all', [NotificationApiController::class, 'markAllRead'])->name('read-all');
                    Route::post('{notification}/read', [NotificationApiController::class, 'markRead'])->name('read');
                });
            });

            Route::middleware('permission:manager.dashboard')->prefix('manager')->name('manager.')->group(function () {
                Route::get('dashboard', [ManagerApiController::class, 'dashboard'])->name('dashboard');
                Route::get('attendance', [ManagerApiController::class, 'teamAttendance'])->name('attendance');
                Route::get('leave/pending', [ManagerApiController::class, 'pendingLeave'])->name('leave.pending');
                Route::post('leave/{application}/approve', [ManagerApiController::class, 'approveLeave'])
                    ->middleware('permission:leave.approve')
                    ->name('leave.approve');
                Route::post('leave/{application}/reject', [ManagerApiController::class, 'rejectLeave'])
                    ->middleware('permission:leave.approve')
                    ->name('leave.reject');
                Route::get('directory', [ManagerApiController::class, 'directory'])->name('directory');
            });

            Route::middleware('permission:hrms.view')->prefix('hr')->name('hr.')->group(function () {
                Route::get('dashboard', [HrDashboardApiController::class, 'dashboard'])->name('dashboard');
                Route::get('directory', [HrDashboardApiController::class, 'directory'])->name('directory');
                Route::get('stats', [HrDashboardApiController::class, 'stats'])->name('stats');
            });

            Route::middleware('permission:recruitment.view')->prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('jobs', [RecruitmentHrmsApiController::class, 'jobs'])->name('jobs.index');
                Route::get('jobs/{job}', [RecruitmentHrmsApiController::class, 'showJob'])->name('jobs.show');
                Route::get('candidates', [RecruitmentHrmsApiController::class, 'candidates'])->name('candidates.index');
                Route::get('candidates/{candidate}', [RecruitmentHrmsApiController::class, 'showCandidate'])->name('candidates.show');
                Route::get('applications', [RecruitmentHrmsApiController::class, 'applications'])->name('applications.index');
                Route::get('applications/{application}', [RecruitmentHrmsApiController::class, 'showApplication'])->name('applications.show');
                Route::get('offers', [RecruitmentHrmsApiController::class, 'offers'])->name('offers.index');
                Route::get('offers/{offer}', [RecruitmentHrmsApiController::class, 'showOffer'])->name('offers.show');
            });
        });
});
