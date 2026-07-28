<?php

use App\Http\Controllers\Api\Hrms\AttendanceCalendarApiController;
use App\Http\Controllers\Api\Hrms\AttendanceDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])
    ->prefix('attendance')
    ->name('api.attendance.')
    ->group(function () {
        Route::get('calendar', AttendanceCalendarApiController::class)->name('calendar');

        Route::middleware('permission:ess.access')->group(function () {
            Route::get('dashboard', [AttendanceDashboardApiController::class, 'employeeDashboard'])->name('dashboard');
            Route::post('check-in', [AttendanceDashboardApiController::class, 'checkIn'])->name('check-in');
            Route::post('check-out', [AttendanceDashboardApiController::class, 'checkOut'])->name('check-out');
        });

        Route::get('team-summary', [AttendanceDashboardApiController::class, 'teamSummary'])
            ->middleware('permission:manager.dashboard')
            ->name('team-summary');
    });
