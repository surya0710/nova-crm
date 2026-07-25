<?php

use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\AuthenticatedSessionController;
use App\Http\Controllers\Platform\ConfigurationController;
use App\Http\Controllers\Platform\CouponController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\GlobalUserController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\IndustryTemplateController;
use App\Http\Controllers\Platform\InvoiceController;
use App\Http\Controllers\Platform\LicensingController;
use App\Http\Controllers\Platform\MonitoringController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\PlanController;
use App\Http\Controllers\Platform\ProviderController;
use App\Http\Controllers\Platform\ReportController;
use App\Http\Controllers\Platform\SecurityController;
use App\Http\Controllers\Platform\ShellController;
use App\Http\Controllers\Platform\SubscriptionController;
use App\Http\Controllers\Platform\SupportController;
use App\Http\Controllers\Platform\TransactionController;
use App\Http\Controllers\Platform\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('platform.guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('platform.auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('dashboard/widgets', [DashboardController::class, 'updateWidgets'])->name('dashboard.widgets');

    Route::get('shell/commands', [ShellController::class, 'commands'])->name('shell.commands');
    Route::get('shell/search', [ShellController::class, 'search'])->name('shell.search');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::put('organizations/{organization}/modules', [OrganizationController::class, 'updateModules'])->name('organizations.modules.update');
    Route::put('organizations/{organization}/limits', [OrganizationController::class, 'updateLimits'])->name('organizations.limits.update');
    Route::post('organizations/{organization}/suspend', [OrganizationController::class, 'suspend'])->name('organizations.suspend');
    Route::post('organizations/{organization}/activate', [OrganizationController::class, 'activate'])->name('organizations.activate');
    Route::post('organizations/{organization}/archive', [OrganizationController::class, 'archive'])->name('organizations.archive');
    Route::post('organizations/{organization}/restore', [OrganizationController::class, 'restore'])->name('organizations.restore');
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::post('organizations/{organization}/impersonate', [ImpersonationController::class, 'start'])->name('organizations.impersonate');
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/active', [SubscriptionController::class, 'active'])->name('subscriptions.active');
    Route::get('subscriptions/trials', [SubscriptionController::class, 'trials'])->name('subscriptions.trials');
    Route::get('subscriptions/renewals', [SubscriptionController::class, 'renewals'])->name('subscriptions.renewals');
    Route::post('organizations/{organization}/subscriptions/assign-plan', [SubscriptionController::class, 'assignPlan'])->name('subscriptions.assign-plan');
    Route::post('organizations/{organization}/subscriptions/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('organizations/{organization}/subscriptions/downgrade', [SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
    Route::post('organizations/{organization}/subscriptions/start-trial', [SubscriptionController::class, 'startTrial'])->name('subscriptions.start-trial');
    Route::post('organizations/{organization}/subscriptions/end-trial', [SubscriptionController::class, 'endTrial'])->name('subscriptions.end-trial');

    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

    Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
    Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('licensing', [LicensingController::class, 'index'])->name('licensing.index');
    Route::post('licensing/plans', [LicensingController::class, 'updatePlan'])->name('licensing.update-plan');
    Route::post('organizations/{organization}/licensing/modules', [LicensingController::class, 'assignModules'])->name('licensing.assign-modules');
    Route::post('organizations/{organization}/licensing/quotas', [LicensingController::class, 'setQuotas'])->name('licensing.set-quotas');

    Route::get('global-users', [GlobalUserController::class, 'index'])->name('global-users.index');
    Route::get('global-users/login-history', [GlobalUserController::class, 'loginHistory'])->name('global-users.login-history');
    Route::get('global-users/sessions', [GlobalUserController::class, 'sessions'])->name('global-users.sessions');
    Route::get('global-users/mfa', [GlobalUserController::class, 'mfa'])->name('global-users.mfa');
    Route::post('global-users/{user}/lock', [GlobalUserController::class, 'lock'])->name('global-users.lock');
    Route::post('global-users/{user}/unlock', [GlobalUserController::class, 'unlock'])->name('global-users.unlock');
    Route::post('global-users/{user}/password-reset', [GlobalUserController::class, 'passwordReset'])->name('global-users.password-reset');
    Route::post('global-users/sessions/revoke', [GlobalUserController::class, 'revokeSession'])->name('global-users.revoke-session');

    Route::get('providers', [ProviderController::class, 'index'])->name('providers.index');
    Route::get('providers/{provider}', [ProviderController::class, 'show'])->name('providers.show');
    Route::post('providers/{provider}/validate', [ProviderController::class, 'validateProvider'])->name('providers.validate');
    Route::post('providers/{provider}/test', [ProviderController::class, 'test'])->name('providers.test');

    Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    Route::get('security', [SecurityController::class, 'index'])->name('security.index');
    Route::post('security/policies', [SecurityController::class, 'updatePolicies'])->name('security.update-policies');

    Route::get('support', [SupportController::class, 'index'])->name('support.index');
    Route::get('support/tickets', [SupportController::class, 'tickets'])->name('support.tickets');
    Route::get('support/tickets/create', [SupportController::class, 'createTicket'])->name('support.tickets.create');
    Route::post('support/tickets', [SupportController::class, 'storeTicket'])->name('support.tickets.store');
    Route::get('support/tickets/{ticket}', [SupportController::class, 'showTicket'])->name('support.tickets.show');
    Route::patch('support/tickets/{ticket}', [SupportController::class, 'updateTicket'])->name('support.tickets.update');
    Route::get('support/announcements', [SupportController::class, 'announcements'])->name('support.announcements');
    Route::post('support/announcements', [SupportController::class, 'storeAnnouncement'])->name('support.announcements.store');

    Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('configuration', [ConfigurationController::class, 'update'])->name('configuration.update');

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
