<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSetupController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::middleware(['auth', 'set.organization'])->group(function () {
    Route::get('organization/setup', [OrganizationSetupController::class, 'create'])->name('organization.setup');
    Route::post('organization/setup', [OrganizationSetupController::class, 'store'])->name('organization.setup.store');

    Route::middleware('ensure.organization')->group(function () {
        Route::get('/dashboard', DashboardController::class)->middleware('verified')->name('dashboard');

        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::get('leads/follow-ups/due', [LeadController::class, 'dueFollowUps'])->name('leads.follow-ups.due');
        Route::resource('leads', LeadController::class);
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status.update');
        Route::patch('leads/{lead}/follow-up', [LeadController::class, 'updateFollowUp'])->name('leads.follow-up.update');
        Route::post('leads/{lead}/follow-up/acknowledge', [LeadController::class, 'acknowledgeFollowUp'])->name('leads.follow-up.acknowledge');

        Route::resource('customers', CustomerController::class);
        Route::post('customers/{customer}/notes', [CustomerController::class, 'storeNote'])->name('customers.notes.store');
        Route::post('customers/{customer}/send', [CustomerController::class, 'sendMail'])->name('customers.send');

        Route::resource('pipeline', OpportunityController::class)
            ->parameters(['pipeline' => 'opportunity']);
        Route::patch('pipeline/{opportunity}/stage', [OpportunityController::class, 'updateStage'])->name('pipeline.stage.update');
        Route::post('pipeline/{opportunity}/notes', [OpportunityController::class, 'storeNote'])->name('pipeline.notes.store');

        Route::resource('products', ProductController::class);

        Route::resource('quotations', QuotationController::class);
        Route::patch('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status.update');
        Route::post('quotations/{quotation}/send', [QuotationController::class, 'sendMail'])->name('quotations.send');

        Route::resource('invoices', InvoiceController::class);
        Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status.update');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendMail'])->name('invoices.send');

        Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('payments/{payment}/send', [PaymentController::class, 'sendMail'])->name('payments.send');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::resource('tasks', TaskController::class);
        Route::patch('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

        Route::get('search', [SearchController::class, 'index'])->name('search.index');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->middleware('permission:audit.view');
        Route::post('attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index')->middleware('permission:api.tokens');
        Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store')->middleware('permission:api.tokens');
        Route::delete('api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy')->middleware('permission:api.tokens');

        Route::get('team', [TeamController::class, 'index'])->name('team.index');
        Route::post('team', [TeamController::class, 'store'])->name('team.store');
        Route::patch('team/{member}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('team/{member}', [TeamController::class, 'destroy'])->name('team.destroy');

        Route::get('organization/settings', [OrganizationController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('organization.edit');
        Route::patch('organization/settings', [OrganizationController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('organization.update');
        Route::post('organization/settings/test-mail', [OrganizationController::class, 'sendTestMail'])
            ->middleware('permission:settings.manage')
            ->name('organization.test-mail');
        Route::post('organization/switch/{organization}', OrganizationSwitchController::class)->name('organization.switch');
    });
});

Route::middleware(['auth', 'set.organization'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
