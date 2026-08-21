<?php

use App\Http\Controllers\Api\CapacityForecastController as ApiCapacityForecastController;
use App\Http\Controllers\Api\CrmEmailController as ApiCrmEmailController;
use App\Http\Controllers\Api\CrmEmailTemplateApiController as ApiCrmEmailTemplateController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\ContactController as ApiContactController;
use App\Http\Controllers\Api\CustomerTicketController as ApiCustomerTicketController;
use App\Http\Controllers\Api\CrmActivityController as ApiCrmActivityController;
use App\Http\Controllers\Api\ExecutiveDashboardController as ApiExecutiveDashboardController;
use App\Http\Controllers\Api\InvoiceController as ApiInvoiceController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use App\Http\Controllers\Api\NotificationPreferenceController as ApiNotificationPreferenceController;
use App\Http\Controllers\Api\OpportunityController as ApiOpportunityController;
use App\Http\Controllers\Api\PaymentController as ApiPaymentController;
use App\Http\Controllers\Api\PriceListController as ApiPriceListController;
use App\Http\Controllers\Api\ProductCategoryController as ApiProductCategoryController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\QuotationController as ApiQuotationController;
use App\Http\Controllers\Api\ReceivableController as ApiReceivableController;
use App\Http\Controllers\Api\SalesOrderController as ApiSalesOrderController;
use App\Http\Controllers\Api\SalesForecastController as ApiSalesForecastController;
use App\Http\Controllers\Api\AdjustmentNoteController as ApiAdjustmentNoteController;
use App\Http\Controllers\Api\PortfolioController as ApiPortfolioController;
use App\Http\Controllers\Api\PortfolioForecastController as ApiPortfolioForecastController;
use App\Http\Controllers\Api\PortfolioReportController as ApiPortfolioReportController;
use App\Http\Controllers\Api\PortfolioStatisticsController as ApiPortfolioStatisticsController;
use App\Http\Controllers\Api\ProgramController as ApiProgramController;
use App\Http\Controllers\Api\ProjectAutomationController as ApiProjectAutomationController;
use App\Http\Controllers\Api\ProjectBaselineController as ApiProjectBaselineController;
use App\Http\Controllers\Api\ProjectBudgetController as ApiProjectBudgetController;
use App\Http\Controllers\Api\ProjectCalendarController as ApiProjectCalendarController;
use App\Http\Controllers\Api\ProjectCategoryController as ApiProjectCategoryController;
use App\Http\Controllers\Api\ProjectCollaborationController as ApiProjectCollaborationController;
use App\Http\Controllers\Api\ProjectController as ApiProjectController;
use App\Http\Controllers\Api\ProjectDependencyController as ApiProjectDependencyController;
use App\Http\Controllers\Api\ProjectExecutiveController as ApiProjectExecutiveController;
use App\Http\Controllers\Api\ProjectHealthController as ApiProjectHealthController;
use App\Http\Controllers\Api\ProjectIssueController as ApiProjectIssueController;
use App\Http\Controllers\Api\ProjectLabelController as ApiProjectLabelController;
use App\Http\Controllers\Api\ProjectLifecycleStageController as ApiProjectLifecycleStageController;
use App\Http\Controllers\Api\ProjectMemberController as ApiProjectMemberController;
use App\Http\Controllers\Api\ProjectMentionController as ApiProjectMentionController;
use App\Http\Controllers\Api\ProjectMilestoneController as ApiProjectMilestoneController;
use App\Http\Controllers\Api\ProjectMilestoneProgressController as ApiProjectMilestoneProgressController;
use App\Http\Controllers\Api\ProjectProgressController as ApiProjectProgressController;
use App\Http\Controllers\Api\ProjectReportController as ApiProjectReportController;
use App\Http\Controllers\Api\ProjectRiskController as ApiProjectRiskController;
use App\Http\Controllers\Api\ProjectStatisticsController as ApiProjectStatisticsController;
use App\Http\Controllers\Api\ProjectStatusController as ApiProjectStatusController;
use App\Http\Controllers\Api\ProjectTemplateController as ApiProjectTemplateController;
use App\Http\Controllers\Api\ProjectTimelineController as ApiProjectTimelineController;
use App\Http\Controllers\Api\ProjectTypeController as ApiProjectTypeController;
use App\Http\Controllers\Api\ProjectWatcherController as ApiProjectWatcherController;
use App\Http\Controllers\Api\Recruitment\RecruitmentApiController;
use App\Http\Controllers\Api\ResourceAllocationController as ApiResourceAllocationController;
use App\Http\Controllers\Api\ResourceCalendarController as ApiResourceCalendarController;
use App\Http\Controllers\Api\TaskAttachmentController as ApiTaskAttachmentController;
use App\Http\Controllers\Api\TaskChecklistController as ApiTaskChecklistController;
use App\Http\Controllers\Api\TaskCommentController as ApiTaskCommentController;
use App\Http\Controllers\Api\TaskController as ApiTaskController;
use App\Http\Controllers\Api\TaskDependencyController as ApiTaskDependencyController;
use App\Http\Controllers\Api\TaskPriorityController as ApiTaskPriorityController;
use App\Http\Controllers\Api\TaskRecurrenceController as ApiTaskRecurrenceController;
use App\Http\Controllers\Api\TaskStatusController as ApiTaskStatusController;
use App\Http\Controllers\Api\TaskTimeLogController as ApiTaskTimeLogController;
use App\Http\Controllers\Api\TaskWatcherController as ApiTaskWatcherController;
use App\Http\Controllers\Api\WorkloadController as ApiWorkloadController;
use App\Http\Controllers\Dashboard\DashboardApiController;
use App\Http\Controllers\Dashboard\DashboardPreferenceController;
use App\Http\Controllers\Dashboard\DashboardWidgetController;
use App\Http\Controllers\Dashboard\QuickActionController;
use App\Http\Controllers\Dashboard\RecentActivitiesController;
use App\Http\Controllers\Dashboard\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_identity.php';
    require __DIR__.'/api_imports.php';
    require __DIR__.'/api_bulk.php';
    require __DIR__.'/api_exports.php';
    require __DIR__.'/api_lookups.php';
    require __DIR__.'/api_attendance.php';
    require __DIR__.'/api_payroll.php';
    require __DIR__.'/api_income_tax.php';
    require __DIR__.'/api_hrms.php';
});

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api', 'set.organization', 'ensure.organization', 'organization.api'])->group(function () {
    require __DIR__.'/api_rbac.php';

    Route::middleware('permission:dashboard.view')->prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/workspace', [WorkspaceController::class, 'show'])->name('workspace');
        Route::get('/', [DashboardApiController::class, 'index'])->name('index');
        Route::get('/widgets', [DashboardWidgetController::class, 'index'])->name('widgets.index');
        Route::get('/widgets/{widgetKey}/data', [DashboardWidgetController::class, 'data'])->name('widgets.data');
        Route::post('/widgets/{widget}/refresh', [DashboardWidgetController::class, 'refresh'])->name('widgets.refresh');
        Route::get('/preferences', [DashboardPreferenceController::class, 'show'])->name('preferences.show');
        Route::post('/preferences', [DashboardPreferenceController::class, 'update'])
            ->middleware('permission:dashboard.customize')
            ->name('preferences.update');
        Route::delete('/preferences', [DashboardPreferenceController::class, 'reset'])
            ->middleware('permission:dashboard.customize')
            ->name('preferences.reset');
        Route::get('/quick-actions', [QuickActionController::class, 'index'])->name('quick-actions.index');
        Route::get('/recent-activities', [RecentActivitiesController::class, 'index'])->name('recent-activities');
    });

    Route::middleware('permission:api.access')->group(function () {
        Route::get('leads', [ApiLeadController::class, 'index']);
        Route::post('leads', [ApiLeadController::class, 'store'])
            ->middleware('throttle:api-lead-intake');
        Route::get('leads/{lead}', [ApiLeadController::class, 'show']);
        Route::put('leads/{lead}', [ApiLeadController::class, 'update']);
        Route::patch('leads/{lead}', [ApiLeadController::class, 'update']);

        Route::get('customers', [ApiCustomerController::class, 'index']);
        Route::post('customers', [ApiCustomerController::class, 'store']);
        Route::get('customers/{customer}', [ApiCustomerController::class, 'show']);
        Route::put('customers/{customer}', [ApiCustomerController::class, 'update']);
        Route::patch('customers/{customer}', [ApiCustomerController::class, 'update']);
        Route::get('customers/{customer}/contacts', [ApiContactController::class, 'index']);
        Route::post('customers/{customer}/contacts', [ApiContactController::class, 'store']);
        Route::get('contacts', [ApiContactController::class, 'index']);
        Route::get('contacts/{contact}', [ApiContactController::class, 'show']);
        Route::put('contacts/{contact}', [ApiContactController::class, 'update']);
        Route::patch('contacts/{contact}', [ApiContactController::class, 'update']);
        Route::delete('contacts/{contact}', [ApiContactController::class, 'destroy']);
        Route::get('contacts/{contact}/activities', [ApiContactController::class, 'activities']);
        Route::post('contacts/{contact}/activities', [ApiContactController::class, 'storeActivity']);
        Route::get('contacts/{contact}/timeline', [ApiContactController::class, 'timeline']);
        Route::post('crm/email/send', [ApiCrmEmailController::class, 'send']);
        Route::get('crm/email/messages', [ApiCrmEmailController::class, 'messages']);
        Route::get('crm/email/messages/{message}', [ApiCrmEmailController::class, 'showMessage']);
        Route::get('crm/email/conversations', [ApiCrmEmailController::class, 'conversations']);
        Route::get('crm/email/conversations/{conversation}', [ApiCrmEmailController::class, 'showConversation']);
        Route::get('crm/email/conversations/{conversation}/messages', [ApiCrmEmailController::class, 'conversationMessages']);
        Route::get('crm/email/metrics', [ApiCrmEmailController::class, 'metrics']);
        Route::get('crm/email/templates', [ApiCrmEmailTemplateController::class, 'index']);
        Route::post('crm/email/templates', [ApiCrmEmailTemplateController::class, 'store']);
        Route::get('crm/email/templates/{template}', [ApiCrmEmailTemplateController::class, 'show']);
        Route::put('crm/email/templates/{template}', [ApiCrmEmailTemplateController::class, 'update']);
        Route::patch('crm/email/templates/{template}', [ApiCrmEmailTemplateController::class, 'update']);
        Route::delete('crm/email/templates/{template}', [ApiCrmEmailTemplateController::class, 'destroy']);
        Route::get('customers/{customer}/tickets', [ApiCustomerTicketController::class, 'index']);
        Route::post('customers/{customer}/tickets', [ApiCustomerTicketController::class, 'store']);
        Route::get('tickets', [ApiCustomerTicketController::class, 'index']);
        Route::get('tickets/{ticket}', [ApiCustomerTicketController::class, 'show']);
        Route::put('tickets/{ticket}', [ApiCustomerTicketController::class, 'update']);
        Route::patch('tickets/{ticket}', [ApiCustomerTicketController::class, 'update']);
        Route::post('tickets/{ticket}/notes', [ApiCustomerTicketController::class, 'storeNote']);
        Route::patch('tickets/{ticket}/assign', [ApiCustomerTicketController::class, 'assign']);
        Route::post('tickets/{ticket}/reopen', [ApiCustomerTicketController::class, 'reopen']);

        Route::get('opportunities', [ApiOpportunityController::class, 'index']);
        Route::post('opportunities', [ApiOpportunityController::class, 'store']);
        Route::get('opportunities/{opportunity}', [ApiOpportunityController::class, 'show']);
        Route::put('opportunities/{opportunity}', [ApiOpportunityController::class, 'update']);
        Route::patch('opportunities/{opportunity}', [ApiOpportunityController::class, 'update']);
        Route::patch('opportunities/{opportunity}/stage', [ApiOpportunityController::class, 'updateStage']);
        Route::post('opportunities/{opportunity}/activities', [ApiOpportunityController::class, 'storeActivity']);

        Route::get('activities', [ApiCrmActivityController::class, 'index']);
        Route::post('activities', [ApiCrmActivityController::class, 'store']);
        Route::post('activities/{crm_activity}/complete', [ApiCrmActivityController::class, 'complete']);
        Route::get('sales/forecast', ApiSalesForecastController::class);

        Route::get('products', [ApiProductController::class, 'index']);
        Route::post('products', [ApiProductController::class, 'store']);
        Route::get('products/{product}', [ApiProductController::class, 'show']);
        Route::put('products/{product}', [ApiProductController::class, 'update']);
        Route::patch('products/{product}', [ApiProductController::class, 'update']);
        Route::delete('products/{product}', [ApiProductController::class, 'destroy']);

        Route::apiResource('product-categories', ApiProductCategoryController::class)
            ->parameters(['product-categories' => 'productCategory'])
            ->names('api.product-categories');

        Route::get('quotations', [ApiQuotationController::class, 'index']);
        Route::post('quotations', [ApiQuotationController::class, 'store']);
        Route::get('quotations/{quotation}', [ApiQuotationController::class, 'show']);
        Route::put('quotations/{quotation}', [ApiQuotationController::class, 'update']);
        Route::patch('quotations/{quotation}', [ApiQuotationController::class, 'update']);
        Route::post('quotations/{quotation}/convert', [ApiQuotationController::class, 'convert']);

        Route::get('sales-orders', [ApiSalesOrderController::class, 'index']);
        Route::post('sales-orders', [ApiSalesOrderController::class, 'store']);
        Route::get('sales-orders/{sales_order}', [ApiSalesOrderController::class, 'show']);
        Route::put('sales-orders/{sales_order}', [ApiSalesOrderController::class, 'update']);
        Route::patch('sales-orders/{sales_order}', [ApiSalesOrderController::class, 'update']);
        Route::get('sales-orders/{sales_order}/items', [ApiSalesOrderController::class, 'items']);
        Route::post('sales-orders/{sales_order}/convert', [ApiSalesOrderController::class, 'convert']);

        Route::get('adjustment-notes', [ApiAdjustmentNoteController::class, 'index']);
        Route::post('adjustment-notes', [ApiAdjustmentNoteController::class, 'store']);
        Route::get('adjustment-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'show']);
        Route::put('adjustment-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update']);
        Route::patch('adjustment-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update']);
        Route::post('adjustment-notes/{adjustment_note}/apply', [ApiAdjustmentNoteController::class, 'apply']);

        Route::get('credit-notes', [ApiAdjustmentNoteController::class, 'index'])->name('api.credit-notes.index');
        Route::post('credit-notes', [ApiAdjustmentNoteController::class, 'store'])->name('api.credit-notes.store');
        Route::get('credit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'show'])->name('api.credit-notes.show');
        Route::put('credit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update'])->name('api.credit-notes.update');
        Route::patch('credit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update']);
        Route::post('credit-notes/{adjustment_note}/apply', [ApiAdjustmentNoteController::class, 'apply'])->name('api.credit-notes.apply');

        Route::get('debit-notes', [ApiAdjustmentNoteController::class, 'index'])->name('api.debit-notes.index');
        Route::post('debit-notes', [ApiAdjustmentNoteController::class, 'store'])->name('api.debit-notes.store');
        Route::get('debit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'show'])->name('api.debit-notes.show');
        Route::put('debit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update'])->name('api.debit-notes.update');
        Route::patch('debit-notes/{adjustment_note}', [ApiAdjustmentNoteController::class, 'update']);
        Route::post('debit-notes/{adjustment_note}/apply', [ApiAdjustmentNoteController::class, 'apply'])->name('api.debit-notes.apply');

        Route::get('invoices', [ApiInvoiceController::class, 'index']);
        Route::post('invoices', [ApiInvoiceController::class, 'store']);
        Route::get('invoices/{invoice}', [ApiInvoiceController::class, 'show']);
        Route::put('invoices/{invoice}', [ApiInvoiceController::class, 'update']);
        Route::patch('invoices/{invoice}', [ApiInvoiceController::class, 'update']);
        Route::post('invoices/{invoice}/payments', [ApiPaymentController::class, 'allocate']);

        Route::get('payments', [ApiPaymentController::class, 'index']);
        Route::post('payments', [ApiPaymentController::class, 'store']);
        Route::get('payments/{payment}', [ApiPaymentController::class, 'show']);

        Route::get('receivables', [ApiReceivableController::class, 'index']);
        Route::get('customers/{customer}/ledger', [ApiReceivableController::class, 'ledger']);

        Route::get('price-lists', [ApiPriceListController::class, 'index']);
        Route::post('price-lists', [ApiPriceListController::class, 'store']);
        Route::get('price-lists/{price_list}', [ApiPriceListController::class, 'show']);
        Route::put('price-lists/{price_list}', [ApiPriceListController::class, 'update']);
        Route::patch('price-lists/{price_list}', [ApiPriceListController::class, 'update']);
        Route::delete('price-lists/{price_list}', [ApiPriceListController::class, 'destroy']);

        Route::get('projects/executive-summary', [ApiProjectExecutiveController::class, 'summary']);
        Route::get('projects/watching', [ApiProjectWatcherController::class, 'index']);
        Route::get('projects/calendar', [ApiProjectCalendarController::class, 'index']);
        Route::get('projects/automation', [ApiProjectAutomationController::class, 'index']);

        Route::get('projects', [ApiProjectController::class, 'index']);
        Route::post('projects', [ApiProjectController::class, 'store']);
        Route::get('projects/{project}', [ApiProjectController::class, 'show']);
        Route::put('projects/{project}', [ApiProjectController::class, 'update']);
        Route::patch('projects/{project}', [ApiProjectController::class, 'update']);
        Route::delete('projects/{project}', [ApiProjectController::class, 'destroy']);
        Route::post('projects/{project}/archive', [ApiProjectController::class, 'archive']);
        Route::post('projects/{project}/restore', [ApiProjectController::class, 'restore']);

        Route::apiResource('project-categories', ApiProjectCategoryController::class)
            ->parameters(['project-categories' => 'category'])
            ->names('api.project-categories');
        Route::apiResource('project-types', ApiProjectTypeController::class)
            ->parameters(['project-types' => 'type'])
            ->names('api.project-types');
        Route::apiResource('project-statuses', ApiProjectStatusController::class)
            ->parameters(['project-statuses' => 'status'])
            ->names('api.project-statuses');
        Route::apiResource('project-lifecycle-stages', ApiProjectLifecycleStageController::class)
            ->parameters(['project-lifecycle-stages' => 'stage'])
            ->names('api.project-lifecycle-stages');

        Route::apiResource('project-labels', ApiProjectLabelController::class)
            ->parameters(['project-labels' => 'label'])
            ->names('api.project-labels');
        Route::apiResource('project-templates', ApiProjectTemplateController::class)
            ->parameters(['project-templates' => 'template'])
            ->names('api.project-templates');
        Route::post('project-templates/{template}/create-project', [ApiProjectTemplateController::class, 'createFromTemplate']);
        Route::post('project-templates/{template}/duplicate', [ApiProjectTemplateController::class, 'duplicate']);
        Route::post('project-templates/{template}/favorite', [ApiProjectTemplateController::class, 'favorite']);

        Route::get('projects/{project}/members', [ApiProjectMemberController::class, 'index']);
        Route::post('projects/{project}/members', [ApiProjectMemberController::class, 'store']);
        Route::patch('projects/{project}/members/{member}', [ApiProjectMemberController::class, 'update']);
        Route::delete('projects/{project}/members/{member}', [ApiProjectMemberController::class, 'destroy']);

        Route::get('projects/{project}/milestones', [ApiProjectMilestoneController::class, 'index']);
        Route::post('projects/{project}/milestones', [ApiProjectMilestoneController::class, 'store']);
        Route::patch('projects/{project}/milestones/{milestone}', [ApiProjectMilestoneController::class, 'update']);
        Route::delete('projects/{project}/milestones/{milestone}', [ApiProjectMilestoneController::class, 'destroy']);
        Route::post('projects/{project}/milestones/{milestone}/complete', [ApiProjectMilestoneController::class, 'complete']);

        Route::get('projects/{project}/health', [ApiProjectHealthController::class, 'show']);
        Route::get('projects/{project}/progress', [ApiProjectProgressController::class, 'index']);
        Route::post('projects/{project}/progress', [ApiProjectProgressController::class, 'store']);
        Route::put('projects/{project}/progress/{progressUpdate}', [ApiProjectProgressController::class, 'update']);
        Route::patch('projects/{project}/progress/{progressUpdate}', [ApiProjectProgressController::class, 'update']);
        Route::delete('projects/{project}/progress/{progressUpdate}', [ApiProjectProgressController::class, 'destroy']);
        Route::get('projects/{project}/reports', [ApiProjectReportController::class, 'index']);
        Route::post('projects/{project}/reports', [ApiProjectReportController::class, 'store']);
        Route::get('projects/{project}/statistics', [ApiProjectStatisticsController::class, 'show']);
        Route::get('projects/{project}/timeline', [ApiProjectTimelineController::class, 'show']);
        Route::get('projects/{project}/gantt', [ApiProjectTimelineController::class, 'gantt']);
        Route::get('projects/{project}/milestones/progress', [ApiProjectMilestoneProgressController::class, 'index']);

        Route::post('projects/{project}/watch', [ApiProjectWatcherController::class, 'store']);
        Route::delete('projects/{project}/watch', [ApiProjectWatcherController::class, 'destroy']);
        Route::post('projects/{project}/save-as-template', [ApiProjectTemplateController::class, 'saveFromProject']);
        Route::get('projects/{project}/collaboration', [ApiProjectCollaborationController::class, 'show']);
        Route::post('projects/{project}/collaboration/pins', [ApiProjectCollaborationController::class, 'pin']);
        Route::delete('projects/{project}/collaboration/pins/{pin}', [ApiProjectCollaborationController::class, 'unpin']);
        Route::post('projects/{project}/calendar/sync', [ApiProjectCalendarController::class, 'sync']);

        Route::get('projects/{project}/risks', [ApiProjectRiskController::class, 'projectIndex']);
        Route::post('projects/{project}/risks', [ApiProjectRiskController::class, 'store']);
        Route::put('projects/{project}/risks/{risk}', [ApiProjectRiskController::class, 'update']);
        Route::patch('projects/{project}/risks/{risk}', [ApiProjectRiskController::class, 'update']);
        Route::delete('projects/{project}/risks/{risk}', [ApiProjectRiskController::class, 'destroy']);

        Route::get('projects/{project}/issues', [ApiProjectIssueController::class, 'projectIndex']);
        Route::post('projects/{project}/issues', [ApiProjectIssueController::class, 'store']);
        Route::put('projects/{project}/issues/{issue}', [ApiProjectIssueController::class, 'update']);
        Route::patch('projects/{project}/issues/{issue}', [ApiProjectIssueController::class, 'update']);
        Route::delete('projects/{project}/issues/{issue}', [ApiProjectIssueController::class, 'destroy']);

        Route::get('projects/{project}/baselines', [ApiProjectBaselineController::class, 'index']);
        Route::post('projects/{project}/baselines', [ApiProjectBaselineController::class, 'store']);
        Route::get('projects/{project}/baselines/{baseline}', [ApiProjectBaselineController::class, 'show']);

        Route::get('projects/{project}/budgets', [ApiProjectBudgetController::class, 'show']);
        Route::put('projects/{project}/budgets', [ApiProjectBudgetController::class, 'update']);
        Route::patch('projects/{project}/budgets', [ApiProjectBudgetController::class, 'update']);

        Route::get('projects/{project}/dependencies', [ApiProjectDependencyController::class, 'projectIndex']);

        Route::get('portfolios/executive', [ApiExecutiveDashboardController::class, 'show']);
        Route::get('portfolios/forecasts', [ApiPortfolioForecastController::class, 'index']);
        Route::get('portfolios/forecasts/{portfolio}', [ApiPortfolioForecastController::class, 'show']);
        Route::get('portfolios/{portfolio}/dashboard', [ApiPortfolioController::class, 'dashboard']);
        Route::get('portfolios/{portfolio}/statistics', [ApiPortfolioStatisticsController::class, 'show']);
        Route::post('portfolios/{portfolio}/archive', [ApiPortfolioController::class, 'archive']);
        Route::post('portfolios/{portfolio}/projects', [ApiPortfolioController::class, 'attachProject']);
        Route::delete('portfolios/{portfolio}/projects/{project}', [ApiPortfolioController::class, 'detachProject']);
        Route::apiResource('portfolios', ApiPortfolioController::class)->names('api.portfolios');

        Route::get('programs/{program}/dashboard', [ApiProgramController::class, 'dashboard']);
        Route::post('programs/{program}/projects', [ApiProgramController::class, 'attachProject']);
        Route::delete('programs/{program}/projects/{project}', [ApiProgramController::class, 'detachProject']);
        Route::apiResource('programs', ApiProgramController::class)->names('api.programs');

        Route::get('project-dependencies', [ApiProjectDependencyController::class, 'index']);
        Route::post('project-dependencies', [ApiProjectDependencyController::class, 'store']);
        Route::put('project-dependencies/{dependency}', [ApiProjectDependencyController::class, 'update']);
        Route::patch('project-dependencies/{dependency}', [ApiProjectDependencyController::class, 'update']);
        Route::delete('project-dependencies/{dependency}', [ApiProjectDependencyController::class, 'destroy']);

        Route::get('risks', [ApiProjectRiskController::class, 'index']);
        Route::post('risks', [ApiProjectRiskController::class, 'store']);
        Route::put('risks/{risk}', [ApiProjectRiskController::class, 'update']);
        Route::patch('risks/{risk}', [ApiProjectRiskController::class, 'update']);
        Route::delete('risks/{risk}', [ApiProjectRiskController::class, 'destroy']);

        Route::get('issues', [ApiProjectIssueController::class, 'index']);
        Route::post('issues', [ApiProjectIssueController::class, 'store']);
        Route::put('issues/{issue}', [ApiProjectIssueController::class, 'update']);
        Route::patch('issues/{issue}', [ApiProjectIssueController::class, 'update']);
        Route::delete('issues/{issue}', [ApiProjectIssueController::class, 'destroy']);

        Route::get('portfolio-reports', [ApiPortfolioReportController::class, 'index']);
        Route::post('portfolio-reports', [ApiPortfolioReportController::class, 'store']);
        Route::get('portfolio-reports/{report}/download', [ApiPortfolioReportController::class, 'download']);

        Route::get('notification-preferences', [ApiNotificationPreferenceController::class, 'show']);
        Route::put('notification-preferences', [ApiNotificationPreferenceController::class, 'update']);
        Route::get('mentions', [ApiProjectMentionController::class, 'index']);
        Route::get('mentions/autocomplete', [ApiProjectMentionController::class, 'autocomplete']);

        Route::get('tasks', [ApiTaskController::class, 'index']);
        Route::post('tasks', [ApiTaskController::class, 'store']);
        Route::get('tasks/{task}', [ApiTaskController::class, 'show']);
        Route::put('tasks/{task}', [ApiTaskController::class, 'update']);
        Route::patch('tasks/{task}', [ApiTaskController::class, 'update']);
        Route::delete('tasks/{task}', [ApiTaskController::class, 'destroy']);
        Route::post('tasks/{task}/archive', [ApiTaskController::class, 'archive']);
        Route::post('tasks/{task}/restore', [ApiTaskController::class, 'restore']);
        Route::post('tasks/{task}/assign', [ApiTaskController::class, 'assign']);
        Route::post('tasks/{task}/complete', [ApiTaskController::class, 'complete']);

        Route::apiResource('task-statuses', ApiTaskStatusController::class)
            ->parameters(['task-statuses' => 'status'])
            ->names('api.task-statuses');
        Route::apiResource('task-priorities', ApiTaskPriorityController::class)
            ->parameters(['task-priorities' => 'priority'])
            ->names('api.task-priorities');

        Route::get('tasks/{task}/dependencies', [ApiTaskDependencyController::class, 'index']);
        Route::post('tasks/{task}/dependencies', [ApiTaskDependencyController::class, 'store']);
        Route::delete('tasks/{task}/dependencies/{dependency}', [ApiTaskDependencyController::class, 'destroy']);

        Route::get('tasks/{task}/checklists', [ApiTaskChecklistController::class, 'index']);
        Route::post('tasks/{task}/checklists', [ApiTaskChecklistController::class, 'store']);
        Route::patch('tasks/{task}/checklists/{checklist}', [ApiTaskChecklistController::class, 'update']);
        Route::delete('tasks/{task}/checklists/{checklist}', [ApiTaskChecklistController::class, 'destroy']);
        Route::post('tasks/{task}/checklists/{checklist}/complete', [ApiTaskChecklistController::class, 'complete']);

        Route::get('tasks/{task}/comments', [ApiTaskCommentController::class, 'index']);
        Route::post('tasks/{task}/comments', [ApiTaskCommentController::class, 'store']);
        Route::patch('tasks/{task}/comments/{comment}', [ApiTaskCommentController::class, 'update']);
        Route::delete('tasks/{task}/comments/{comment}', [ApiTaskCommentController::class, 'destroy']);

        Route::get('tasks/{task}/attachments', [ApiTaskAttachmentController::class, 'index']);
        Route::post('tasks/{task}/attachments', [ApiTaskAttachmentController::class, 'store']);
        Route::get('tasks/{task}/attachments/{attachment}/download', [ApiTaskAttachmentController::class, 'download']);
        Route::delete('tasks/{task}/attachments/{attachment}', [ApiTaskAttachmentController::class, 'destroy']);

        Route::get('tasks/{task}/time-logs', [ApiTaskTimeLogController::class, 'index']);
        Route::post('tasks/{task}/time-logs', [ApiTaskTimeLogController::class, 'store']);
        Route::post('tasks/{task}/time-logs/start', [ApiTaskTimeLogController::class, 'start']);
        Route::post('tasks/{task}/time-logs/pause', [ApiTaskTimeLogController::class, 'pause']);
        Route::post('tasks/{task}/time-logs/resume', [ApiTaskTimeLogController::class, 'resume']);
        Route::post('tasks/{task}/time-logs/stop', [ApiTaskTimeLogController::class, 'stop']);
        Route::delete('tasks/{task}/time-logs/{time_log}', [ApiTaskTimeLogController::class, 'destroy']);

        Route::post('tasks/{task}/watch', [ApiTaskWatcherController::class, 'store']);
        Route::delete('tasks/{task}/watch', [ApiTaskWatcherController::class, 'destroy']);
        Route::post('tasks/{task}/recurrence', [ApiTaskRecurrenceController::class, 'store']);
        Route::put('tasks/{task}/recurrence/{recurrence}', [ApiTaskRecurrenceController::class, 'update']);
        Route::patch('tasks/{task}/recurrence/{recurrence}', [ApiTaskRecurrenceController::class, 'update']);
        Route::delete('tasks/{task}/recurrence/{recurrence}', [ApiTaskRecurrenceController::class, 'destroy']);
        Route::post('tasks/{task}/labels/{label}', [ApiProjectLabelController::class, 'attach']);
        Route::delete('tasks/{task}/labels/{label}', [ApiProjectLabelController::class, 'detach']);

        Route::apiResource('resource-calendars', ApiResourceCalendarController::class);
        Route::apiResource('resource-allocations', ApiResourceAllocationController::class);

        Route::get('workload/employees/{employee}', [ApiWorkloadController::class, 'employee']);
        Route::get('workload/team', [ApiWorkloadController::class, 'team']);
        Route::post('workload/snapshots', [ApiWorkloadController::class, 'storeSnapshots']);

        Route::get('capacity/forecast', [ApiCapacityForecastController::class, 'forecast']);
        Route::get('capacity/risks', [ApiCapacityForecastController::class, 'risks']);

        Route::prefix('recruitment')->group(function () {
            Route::get('jobs', [RecruitmentApiController::class, 'jobs']);
            Route::get('jobs/{job}', [RecruitmentApiController::class, 'showJob']);
            Route::get('applications', [RecruitmentApiController::class, 'applications']);
            Route::get('applications/{application}', [RecruitmentApiController::class, 'showApplication']);
            Route::get('candidates', [RecruitmentApiController::class, 'candidates']);
            Route::get('candidates/{candidate}', [RecruitmentApiController::class, 'showCandidate']);
            Route::get('offers', [RecruitmentApiController::class, 'offers']);
            Route::get('offers/{offer}', [RecruitmentApiController::class, 'showOffer']);
            Route::get('reports', [RecruitmentApiController::class, 'reports']);
            Route::get('reports/{report}', [RecruitmentApiController::class, 'showReport']);
        });
    });
});

