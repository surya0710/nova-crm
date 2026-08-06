<?php

use App\Http\Controllers\Portal\ClientAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalDeliverableController;
use App\Http\Controllers\Portal\PortalDiscussionController;
use App\Http\Controllers\Portal\PortalProjectController;
use App\Http\Controllers\Portal\PortalUploadRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('{organization:slug}/portal')
    ->middleware(['portal.organization'])
    ->name('portal.')
    ->group(function () {
        Route::middleware('guest:client')->group(function () {
            Route::get('/login', [ClientAuthController::class, 'createLogin'])->name('login');
            Route::post('/login', [ClientAuthController::class, 'login'])->middleware('throttle:6,1');
        });

        Route::middleware(['auth:client', 'portal.client'])->group(function () {
            Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
            Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
            Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
            Route::get('/deliverables/{deliverable}', [PortalDeliverableController::class, 'show'])->name('deliverables.show');
            Route::post('/approvals/{approval}/approve', [PortalDeliverableController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{approval}/reject', [PortalDeliverableController::class, 'reject'])->name('approvals.reject');
            Route::post('/projects/{project}/discussions', [PortalDiscussionController::class, 'store'])->name('discussions.store');
            Route::post('/upload-requests/{uploadRequest}/fulfill', [PortalUploadRequestController::class, 'fulfill'])->name('upload-requests.fulfill');
        });
    });
