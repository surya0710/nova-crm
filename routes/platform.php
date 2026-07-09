<?php

use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\AuthenticatedSessionController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\IndustryTemplateController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\ReportController;
use App\Http\Controllers\Platform\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('platform.guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('platform.auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::post('organizations/{organization}/suspend', [OrganizationController::class, 'suspend'])->name('organizations.suspend');
    Route::post('organizations/{organization}/activate', [OrganizationController::class, 'activate'])->name('organizations.activate');
    Route::post('organizations/{organization}/archive', [OrganizationController::class, 'archive'])->name('organizations.archive');

    Route::post('organizations/{organization}/impersonate', [ImpersonationController::class, 'start'])->name('organizations.impersonate');
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    Route::get('industry-templates', [IndustryTemplateController::class, 'index'])->name('industry-templates.index');
    Route::get('industry-templates/create', [IndustryTemplateController::class, 'create'])->name('industry-templates.create');
    Route::post('industry-templates', [IndustryTemplateController::class, 'store'])->name('industry-templates.store');
    Route::get('industry-templates/{industryTemplate}', [IndustryTemplateController::class, 'show'])->name('industry-templates.show');
    Route::get('industry-templates/{industryTemplate}/edit', [IndustryTemplateController::class, 'edit'])->name('industry-templates.edit');
    Route::patch('industry-templates/{industryTemplate}', [IndustryTemplateController::class, 'update'])->name('industry-templates.update');
    Route::post('industry-templates/{industryTemplate}/publish', [IndustryTemplateController::class, 'publish'])->name('industry-templates.publish');
    Route::post('industry-templates/{industryTemplate}/inactivate', [IndustryTemplateController::class, 'inactivate'])->name('industry-templates.inactivate');
    Route::post('industry-templates/{industryTemplate}/archive', [IndustryTemplateController::class, 'archive'])->name('industry-templates.archive');
    Route::post('industry-templates/{industryTemplate}/reactivate', [IndustryTemplateController::class, 'reactivate'])->name('industry-templates.reactivate');
    Route::post('industry-template-versions/{version}/clone', [IndustryTemplateController::class, 'clone'])->name('industry-template-versions.clone');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
});
