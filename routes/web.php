<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AssignmentSettingsController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\MarketingProviderOAuthController;
use App\Http\Controllers\MarketingTrackingController;
use App\Http\Controllers\MetadataFieldBlueprintActivationController;
use App\Http\Controllers\MetadataFieldDefinitionController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSetupController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedFilterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowExecutionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('platform')->check()) {
        return redirect()->route('platform.dashboard');
    }

    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('impersonation/accept/{token}', [ImpersonationController::class, 'accept'])
    ->name('impersonation.accept');

Route::post('marketing/track', [MarketingTrackingController::class, 'store'])
    ->middleware(['throttle:marketing-tracking', 'marketing.tracking'])
    ->name('marketing.track');

Route::match(['get', 'post'], 'webhooks/marketing/{provider}', [MetaWebhookController::class, 'handle'])
    ->middleware(['throttle:marketing-webhooks'])
    ->name('webhooks.marketing');

Route::middleware(['auth', 'prevent.platform.tenant', 'set.organization'])->group(function () {
    Route::get('organization/setup', [OrganizationSetupController::class, 'create'])->name('organization.setup');
    Route::post('organization/setup', [OrganizationSetupController::class, 'store'])->name('organization.setup.store');

    Route::middleware(['ensure.organization', 'organization.lifecycle'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->middleware('verified')->name('dashboard');

        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::get('leads/follow-ups/due', [LeadController::class, 'dueFollowUps'])->name('leads.follow-ups.due');

        Route::get('imports/leads', [LeadImportController::class, 'create'])->name('leads.import.create');
        Route::post('imports/leads', [LeadImportController::class, 'store'])->name('leads.import.store');
        Route::get('imports/leads/template/csv', [LeadImportController::class, 'downloadCsvTemplate'])->name('leads.import.template.csv');
        Route::get('imports/leads/template/xlsx', [LeadImportController::class, 'downloadXlsxTemplate'])->name('leads.import.template.xlsx');
        Route::get('imports/leads/{session}', [LeadImportController::class, 'preview'])->name('leads.import.preview');
        Route::post('imports/leads/{session}/execute', [LeadImportController::class, 'execute'])->name('leads.import.execute');
        Route::get('imports/leads/{session}/summary', [LeadImportController::class, 'summary'])->name('leads.import.summary');
        Route::get('imports/leads/{session}/errors', [LeadImportController::class, 'errors'])->name('leads.import.errors');
        Route::get('imports/leads/{session}/report', [LeadImportController::class, 'validationReport'])->name('leads.import.report');
        Route::get('imports/leads/{session}/report/xlsx', [LeadImportController::class, 'validationReportXlsx'])->name('leads.import.report.xlsx');

        Route::resource('leads', LeadController::class);
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status.update');
        Route::patch('leads/{lead}/follow-up', [LeadController::class, 'updateFollowUp'])->name('leads.follow-up.update');
        Route::post('leads/{lead}/follow-up/acknowledge', [LeadController::class, 'acknowledgeFollowUp'])->name('leads.follow-up.acknowledge');

        Route::resource('customers', CustomerController::class);
        Route::get('imports/customers', [CustomerImportController::class, 'create'])->name('customers.import.create');
        Route::post('imports/customers', [CustomerImportController::class, 'store'])->name('customers.import.store');
        Route::get('imports/customers/{session}', [CustomerImportController::class, 'preview'])->name('customers.import.preview');
        Route::post('imports/customers/{session}/execute', [CustomerImportController::class, 'execute'])->name('customers.import.execute');
        Route::get('imports/customers/{session}/summary', [CustomerImportController::class, 'summary'])->name('customers.import.summary');
        Route::get('imports/customers/{session}/errors', [CustomerImportController::class, 'errors'])->name('customers.import.errors');
        Route::get('imports/customers/{session}/report', [CustomerImportController::class, 'validationReport'])->name('customers.import.report');
        Route::get('imports/customers/{session}/report/xlsx', [CustomerImportController::class, 'validationReportXlsx'])->name('customers.import.report.xlsx');
        Route::post('customers/{customer}/notes', [CustomerController::class, 'storeNote'])->name('customers.notes.store');
        Route::post('customers/{customer}/send', [CustomerController::class, 'sendMail'])->name('customers.send');

        Route::resource('pipeline', OpportunityController::class)
            ->parameters(['pipeline' => 'opportunity']);
        Route::patch('pipeline/{opportunity}/stage', [OpportunityController::class, 'updateStage'])->name('pipeline.stage.update');
        Route::post('pipeline/{opportunity}/notes', [OpportunityController::class, 'storeNote'])->name('pipeline.notes.store');

        Route::resource('products', ProductController::class);

        Route::resource('quotations', QuotationController::class);
        Route::patch('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status.update');
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
        Route::post('quotations/{quotation}/send', [QuotationController::class, 'sendMail'])->name('quotations.send');

        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendMail'])->name('invoices.send');

        Route::resource('payments', PaymentController::class)
            ->only(['index', 'create', 'store', 'show']);
        Route::post('payments/{payment}/send', [PaymentController::class, 'sendMail'])->name('payments.send');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/finance', [ReportController::class, 'finance'])->name('reports.finance');
        Route::get('reports/finance/export/outstanding', [ReportController::class, 'exportOutstanding'])->name('reports.export.outstanding');
        Route::get('reports/finance/export/revenue', [ReportController::class, 'exportRevenue'])->name('reports.export.revenue');
        Route::get('customers/{customer}/statement/export', [ReportController::class, 'exportCustomerStatement'])->name('customers.statement.export');

        Route::resource('tasks', TaskController::class);
        Route::patch('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

        Route::post('saved-filters', [SavedFilterController::class, 'store'])->name('saved-filters.store');
        Route::patch('saved-filters/{saved_filter}', [SavedFilterController::class, 'update'])->name('saved-filters.update');
        Route::delete('saved-filters/{saved_filter}', [SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');
        Route::post('saved-filters/{saved_filter}/duplicate', [SavedFilterController::class, 'duplicate'])->name('saved-filters.duplicate');

        Route::get('search', [SearchController::class, 'index'])->name('search.index');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')->middleware('permission:audit.view');
        Route::post('metadata-fields/activate-blueprints', MetadataFieldBlueprintActivationController::class)->name('metadata-fields.activate-blueprints')->middleware('permission:metadata.manage');
        Route::post('metadata-fields/{metadata_field}/publish', [MetadataFieldDefinitionController::class, 'publish'])->name('metadata-fields.publish')->middleware('permission:metadata.update');
        Route::post('metadata-fields/{metadata_field}/activate', [MetadataFieldDefinitionController::class, 'activate'])->name('metadata-fields.activate')->middleware('permission:metadata.update');
        Route::post('metadata-fields/{metadata_field}/deactivate', [MetadataFieldDefinitionController::class, 'deactivate'])->name('metadata-fields.deactivate')->middleware('permission:metadata.update');
        Route::resource('metadata-fields', MetadataFieldDefinitionController::class)
            ->middleware('permission:metadata.view');
        Route::post('attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index')->middleware('permission:api.tokens');
        Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store')->middleware('permission:api.tokens');
        Route::delete('api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy')->middleware('permission:api.tokens');

        Route::get('integrations', [IntegrationController::class, 'index'])
            ->middleware('permission:integrations.view')
            ->name('integrations.index');
        Route::get('integrations/diagnostics', [IntegrationController::class, 'diagnostics'])
            ->middleware('permission:integrations.view')
            ->name('integrations.diagnostics');
        Route::get('integrations/{provider}', [IntegrationController::class, 'show'])
            ->middleware('permission:integrations.view')
            ->name('integrations.show');
        Route::post('integrations/{provider}/assets', [IntegrationController::class, 'saveAssets'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.assets.save');
        Route::post('integrations/{provider}/assets/refresh', [IntegrationController::class, 'refreshAssets'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.assets.refresh');
        Route::post('integrations/{provider}/lead-forms/sync', [IntegrationController::class, 'synchronizeLeadForms'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.lead-forms.sync');
        Route::post('integrations/{provider}/leads/import', [IntegrationController::class, 'importLeads'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.leads.import');
        Route::post('integrations/{provider}/conversions/upload', [IntegrationController::class, 'uploadConversions'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.conversions.upload');
        Route::post('integrations/{provider}/webhooks/process', [IntegrationController::class, 'processWebhooks'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.webhooks.process');
        Route::post('integrations/{provider}/health-check', [IntegrationController::class, 'runHealthCheck'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.health-check');
        Route::post('integrations/{provider}/disconnect', [IntegrationController::class, 'disconnect'])
            ->middleware('permission:integrations.manage')
            ->name('integrations.disconnect');

        Route::get('assignments', [AssignmentSettingsController::class, 'index'])
            ->middleware('permission:assignments.view')
            ->name('assignments.index');
        Route::post('assignments/pools', [AssignmentSettingsController::class, 'storePool'])
            ->middleware('permission:assignments.manage')
            ->name('assignments.pools.store');
        Route::put('assignments/pools/{pool}', [AssignmentSettingsController::class, 'updatePool'])
            ->middleware('permission:assignments.manage')
            ->name('assignments.pools.update');
        Route::post('assignments/rules', [AssignmentSettingsController::class, 'storeRule'])
            ->middleware('permission:assignments.manage')
            ->name('assignments.rules.store');
        Route::put('assignments/rules/{rule}', [AssignmentSettingsController::class, 'updateRule'])
            ->middleware('permission:assignments.manage')
            ->name('assignments.rules.update');

        Route::post('workflows/{workflow}/enable', [WorkflowController::class, 'enable'])
            ->name('workflows.enable');
        Route::post('workflows/{workflow}/disable', [WorkflowController::class, 'disable'])
            ->name('workflows.disable');
        Route::get('workflows/{workflow}/executions', [WorkflowExecutionController::class, 'index'])
            ->name('workflows.executions.index');
        Route::get('workflows/{workflow}/executions/{execution}', [WorkflowExecutionController::class, 'show'])
            ->scopeBindings()
            ->name('workflows.executions.show');
        Route::resource('workflows', WorkflowController::class);

        Route::get('marketing/providers/{provider}/connect', [MarketingProviderOAuthController::class, 'connect'])
            ->middleware('permission:integrations.manage')
            ->name('marketing.providers.connect');
        Route::get('marketing/providers/{provider}/callback', [MarketingProviderOAuthController::class, 'callback'])
            ->middleware('permission:integrations.manage')
            ->name('marketing.providers.callback');
        Route::post('marketing/providers/{provider}/disconnect', [MarketingProviderOAuthController::class, 'disconnect'])
            ->middleware('permission:integrations.manage')
            ->name('marketing.providers.disconnect');

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

        Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])
            ->name('impersonation.stop');
    });
});

Route::middleware(['auth', 'prevent.platform.tenant', 'set.organization'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