Route::prefix('v1/portal/{organization:slug}')
    ->middleware(['web', 'portal.organization', 'auth:client', 'portal.client', 'throttle:60,1'])
    ->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\Portal\PortalApiController::class, 'dashboard']);
        Route::get('billing', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'overview']);
        Route::get('quotations', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'quotations']);
        Route::get('quotations/{quotation}', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'showQuotation']);
        Route::post('quotations/{quotation}/accept', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'acceptQuotation']);
        Route::post('quotations/{quotation}/reject', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'rejectQuotation']);
        Route::get('sales-orders', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'salesOrders']);
        Route::get('sales-orders/{sales_order}', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'showSalesOrder']);
        Route::get('invoices', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'invoices']);
        Route::get('invoices/{invoice}', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'showInvoice']);
        Route::post('invoices/{invoice}/pay', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'payInvoice']);
        Route::get('payments', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'payments']);
        Route::get('notes', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'notes']);
        Route::get('ledger', [\App\Http\Controllers\Api\Portal\PortalCommercialApiController::class, 'ledger']);
        Route::get('projects/{project}', [\App\Http\Controllers\Api\Portal\PortalApiController::class, 'project']);
        Route::get('projects/{project}/deliverables', [\App\Http\Controllers\Api\Portal\PortalApiController::class, 'deliverables']);
        Route::post('approvals/{approval}/approve', [\App\Http\Controllers\Api\Portal\PortalApiController::class, 'approve']);
        Route::post('approvals/{approval}/reject', [\App\Http\Controllers\Api\Portal\PortalApiController::class, 'reject']);
    });
