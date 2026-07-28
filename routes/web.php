<?php

use App\Http\Controllers\Administration\AdministrationHomeController;
use App\Http\Controllers\Administration\BrandingController as AdministrationBrandingController;
use App\Http\Controllers\Administration\DeveloperController as AdministrationDeveloperController;
use App\Http\Controllers\Administration\ImportCenterController;
use App\Http\Controllers\Administration\BulkOperationsController;
use App\Http\Controllers\Administration\ExportCenterController;
use App\Http\Controllers\Administration\ModulesController as AdministrationModulesController;
use App\Http\Controllers\Administration\SecurityController as AdministrationSecurityController;
use App\Http\Controllers\Analytics\AnalyticsHomeController;
use App\Http\Controllers\Analytics\AnalyticsPagesController;
use App\Http\Controllers\Marketing\MarketingAttributionController;
use App\Http\Controllers\Marketing\MarketingCampaignController;
use App\Http\Controllers\Marketing\MarketingHomeController;
use App\Http\Controllers\Marketing\MarketingProvidersController;
use App\Http\Controllers\WorkspaceDashboardPreferenceController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AssignmentSettingsController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Crm\CrmActivitiesController;
use App\Http\Controllers\Crm\CrmExportsController;
use App\Http\Controllers\Crm\CrmHomeController;
use App\Http\Controllers\Operations\OperationsHomeController;
use App\Http\Controllers\Projects\ProjectsBudgetsHubController;
use App\Http\Controllers\Projects\ProjectsHomeController;
use App\Http\Controllers\Projects\ProjectsMilestonesHubController;
use App\Http\Controllers\Projects\ProjectsReportsController;
use App\Http\Controllers\Crm\CrmReportsController;
use App\Http\Controllers\Crm\CrmRevenueController;
use App\Http\Controllers\Crm\CrmSavedViewsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\Dashboard\DashboardApiController;
use App\Http\Controllers\Dashboard\DashboardPreferenceController;
use App\Http\Controllers\Dashboard\DashboardWidgetController;
use App\Http\Controllers\Dashboard\QuickActionController;
use App\Http\Controllers\Dashboard\RecentActivitiesController;
use App\Http\Controllers\Dashboard\WorkspaceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Shell\CommandPaletteController;
use App\Http\Controllers\Lookup\LookupController;
use App\Http\Controllers\Shell\GlobalSearchController;
use App\Http\Controllers\Shell\NotificationDrawerController;
use App\Http\Controllers\Shell\ShellPreferenceController;
use App\Http\Controllers\Ess\EssAttendanceController;
use App\Http\Controllers\Ess\EssDashboardController;
use App\Http\Controllers\Ess\EssDocumentController;
use App\Http\Controllers\Ess\EssLeaveController;
use App\Http\Controllers\Ess\EssPayrollController;
use App\Http\Controllers\Ess\EssProfileController;
use App\Http\Controllers\Hrms\AnnouncementController as HrmsAnnouncementController;
use App\Http\Controllers\Hrms\AppraisalCalibrationController as HrmsAppraisalCalibrationController;
use App\Http\Controllers\Hrms\AppraisalDashboardController as HrmsAppraisalDashboardController;
use App\Http\Controllers\Hrms\AppraisalDevelopmentPlanController as HrmsAppraisalDevelopmentPlanController;
use App\Http\Controllers\Hrms\AppraisalSessionController as HrmsAppraisalSessionController;
use App\Http\Controllers\Hrms\AssetController as HrmsAssetController;
use App\Http\Controllers\Hrms\AttendanceCalendarController as HrmsAttendanceCalendarController;
use App\Http\Controllers\Hrms\AttendanceController as HrmsAttendanceController;
use App\Http\Controllers\Hrms\BranchController as HrmsBranchController;
use App\Http\Controllers\Hrms\CompetencyCategoryController as HrmsCompetencyCategoryController;
use App\Http\Controllers\Hrms\CompetencyController as HrmsCompetencyController;
use App\Http\Controllers\Hrms\DepartmentController as HrmsDepartmentController;
use App\Http\Controllers\Hrms\DesignationController as HrmsDesignationController;
use App\Http\Controllers\Hrms\EmployeeAppraisalController as HrmsEmployeeAppraisalController;
use App\Http\Controllers\Hrms\EmployeeController as HrmsEmployeeController;
use App\Http\Controllers\Hrms\EmployeeDirectoryController;
use App\Http\Controllers\Hrms\EmployeeDocumentController as HrmsEmployeeDocumentController;
use App\Http\Controllers\Hrms\EmployeeExitController as HrmsEmployeeExitController;
use App\Http\Controllers\Hrms\EmployeeSalaryAssignmentController as HrmsEmployeeSalaryAssignmentController;
use App\Http\Controllers\Hrms\EmployeeTimelineController;
use App\Http\Controllers\Hrms\FeedbackCampaignController as HrmsFeedbackCampaignController;
use App\Http\Controllers\Hrms\FeedbackDashboardController as HrmsFeedbackDashboardController;
use App\Http\Controllers\Hrms\FeedbackReportController as HrmsFeedbackReportController;
use App\Http\Controllers\Hrms\FeedbackRequestController as HrmsFeedbackRequestController;
use App\Http\Controllers\Hrms\FeedbackTemplateController as HrmsFeedbackTemplateController;
use App\Http\Controllers\Hrms\GoalCategoryController as HrmsGoalCategoryController;
use App\Http\Controllers\Hrms\GoalCheckinController as HrmsGoalCheckinController;
use App\Http\Controllers\Hrms\GoalController as HrmsGoalController;
use App\Http\Controllers\Hrms\GoalLibraryController as HrmsGoalLibraryController;
use App\Http\Controllers\Hrms\GoalProgressController as HrmsGoalProgressController;
use App\Http\Controllers\Hrms\HolidayController as HrmsHolidayController;
use App\Http\Controllers\Hrms\CandidateController as HrmsCandidateController;
use App\Http\Controllers\Hrms\CandidateEvaluationController as HrmsCandidateEvaluationController;
use App\Http\Controllers\Hrms\EvaluationTemplateController as HrmsRecruitmentEvaluationTemplateController;
use App\Http\Controllers\Hrms\InterviewRoundController as HrmsInterviewRoundController;
use App\Http\Controllers\Hrms\InterviewStageController as HrmsInterviewStageController;
use App\Http\Controllers\Hrms\JobApplicationController as HrmsJobApplicationController;
use App\Http\Controllers\Hrms\HiringDecisionController as HrmsHiringDecisionController;
use App\Http\Controllers\Hrms\OfferApprovalController as HrmsOfferApprovalController;
use App\Http\Controllers\Hrms\OfferLetterController as HrmsOfferLetterController;
use App\Http\Controllers\Hrms\OfferNegotiationController as HrmsOfferNegotiationController;
use App\Http\Controllers\Hrms\OfferTemplateController as HrmsOfferTemplateController;
use App\Http\Controllers\Hrms\JobOpeningController as HrmsJobOpeningController;
use App\Http\Controllers\Hrms\JobRequisitionController as HrmsJobRequisitionController;
use App\Http\Controllers\Hrms\HrmsDashboardController;
use App\Http\Controllers\Hrms\HrmsHomeController;
use App\Http\Controllers\Hrms\KpiController as HrmsKpiController;
use App\Http\Controllers\Hrms\LeaveApplicationController as HrmsLeaveApplicationController;
use App\Http\Controllers\Hrms\LeaveBalanceController as HrmsLeaveBalanceController;
use App\Http\Controllers\Hrms\LeaveDashboardController as HrmsLeaveDashboardController;
use App\Http\Controllers\Hrms\LeaveTypeController as HrmsLeaveTypeController;
use App\Http\Controllers\Hrms\ManagerDashboardController;
use App\Http\Controllers\Hrms\OrganizationCalendarController;
use App\Http\Controllers\Hrms\PayrollConfigurationController as HrmsPayrollConfigurationController;
use App\Http\Controllers\Hrms\PayrollDashboardController as HrmsPayrollDashboardController;
use App\Http\Controllers\Hrms\PayrollFinanceController as HrmsPayrollFinanceController;
use App\Http\Controllers\Hrms\PayrollPeriodController as HrmsPayrollPeriodController;
use App\Http\Controllers\Hrms\PayrollResultController as HrmsPayrollResultController;
use App\Http\Controllers\Hrms\PayrollRunController as HrmsPayrollRunController;
use App\Http\Controllers\Hrms\PayslipController as HrmsPayslipController;
use App\Http\Controllers\Hrms\PerformanceConfigurationController as HrmsPerformanceConfigurationController;
use App\Http\Controllers\Hrms\PerformanceCycleController as HrmsPerformanceCycleController;
use App\Http\Controllers\Hrms\PerformanceDashboardController as HrmsPerformanceDashboardController;
use App\Http\Controllers\Hrms\PerformanceRatingScaleController as HrmsPerformanceRatingScaleController;
use App\Http\Controllers\Hrms\PerformanceReviewAssignmentController as HrmsPerformanceReviewAssignmentController;
use App\Http\Controllers\Hrms\PerformanceReviewController as HrmsPerformanceReviewController;
use App\Http\Controllers\Hrms\PerformanceReviewTemplateController as HrmsPerformanceReviewTemplateController;
use App\Http\Controllers\Hrms\RecruitmentDashboardController as HrmsRecruitmentDashboardController;
use App\Http\Controllers\Hrms\RecruitmentAnalyticsController as HrmsRecruitmentAnalyticsController;
use App\Http\Controllers\Hrms\RecruitmentReportController as HrmsRecruitmentReportController;
use App\Http\Controllers\Hrms\RecruitmentSavedReportController as HrmsRecruitmentSavedReportController;
use App\Http\Controllers\Hrms\RecruitmentExportController as HrmsRecruitmentExportController;
use App\Http\Controllers\Hrms\SalaryComponentController as HrmsSalaryComponentController;
use App\Http\Controllers\Hrms\SalaryStructureController as HrmsSalaryStructureController;
use App\Http\Controllers\Hrms\ShiftAssignmentController as HrmsShiftAssignmentController;
use App\Http\Controllers\Hrms\ShiftController as HrmsShiftController;
use App\Http\Controllers\Hrms\StatutoryComplianceController as HrmsStatutoryComplianceController;
use App\Http\Controllers\Hrms\TalentMatrixController as HrmsTalentMatrixController;
use App\Http\Controllers\Hrms\TeamController as HrmsTeamController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\KnowledgeCenterController;
use App\Http\Controllers\MarketingProviderOAuthController;
use App\Http\Controllers\MarketingTrackingController;
use App\Http\Controllers\MetadataFieldBlueprintActivationController;
use App\Http\Controllers\MetadataFieldDefinitionController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSettings\HrConfigurationController;
use App\Http\Controllers\OrganizationSettings\OrganizationSettingsHubController;
use App\Http\Controllers\OrganizationSetupController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PortfolioExecutiveController;
use App\Http\Controllers\PortfolioForecastController;
use App\Http\Controllers\PortfolioReportController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProjectBaselineController;
use App\Http\Controllers\ProjectBudgetController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\ProjectCalendarController;
use App\Http\Controllers\ProjectCollaborationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDependencyController;
use App\Http\Controllers\ProjectExecutiveDashboardController;
use App\Http\Controllers\ProjectAutomationController;
use App\Http\Controllers\ProjectGanttController;
use App\Http\Controllers\ProjectHealthController;
use App\Http\Controllers\ProjectIssueController;
use App\Http\Controllers\ProjectLabelController;
use App\Http\Controllers\ProjectMentionController;
use App\Http\Controllers\ProjectProgressController;
use App\Http\Controllers\ProjectProgressDashboardController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectRiskController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\ResourceAllocationController;
use App\Http\Controllers\ResourceCalendarController;
use App\Http\Controllers\ResourcePlannerController;
use App\Http\Controllers\ProjectLifecycleStageController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectMilestoneController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\ProjectTemplateController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\ProjectWatcherController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedFilterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskChecklistController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDependencyController;
use App\Http\Controllers\TaskPriorityController;
use App\Http\Controllers\TaskRecurrenceController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\TaskTimeLogController;
use App\Http\Controllers\TaskWatcherController;
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
        $user = Auth::user();
        $organization = $user->organizations()->find(session('current_organization_id'))
            ?? $user->organizations()->first();

        if ($organization) {
            $landing = app(\App\Services\Navigation\NavigationService::class)
                ->resolveLandingUrl($user, $organization);

            return redirect()->to($landing);
        }

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

    Route::middleware(['ensure.organization', 'organization.lifecycle', 'module'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->middleware('verified')->name('dashboard');
        Route::redirect('/app', '/dashboard')->middleware('verified')->name('app');

        Route::prefix('shell')->name('shell.')->group(function () {
            Route::patch('preferences', [ShellPreferenceController::class, 'update'])->name('preferences.update');
            Route::post('workspace', [ShellPreferenceController::class, 'switchWorkspace'])->name('workspace.switch');
            Route::post('workspace-favorites', [ShellPreferenceController::class, 'toggleFavoriteWorkspace'])->name('workspace-favorites.toggle');
            Route::post('favorites', [ShellPreferenceController::class, 'toggleFavorite'])->name('favorites.toggle');
            Route::post('recents', [ShellPreferenceController::class, 'recordRecent'])->name('recents.store');
            Route::delete('recents', [ShellPreferenceController::class, 'clearRecents'])->name('recents.clear');
            Route::get('commands', [CommandPaletteController::class, 'index'])->name('commands.index');
            Route::post('commands/recent', [CommandPaletteController::class, 'record'])->name('commands.record');
            Route::get('search', [GlobalSearchController::class, 'index'])->name('search.index');
            Route::get('lookups/{entity}', [LookupController::class, 'search'])->name('lookups.search');
            Route::get('notifications', [NotificationDrawerController::class, 'index'])->name('notifications.index');
        });

        Route::prefix('dashboard')->middleware('permission:dashboard.view')->name('dashboard.')->group(function () {
            Route::get('/workspace', [WorkspaceController::class, 'show'])->name('workspace');
            Route::get('/api', [DashboardApiController::class, 'index'])->name('api');
            Route::get('/widgets', [DashboardWidgetController::class, 'index'])->name('widgets.index');
            Route::get('/widgets/{widgetKey}/data', [DashboardWidgetController::class, 'data'])->name('widgets.data');
            Route::post('/widgets/{widget}/refresh', [DashboardWidgetController::class, 'refresh'])->name('widgets.refresh');
            Route::patch('/widgets/{widget}/organization', [DashboardWidgetController::class, 'updateOrganization'])
                ->middleware('permission:dashboard.manage')
                ->name('widgets.organization.update');
            Route::get('/preferences', [DashboardPreferenceController::class, 'show'])->name('preferences.show');
            Route::post('/preferences', [DashboardPreferenceController::class, 'update'])
                ->middleware('permission:dashboard.customize')
                ->name('preferences.update');
            Route::delete('/preferences', [DashboardPreferenceController::class, 'reset'])
                ->middleware('permission:dashboard.customize')
                ->name('preferences.reset');
            Route::post('/widgets/{widget}/hide', [DashboardPreferenceController::class, 'hide'])
                ->middleware('permission:dashboard.customize')
                ->name('widgets.hide');
            Route::post('/widgets/{widget}/restore', [DashboardPreferenceController::class, 'restore'])
                ->middleware('permission:dashboard.customize')
                ->name('widgets.restore');
            Route::get('/quick-actions', [QuickActionController::class, 'index'])->name('quick-actions.index');
            Route::patch('/quick-actions/{quickAction}/organization', [QuickActionController::class, 'updateOrganization'])
                ->middleware('permission:dashboard.manage')
                ->name('quick-actions.organization.update');
            Route::get('/recent-activities', [RecentActivitiesController::class, 'index'])->name('recent-activities');
        });

        Route::get('hrms/home', HrmsHomeController::class)
            ->name('hrms.home');
        Route::get('hrms', HrmsDashboardController::class)
            ->middleware('permission:hr.dashboard')
            ->name('hrms.dashboard');
        Route::get('hrms/manager/dashboard', ManagerDashboardController::class)
            ->middleware('permission:manager.dashboard')
            ->name('hrms.manager.dashboard');
        Route::resource('hrms/announcements', HrmsAnnouncementController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:announcements.manage')
            ->names('hrms.announcements');
        Route::resource('hrms/assets', HrmsAssetController::class)
            ->middleware('permission:assets.view')
            ->names('hrms.assets');
        Route::post('hrms/assets/{asset}/assign', [HrmsAssetController::class, 'assign'])
            ->middleware('permission:assets.manage')
            ->name('hrms.assets.assign');
        Route::post('hrms/assets/{asset}/return', [HrmsAssetController::class, 'returnAsset'])
            ->middleware('permission:assets.manage')
            ->name('hrms.assets.return');
        Route::post('hrms/assets/{asset}/mark-lost', [HrmsAssetController::class, 'markLost'])
            ->middleware('permission:assets.manage')
            ->name('hrms.assets.mark-lost');
        Route::resource('hrms/exit-processes', HrmsEmployeeExitController::class)
            ->except(['create', 'edit', 'destroy'])
            ->middleware('permission:employee.exit.manage')
            ->parameters(['exit-processes' => 'exitProcess'])
            ->names('hrms.exit-processes');
        Route::post('hrms/exit-processes/{exitProcess}/complete', [HrmsEmployeeExitController::class, 'complete'])
            ->middleware('permission:employee.exit.manage')
            ->name('hrms.exit-processes.complete');
        Route::post('hrms/exit-processes/{exitProcess}/cancel', [HrmsEmployeeExitController::class, 'cancel'])
            ->middleware('permission:employee.exit.manage')
            ->name('hrms.exit-processes.cancel');
        Route::get('hrms/directory', [EmployeeDirectoryController::class, 'index'])
            ->middleware('permission:employee.directory')
            ->name('hrms.directory.index');
        Route::get('hrms/directory/{employee}', [EmployeeDirectoryController::class, 'show'])
            ->middleware('permission:employee.directory')
            ->name('hrms.directory.show');
        Route::get('hrms/calendar', OrganizationCalendarController::class)
            ->middleware('permission:organization.calendar')
            ->name('hrms.calendar');
        Route::get('hrms/employees/{employee}/timeline', [EmployeeTimelineController::class, 'show'])
            ->middleware('permission:hrms.view')
            ->name('hrms.employees.timeline');
        Route::resource('hrms/branches', HrmsBranchController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:hrms.view')
            ->names('hrms.branches');
        Route::resource('hrms/departments', HrmsDepartmentController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:hrms.view')
            ->names('hrms.departments');
        Route::resource('hrms/designations', HrmsDesignationController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:hrms.view')
            ->names('hrms.designations');
        Route::resource('hrms/teams', HrmsTeamController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:hrms.view')
            ->names('hrms.teams');
        Route::post('hrms/employees/bulk-provision', [HrmsEmployeeController::class, 'bulkProvision'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.bulk-provision');
        Route::resource('hrms/employees', HrmsEmployeeController::class)
            ->middleware('permission:hrms.view')
            ->names('hrms.employees');
        Route::post('hrms/employees/{employee}/exit', [HrmsEmployeeController::class, 'exit'])
            ->middleware('permission:hrms.update')
            ->name('hrms.employees.exit');
        Route::post('hrms/employees/{employee}/link-user', [HrmsEmployeeController::class, 'linkUser'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.link-user');
        Route::delete('hrms/employees/{employee}/unlink-user', [HrmsEmployeeController::class, 'unlinkUser'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.unlink-user');
        Route::post('hrms/employees/{employee}/resend-invitation', [HrmsEmployeeController::class, 'resendInvitation'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.resend-invitation');
        Route::post('hrms/employees/{employee}/portal/enable', [HrmsEmployeeController::class, 'enablePortal'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.portal.enable');
        Route::post('hrms/employees/{employee}/portal/disable', [HrmsEmployeeController::class, 'disablePortal'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.portal.disable');
        Route::post('hrms/employees/{employee}/account/lock', [HrmsEmployeeController::class, 'lockAccount'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.account.lock');
        Route::post('hrms/employees/{employee}/account/unlock', [HrmsEmployeeController::class, 'unlockAccount'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.account.unlock');
        Route::post('hrms/employees/{employee}/password-reset', [HrmsEmployeeController::class, 'resetPassword'])
            ->middleware('permission:hrms.manage')
            ->name('hrms.employees.password-reset');

        Route::prefix('hrms/employees/{employee}')->scopeBindings()->group(function () {
            Route::get('documents', [HrmsEmployeeDocumentController::class, 'index'])
                ->middleware('permission:hrms.view')
                ->name('hrms.employees.documents.index');
            Route::get('documents/create', [HrmsEmployeeDocumentController::class, 'create'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.create');
            Route::post('documents', [HrmsEmployeeDocumentController::class, 'store'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.store');
            Route::get('documents/{document}', [HrmsEmployeeDocumentController::class, 'show'])
                ->middleware('permission:hrms.view')
                ->name('hrms.employees.documents.show');
            Route::put('documents/{document}', [HrmsEmployeeDocumentController::class, 'update'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.update');
            Route::delete('documents/{document}', [HrmsEmployeeDocumentController::class, 'destroy'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.destroy');
            Route::get('documents/{document}/download', [HrmsEmployeeDocumentController::class, 'download'])
                ->middleware('permission:hrms.view')
                ->name('hrms.employees.documents.download');
            Route::post('documents/{document}/verify', [HrmsEmployeeDocumentController::class, 'verify'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.verify');
            Route::post('documents/{document}/restore-version', [HrmsEmployeeDocumentController::class, 'restoreVersion'])
                ->middleware('permission:hrms.documents.manage')
                ->name('hrms.employees.documents.restore-version');
        });

        Route::resource('hrms/shifts', HrmsShiftController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:attendance.view')
            ->names('hrms.shifts');
        Route::get('hrms/shift-assignments', [HrmsShiftAssignmentController::class, 'index'])
            ->middleware('permission:attendance.view')
            ->name('hrms.shift-assignments.index');
        Route::post('hrms/shift-assignments', [HrmsShiftAssignmentController::class, 'store'])
            ->middleware('permission:attendance.manage')
            ->name('hrms.shift-assignments.store');

        Route::prefix('hrms/attendance')->group(function () {
            Route::get('/', [HrmsAttendanceCalendarController::class, 'index'])
                ->middleware('permission:attendance.view')
                ->name('hrms.attendance.index');
            Route::get('/records', [HrmsAttendanceController::class, 'records'])
                ->middleware('permission:attendance.view')
                ->name('hrms.attendance.records');
            Route::get('/summary', [HrmsAttendanceController::class, 'summary'])
                ->middleware('permission:attendance.view')
                ->name('hrms.attendance.summary');
            Route::post('/clock-in', [HrmsAttendanceController::class, 'clockIn'])
                ->middleware('permission:attendance.manage')
                ->name('hrms.attendance.clock-in');
            Route::post('/clock-out', [HrmsAttendanceController::class, 'clockOut'])
                ->middleware('permission:attendance.manage')
                ->name('hrms.attendance.clock-out');
            Route::get('/corrections', [HrmsAttendanceController::class, 'correctionsIndex'])
                ->middleware('permission:attendance.view')
                ->name('hrms.attendance.corrections.index');
            Route::post('/corrections', [HrmsAttendanceController::class, 'storeCorrection'])
                ->middleware('permission:attendance.correct')
                ->name('hrms.attendance.corrections.store');
            Route::post('/corrections/{correction}/approve', [HrmsAttendanceController::class, 'approveCorrection'])
                ->middleware('permission:attendance.correct')
                ->name('hrms.attendance.corrections.approve');
            Route::post('/corrections/{correction}/reject', [HrmsAttendanceController::class, 'rejectCorrection'])
                ->middleware('permission:attendance.correct')
                ->name('hrms.attendance.corrections.reject');
            Route::get('/{attendance}', [HrmsAttendanceController::class, 'show'])
                ->middleware('permission:attendance.view')
                ->name('hrms.attendance.show');
        });

        Route::get('hrms/leave', HrmsLeaveDashboardController::class)
            ->middleware('permission:leave.view')
            ->name('hrms.leave.dashboard');
        Route::resource('hrms/leave-types', HrmsLeaveTypeController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:leave.view')
            ->names('hrms.leave-types');
        Route::resource('hrms/holidays', HrmsHolidayController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:leave.view')
            ->names('hrms.holidays');
        Route::get('hrms/leave-balances', [HrmsLeaveBalanceController::class, 'index'])
            ->middleware('permission:leave.view')
            ->name('hrms.leave-balances.index');
        Route::post('hrms/leave-balances/adjust', [HrmsLeaveBalanceController::class, 'adjust'])
            ->middleware('permission:leave.manage')
            ->name('hrms.leave-balances.adjust');
        Route::post('hrms/leave-balances/allocate', [HrmsLeaveBalanceController::class, 'allocate'])
            ->middleware('permission:leave.manage')
            ->name('hrms.leave-balances.allocate');
        Route::get('hrms/leave-balances/ledger', [HrmsLeaveBalanceController::class, 'ledger'])
            ->middleware('permission:leave.view')
            ->name('hrms.leave-balances.ledger');
        Route::get('hrms/leave-applications/approval-queue', [HrmsLeaveApplicationController::class, 'approvalQueue'])
            ->middleware('permission:leave.approve')
            ->name('hrms.leave-applications.approval-queue');
        Route::resource('hrms/leave-applications', HrmsLeaveApplicationController::class)
            ->middleware('permission:leave.view')
            ->names('hrms.leave-applications');
        Route::post('hrms/leave-applications/{leave_application}/approve', [HrmsLeaveApplicationController::class, 'approve'])
            ->middleware('permission:leave.approve')
            ->name('hrms.leave-applications.approve');
        Route::post('hrms/leave-applications/{leave_application}/reject', [HrmsLeaveApplicationController::class, 'reject'])
            ->middleware('permission:leave.approve')
            ->name('hrms.leave-applications.reject');
        Route::post('hrms/leave-applications/{leave_application}/cancel', [HrmsLeaveApplicationController::class, 'cancel'])
            ->middleware('permission:leave.manage')
            ->name('hrms.leave-applications.cancel');

        Route::get('hrms/recruitment', HrmsRecruitmentDashboardController::class)
            ->middleware('permission:recruitment.view')
            ->name('hrms.recruitment.dashboard');
        Route::get('hrms/recruitment/executive', [HrmsRecruitmentDashboardController::class, 'executive'])
            ->middleware('permission:recruitment.analytics.view')
            ->name('hrms.recruitment.executive');
        Route::get('hrms/recruitment/analytics', HrmsRecruitmentAnalyticsController::class)
            ->middleware('permission:recruitment.analytics.view')
            ->name('hrms.recruitment.analytics');
        Route::get('hrms/recruitment/reports', [HrmsRecruitmentReportController::class, 'index'])
            ->middleware('permission:recruitment.reports.view')
            ->name('hrms.recruitment.reports.index');
        Route::resource('hrms/recruitment/saved-reports', HrmsRecruitmentSavedReportController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:recruitment.reports.view')
            ->names('hrms.recruitment.saved-reports')
            ->parameters(['saved-reports' => 'recruitment_saved_report']);
        Route::post('hrms/recruitment/saved-reports/{recruitment_saved_report}/share', [HrmsRecruitmentSavedReportController::class, 'share'])
            ->middleware('permission:recruitment.reports.manage')
            ->name('hrms.recruitment.saved-reports.share');
        Route::get('hrms/recruitment/exports', [HrmsRecruitmentExportController::class, 'index'])
            ->middleware('permission:recruitment.reports.export')
            ->name('hrms.recruitment.exports.index');
        Route::post('hrms/recruitment/exports', [HrmsRecruitmentExportController::class, 'download'])
            ->middleware('permission:recruitment.reports.export')
            ->name('hrms.recruitment.exports.download');
        Route::resource('hrms/recruitment/requisitions', HrmsJobRequisitionController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:recruitment.view')
            ->names('hrms.recruitment.requisitions')
            ->parameters(['requisitions' => 'job_requisition']);
        Route::post('hrms/recruitment/requisitions/{job_requisition}/submit', [HrmsJobRequisitionController::class, 'submit'])
            ->middleware('permission:recruitment.edit')
            ->name('hrms.recruitment.requisitions.submit');
        Route::post('hrms/recruitment/requisitions/{job_requisition}/approve', [HrmsJobRequisitionController::class, 'approve'])
            ->middleware('permission:recruitment.manage')
            ->name('hrms.recruitment.requisitions.approve');
        Route::post('hrms/recruitment/requisitions/{job_requisition}/reject', [HrmsJobRequisitionController::class, 'reject'])
            ->middleware('permission:recruitment.manage')
            ->name('hrms.recruitment.requisitions.reject');
        Route::resource('hrms/recruitment/openings', HrmsJobOpeningController::class)
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:recruitment.view')
            ->names('hrms.recruitment.openings')
            ->parameters(['openings' => 'job_opening']);
        Route::post('hrms/recruitment/openings/{job_opening}/publish', [HrmsJobOpeningController::class, 'publish'])
            ->middleware('permission:recruitment.manage')
            ->name('hrms.recruitment.openings.publish');
        Route::resource('hrms/recruitment/candidates', HrmsCandidateController::class)
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:recruitment.view')
            ->names('hrms.recruitment.candidates');
        Route::resource('hrms/recruitment/applications', HrmsJobApplicationController::class)
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:recruitment.view')
            ->names('hrms.recruitment.applications')
            ->parameters(['applications' => 'job_application']);
        Route::post('hrms/recruitment/applications/{job_application}/stage', [HrmsJobApplicationController::class, 'updateStage'])
            ->middleware('permission:recruitment.edit')
            ->name('hrms.recruitment.applications.stage');
        Route::resource('hrms/recruitment/interview-stages', HrmsInterviewStageController::class)
            ->except(['create', 'edit', 'show'])
            ->middleware('permission:recruitment.interview.view')
            ->names('hrms.recruitment.interview-stages');
        Route::resource('hrms/recruitment/evaluation-templates', HrmsRecruitmentEvaluationTemplateController::class)
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:recruitment.interview.view')
            ->names('hrms.recruitment.evaluation-templates');
        Route::resource('hrms/recruitment/interview-rounds', HrmsInterviewRoundController::class)
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:recruitment.interview.view')
            ->names('hrms.recruitment.interview-rounds');
        Route::post('hrms/recruitment/interview-rounds/{interview_round}/complete', [HrmsInterviewRoundController::class, 'complete'])
            ->middleware('permission:recruitment.interview.edit')
            ->name('hrms.recruitment.interview-rounds.complete');
        Route::post('hrms/recruitment/interview-rounds/{interview_round}/cancel', [HrmsInterviewRoundController::class, 'cancel'])
            ->middleware('permission:recruitment.interview.edit')
            ->name('hrms.recruitment.interview-rounds.cancel');
        Route::get('hrms/recruitment/interview-rounds/{interview_round}/evaluate', [HrmsCandidateEvaluationController::class, 'create'])
            ->middleware('permission:recruitment.evaluate')
            ->name('hrms.recruitment.interview-rounds.evaluate');
        Route::resource('hrms/recruitment/evaluations', HrmsCandidateEvaluationController::class)
            ->only(['index', 'show', 'store'])
            ->middleware('permission:recruitment.interview.view')
            ->names('hrms.recruitment.evaluations');

        Route::resource('hrms/recruitment/offer-templates', HrmsOfferTemplateController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:recruitment.offer.view')
            ->names('hrms.recruitment.offer-templates');
        Route::resource('hrms/recruitment/offers', HrmsOfferLetterController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:recruitment.offer.view')
            ->names('hrms.recruitment.offers');
        Route::post('hrms/recruitment/offers/{offer_letter}/submit', [HrmsOfferLetterController::class, 'submit'])
            ->middleware('permission:recruitment.offer.edit')
            ->name('hrms.recruitment.offers.submit');
        Route::post('hrms/recruitment/offers/{offer_letter}/send', [HrmsOfferLetterController::class, 'send'])
            ->middleware('permission:recruitment.offer.edit')
            ->name('hrms.recruitment.offers.send');
        Route::post('hrms/recruitment/offers/{offer_letter}/accept', [HrmsOfferLetterController::class, 'accept'])
            ->middleware('permission:recruitment.offer.edit')
            ->name('hrms.recruitment.offers.accept');
        Route::post('hrms/recruitment/offers/{offer_letter}/reject', [HrmsOfferLetterController::class, 'reject'])
            ->middleware('permission:recruitment.offer.edit')
            ->name('hrms.recruitment.offers.reject');
        Route::post('hrms/recruitment/offers/{offer_letter}/withdraw', [HrmsOfferLetterController::class, 'withdraw'])
            ->middleware('permission:recruitment.offer.edit')
            ->name('hrms.recruitment.offers.withdraw');
        Route::resource('hrms/recruitment/offer-approvals', HrmsOfferApprovalController::class)
            ->only(['index', 'show'])
            ->middleware('permission:recruitment.offer.view')
            ->names('hrms.recruitment.offer-approvals');
        Route::post('hrms/recruitment/offer-approvals/{offer_approval}/approve', [HrmsOfferApprovalController::class, 'approve'])
            ->middleware('permission:recruitment.offer.approve')
            ->name('hrms.recruitment.offer-approvals.approve');
        Route::post('hrms/recruitment/offer-approvals/{offer_approval}/reject', [HrmsOfferApprovalController::class, 'reject'])
            ->middleware('permission:recruitment.offer.approve')
            ->name('hrms.recruitment.offer-approvals.reject');
        Route::post('hrms/recruitment/offer-approvals/{offer_approval}/return', [HrmsOfferApprovalController::class, 'returnForRevision'])
            ->middleware('permission:recruitment.offer.approve')
            ->name('hrms.recruitment.offer-approvals.return');
        Route::resource('hrms/recruitment/negotiations', HrmsOfferNegotiationController::class)
            ->only(['index', 'show', 'store'])
            ->middleware('permission:recruitment.offer.view')
            ->names('hrms.recruitment.negotiations');
        Route::resource('hrms/recruitment/hiring-decisions', HrmsHiringDecisionController::class)
            ->only(['index', 'show', 'store'])
            ->middleware('permission:recruitment.offer.view')
            ->names('hrms.recruitment.hiring-decisions');
        Route::get('hrms/recruitment/careers/settings', [\App\Http\Controllers\Hrms\CareerSiteSettingsController::class, 'edit'])
            ->middleware('permission:recruitment.careers.manage')
            ->name('hrms.recruitment.careers.settings.edit');
        Route::put('hrms/recruitment/careers/settings', [\App\Http\Controllers\Hrms\CareerSiteSettingsController::class, 'update'])
            ->middleware('permission:recruitment.careers.manage')
            ->name('hrms.recruitment.careers.settings.update');
        Route::get('hrms/recruitment/portal/settings', [\App\Http\Controllers\Hrms\CandidatePortalSettingsController::class, 'edit'])
            ->middleware('permission:recruitment.portal.settings')
            ->name('hrms.recruitment.portal.settings.edit');
        Route::put('hrms/recruitment/portal/settings', [\App\Http\Controllers\Hrms\CandidatePortalSettingsController::class, 'update'])
            ->middleware('permission:recruitment.portal.settings')
            ->name('hrms.recruitment.portal.settings.update');
        Route::get('hrms/recruitment/portal/accounts', [\App\Http\Controllers\Hrms\CandidateAccountAdminController::class, 'index'])
            ->middleware('permission:recruitment.portal.manage')
            ->name('hrms.recruitment.portal.accounts.index');

        Route::get('hrms/recruitment/integrations', [\App\Http\Controllers\Hrms\RecruitmentIntegrationController::class, 'index'])
            ->middleware('permission:recruitment.integration.view')
            ->name('hrms.recruitment.integrations.index');
        Route::post('hrms/recruitment/integrations/{provider}/connect', [\App\Http\Controllers\Hrms\RecruitmentIntegrationController::class, 'connect'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.integrations.connect');
        Route::post('hrms/recruitment/integrations/providers/{recruitment_provider}/disconnect', [\App\Http\Controllers\Hrms\RecruitmentIntegrationController::class, 'disconnect'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.integrations.disconnect');
        Route::post('hrms/recruitment/integrations/providers/{recruitment_provider}/health-check', [\App\Http\Controllers\Hrms\RecruitmentIntegrationController::class, 'healthCheck'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.integrations.health-check');
        Route::post('hrms/recruitment/integrations/retries', [\App\Http\Controllers\Hrms\RecruitmentIntegrationController::class, 'processRetries'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.integrations.retries');

        Route::get('hrms/recruitment/integrations/communication-templates', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'index'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.index');
        Route::post('hrms/recruitment/integrations/communication-templates', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'store'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.store');
        Route::put('hrms/recruitment/integrations/communication-templates/{template}', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'update'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.update');
        Route::post('hrms/recruitment/integrations/communication-templates/{template}/submit', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'submit'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.submit');
        Route::post('hrms/recruitment/integrations/communication-templates/{template}/approve', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'approve'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.approve');
        Route::post('hrms/recruitment/integrations/communication-templates/{template}/deactivate', [\App\Http\Controllers\Hrms\RecruitmentCommunicationTemplateController::class, 'deactivate'])
            ->middleware('permission:recruitment.communication.manage')
            ->name('hrms.recruitment.communication-templates.deactivate');

        Route::get('hrms/recruitment/integrations/calendar', [\App\Http\Controllers\Hrms\RecruitmentCalendarController::class, 'index'])
            ->middleware('permission:recruitment.integration.view')
            ->name('hrms.recruitment.calendar.index');
        Route::post('hrms/recruitment/integrations/calendar/sync', [\App\Http\Controllers\Hrms\RecruitmentCalendarController::class, 'sync'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.calendar.sync');

        Route::get('hrms/recruitment/integrations/job-boards', [\App\Http\Controllers\Hrms\RecruitmentJobBoardController::class, 'index'])
            ->middleware('permission:recruitment.integration.view')
            ->name('hrms.recruitment.job-boards.index');
        Route::post('hrms/recruitment/integrations/job-boards/publish', [\App\Http\Controllers\Hrms\RecruitmentJobBoardController::class, 'publish'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.job-boards.publish');
        Route::post('hrms/recruitment/integrations/job-boards/{listing}/sync', [\App\Http\Controllers\Hrms\RecruitmentJobBoardController::class, 'sync'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.job-boards.sync');
        Route::post('hrms/recruitment/integrations/job-boards/{listing}/close', [\App\Http\Controllers\Hrms\RecruitmentJobBoardController::class, 'close'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.job-boards.close');

        Route::get('hrms/recruitment/integrations/resume-parsing', [\App\Http\Controllers\Hrms\RecruitmentResumeParsingController::class, 'index'])
            ->middleware('permission:recruitment.integration.view')
            ->name('hrms.recruitment.resume-parsing.index');
        Route::post('hrms/recruitment/integrations/resume-parsing', [\App\Http\Controllers\Hrms\RecruitmentResumeParsingController::class, 'store'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.resume-parsing.store');
        Route::post('hrms/recruitment/integrations/resume-parsing/{parseRequest}/apply', [\App\Http\Controllers\Hrms\RecruitmentResumeParsingController::class, 'apply'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.resume-parsing.apply');

        Route::get('hrms/recruitment/integrations/background-verification', [\App\Http\Controllers\Hrms\RecruitmentBackgroundVerificationController::class, 'index'])
            ->middleware('permission:recruitment.integration.view')
            ->name('hrms.recruitment.background-verification.index');
        Route::post('hrms/recruitment/integrations/background-verification', [\App\Http\Controllers\Hrms\RecruitmentBackgroundVerificationController::class, 'store'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.background-verification.store');
        Route::post('hrms/recruitment/integrations/background-verification/{verification}/refresh', [\App\Http\Controllers\Hrms\RecruitmentBackgroundVerificationController::class, 'refresh'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.background-verification.refresh');
        Route::post('hrms/recruitment/integrations/background-verification/{verification}/cancel', [\App\Http\Controllers\Hrms\RecruitmentBackgroundVerificationController::class, 'cancel'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.background-verification.cancel');

        Route::get('hrms/recruitment/integrations/api-access', [\App\Http\Controllers\Hrms\RecruitmentApiAccessController::class, 'index'])
            ->middleware('permission:recruitment.api.manage')
            ->name('hrms.recruitment.api-access.index');

        Route::get('hrms/recruitment/integrations/webhooks', [\App\Http\Controllers\Hrms\RecruitmentWebhookController::class, 'index'])
            ->middleware('permission:recruitment.webhook.view')
            ->name('hrms.recruitment.webhooks.index');
        Route::post('hrms/recruitment/integrations/webhooks', [\App\Http\Controllers\Hrms\RecruitmentWebhookController::class, 'store'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.webhooks.store');
        Route::post('hrms/recruitment/integrations/webhooks/deliveries/{delivery}/retry', [\App\Http\Controllers\Hrms\RecruitmentWebhookController::class, 'retry'])
            ->middleware('permission:recruitment.integration.manage')
            ->name('hrms.recruitment.webhooks.retry');

        Route::get('hrms/performance', HrmsPerformanceDashboardController::class)
            ->middleware('permission:performance.view')
            ->name('hrms.performance.index');
        Route::resource('hrms/performance/cycles', HrmsPerformanceCycleController::class)
            ->parameters(['cycles' => 'cycle'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.view')
            ->names('hrms.performance.cycles');
        Route::post('hrms/performance/cycles/{cycle}/activate', [HrmsPerformanceCycleController::class, 'activate'])
            ->middleware('permission:performance.manage')
            ->name('hrms.performance.cycles.activate');
        Route::post('hrms/performance/cycles/{cycle}/close', [HrmsPerformanceCycleController::class, 'close'])
            ->middleware('permission:performance.manage')
            ->name('hrms.performance.cycles.close');
        Route::post('hrms/performance/cycles/{cycle}/archive', [HrmsPerformanceCycleController::class, 'archive'])
            ->middleware('permission:performance.manage')
            ->name('hrms.performance.cycles.archive');
        Route::resource('hrms/performance/competencies', HrmsCompetencyController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.view')
            ->names('hrms.performance.competencies');
        Route::resource('hrms/performance/categories', HrmsCompetencyCategoryController::class)
            ->parameters(['categories' => 'category'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.view')
            ->names('hrms.performance.categories');
        Route::resource('hrms/performance/templates', HrmsPerformanceReviewTemplateController::class)
            ->parameters(['templates' => 'template'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.view')
            ->names('hrms.performance.templates');
        Route::resource('hrms/performance/rating-scales', HrmsPerformanceRatingScaleController::class)
            ->parameters(['rating-scales' => 'rating_scale'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.view')
            ->names('hrms.performance.rating-scales');
        Route::get('hrms/performance/configuration', [HrmsPerformanceConfigurationController::class, 'edit'])
            ->middleware('permission:performance.configuration')
            ->name('hrms.performance.configuration.edit');
        Route::put('hrms/performance/configuration', [HrmsPerformanceConfigurationController::class, 'update'])
            ->middleware('permission:performance.configuration')
            ->name('hrms.performance.configuration.update');

        Route::resource('hrms/performance/goal-categories', HrmsGoalCategoryController::class)
            ->parameters(['goal-categories' => 'goal_category'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.goal.view')
            ->names('hrms.performance.goal-categories');
        Route::resource('hrms/performance/goals/library', HrmsGoalLibraryController::class)
            ->parameters(['library' => 'library'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.goal.view')
            ->names('hrms.performance.goals.library');
        Route::resource('hrms/performance/kpis', HrmsKpiController::class)
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:performance.goal.view')
            ->names('hrms.performance.kpis');
        Route::get('hrms/performance/progress', HrmsGoalProgressController::class)
            ->middleware('permission:performance.goal.view')
            ->name('hrms.performance.progress.index');
        Route::get('hrms/performance/checkins', [HrmsGoalCheckinController::class, 'index'])
            ->middleware('permission:performance.goal.view')
            ->name('hrms.performance.checkins.index');
        Route::post('hrms/performance/checkins/{goal}', [HrmsGoalCheckinController::class, 'store'])
            ->middleware('permission:performance.goal.update')
            ->name('hrms.performance.checkins.store');
        Route::post('hrms/performance/goals/{goal}/progress', [HrmsGoalController::class, 'progress'])
            ->middleware('permission:performance.goal.update')
            ->name('hrms.performance.goals.progress');
        Route::post('hrms/performance/goals/{goal}/complete', [HrmsGoalController::class, 'complete'])
            ->middleware('permission:performance.goal.manage')
            ->name('hrms.performance.goals.complete');
        Route::resource('hrms/performance/goals', HrmsGoalController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:performance.goal.view')
            ->names('hrms.performance.goals');

        Route::get('hrms/performance/my-reviews', [HrmsPerformanceReviewController::class, 'myReviews'])
            ->middleware('permission:performance.review.view')
            ->name('hrms.performance.my-reviews');
        Route::get('hrms/performance/team-reviews', [HrmsPerformanceReviewController::class, 'teamReviews'])
            ->middleware('permission:performance.review.view')
            ->name('hrms.performance.team-reviews');
        Route::post('hrms/performance/reviews/{review}/start', [HrmsPerformanceReviewController::class, 'start'])
            ->middleware('permission:performance.review.submit')
            ->name('hrms.performance.reviews.start');
        Route::post('hrms/performance/reviews/{review}/draft', [HrmsPerformanceReviewController::class, 'saveDraft'])
            ->middleware('permission:performance.review.submit')
            ->name('hrms.performance.reviews.draft');
        Route::post('hrms/performance/reviews/{review}/submit', [HrmsPerformanceReviewController::class, 'submit'])
            ->middleware('permission:performance.review.submit')
            ->name('hrms.performance.reviews.submit');
        Route::post('hrms/performance/reviews/{review}/reviewed', [HrmsPerformanceReviewController::class, 'markReviewed'])
            ->middleware('permission:performance.review.submit')
            ->name('hrms.performance.reviews.reviewed');
        Route::post('hrms/performance/reviews/{review}/close', [HrmsPerformanceReviewController::class, 'close'])
            ->middleware('permission:performance.review.manage')
            ->name('hrms.performance.reviews.close');
        Route::resource('hrms/performance/reviews', HrmsPerformanceReviewController::class)
            ->only(['index', 'show'])
            ->middleware('permission:performance.review.view')
            ->names('hrms.performance.reviews');
        Route::post('hrms/performance/review-assignments/{assignment}/activate', [HrmsPerformanceReviewAssignmentController::class, 'activate'])
            ->middleware('permission:performance.review.manage')
            ->name('hrms.performance.review-assignments.activate');
        Route::resource('hrms/performance/review-assignments', HrmsPerformanceReviewAssignmentController::class)
            ->parameters(['review-assignments' => 'assignment'])
            ->except(['create', 'edit', 'update'])
            ->middleware('permission:performance.review.view')
            ->names('hrms.performance.review-assignments');

        Route::get('hrms/performance/feedback', HrmsFeedbackDashboardController::class)
            ->middleware('permission:performance.feedback.view')
            ->name('hrms.performance.feedback.index');
        Route::post('hrms/performance/feedback/campaigns/{campaign}/activate', [HrmsFeedbackCampaignController::class, 'activate'])
            ->middleware('permission:performance.feedback.manage')
            ->name('hrms.performance.feedback.campaigns.activate');
        Route::post('hrms/performance/feedback/campaigns/{campaign}/close', [HrmsFeedbackCampaignController::class, 'close'])
            ->middleware('permission:performance.feedback.manage')
            ->name('hrms.performance.feedback.campaigns.close');
        Route::post('hrms/performance/feedback/campaigns/{campaign}/generate-requests', [HrmsFeedbackCampaignController::class, 'generateRequests'])
            ->middleware('permission:performance.feedback.manage')
            ->name('hrms.performance.feedback.campaigns.generate-requests');
        Route::post('hrms/performance/feedback/campaigns/{campaign}/participants', [HrmsFeedbackCampaignController::class, 'addParticipant'])
            ->middleware('permission:performance.feedback.manage')
            ->name('hrms.performance.feedback.campaigns.participants.store');
        Route::resource('hrms/performance/feedback/campaigns', HrmsFeedbackCampaignController::class)
            ->parameters(['campaigns' => 'campaign'])
            ->except(['create', 'edit'])
            ->middleware('permission:performance.feedback.view')
            ->names('hrms.performance.feedback.campaigns');
        Route::get('hrms/performance/feedback/my-feedback', [HrmsFeedbackRequestController::class, 'myFeedback'])
            ->middleware('permission:performance.feedback.view')
            ->name('hrms.performance.feedback.my-feedback');
        Route::post('hrms/performance/feedback/requests/{feedbackRequest}/start', [HrmsFeedbackRequestController::class, 'start'])
            ->middleware('permission:performance.feedback.submit')
            ->name('hrms.performance.feedback.requests.start');
        Route::post('hrms/performance/feedback/requests/{feedbackRequest}/submit', [HrmsFeedbackRequestController::class, 'submit'])
            ->middleware('permission:performance.feedback.submit')
            ->name('hrms.performance.feedback.requests.submit');
        Route::resource('hrms/performance/feedback/requests', HrmsFeedbackRequestController::class)
            ->parameters(['requests' => 'feedbackRequest'])
            ->only(['index', 'show'])
            ->middleware('permission:performance.feedback.view')
            ->names('hrms.performance.feedback.requests');
        Route::get('hrms/performance/feedback/reports', [HrmsFeedbackReportController::class, 'index'])
            ->middleware('permission:performance.feedback.view')
            ->name('hrms.performance.feedback.reports.index');
        Route::get('hrms/performance/feedback/reports/{campaign}', [HrmsFeedbackReportController::class, 'show'])
            ->middleware('permission:performance.feedback.view')
            ->name('hrms.performance.feedback.reports.show');
        Route::resource('hrms/performance/feedback/templates', HrmsFeedbackTemplateController::class)
            ->parameters(['templates' => 'template'])
            ->except(['create', 'edit', 'update', 'destroy'])
            ->middleware('permission:performance.feedback.view')
            ->names('hrms.performance.feedback.templates');

        Route::get('hrms/performance/appraisals', HrmsAppraisalDashboardController::class)
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.index');
        Route::get('hrms/performance/appraisals/list', [HrmsEmployeeAppraisalController::class, 'index'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.list');
        Route::get('hrms/performance/appraisals/my', [HrmsEmployeeAppraisalController::class, 'myAppraisal'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.my');
        Route::get('hrms/performance/appraisals/team', [HrmsEmployeeAppraisalController::class, 'teamAppraisals'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.team');
        Route::post('hrms/performance/appraisals/{appraisal}/submit', [HrmsEmployeeAppraisalController::class, 'submit'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.submit');
        Route::post('hrms/performance/appraisals/{appraisal}/hr-review', [HrmsEmployeeAppraisalController::class, 'hrReview'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.hr-review');
        Route::post('hrms/performance/appraisals/{appraisal}/close', [HrmsEmployeeAppraisalController::class, 'close'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.close');
        Route::post('hrms/performance/appraisals/{appraisal}/recalculate', [HrmsEmployeeAppraisalController::class, 'recalculate'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.recalculate');
        Route::post('hrms/performance/appraisals/{appraisal}/promotion', [HrmsEmployeeAppraisalController::class, 'savePromotion'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.promotion');
        Route::post('hrms/performance/appraisals/{appraisal}/compensation', [HrmsEmployeeAppraisalController::class, 'saveCompensation'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.compensation');
        Route::post('hrms/performance/appraisals/{appraisal}/succession', [HrmsEmployeeAppraisalController::class, 'saveSuccession'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.succession');
        Route::put('hrms/performance/appraisals/{appraisal}/development-plan', [HrmsAppraisalDevelopmentPlanController::class, 'update'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.development-plan');
        Route::get('hrms/performance/appraisals/{appraisal}', [HrmsEmployeeAppraisalController::class, 'show'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.appraisals.show');
        Route::put('hrms/performance/appraisals/{appraisal}', [HrmsEmployeeAppraisalController::class, 'update'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisals.update');

        Route::resource('hrms/performance/appraisal-sessions', HrmsAppraisalSessionController::class)
            ->parameters(['appraisal-sessions' => 'session'])
            ->except(['create', 'edit'])
            ->middleware('permission:performance.appraisal.view')
            ->names('hrms.performance.appraisal-sessions');
        Route::post('hrms/performance/appraisal-sessions/{session}/activate', [HrmsAppraisalSessionController::class, 'activate'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisal-sessions.activate');
        Route::post('hrms/performance/appraisal-sessions/{session}/close', [HrmsAppraisalSessionController::class, 'close'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisal-sessions.close');
        Route::post('hrms/performance/appraisal-sessions/{session}/archive', [HrmsAppraisalSessionController::class, 'archive'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisal-sessions.archive');
        Route::post('hrms/performance/appraisal-sessions/{session}/generate', [HrmsAppraisalSessionController::class, 'generate'])
            ->middleware('permission:performance.appraisal.manage')
            ->name('hrms.performance.appraisal-sessions.generate');

        Route::get('hrms/performance/calibration', [HrmsAppraisalCalibrationController::class, 'index'])
            ->middleware('permission:performance.calibration.manage')
            ->name('hrms.performance.calibration.index');
        Route::post('hrms/performance/calibration', [HrmsAppraisalCalibrationController::class, 'store'])
            ->middleware('permission:performance.calibration.manage')
            ->name('hrms.performance.calibration.store');
        Route::get('hrms/performance/calibration/{calibration}', [HrmsAppraisalCalibrationController::class, 'show'])
            ->middleware('permission:performance.calibration.manage')
            ->name('hrms.performance.calibration.show');
        Route::post('hrms/performance/calibration/{calibration}/adjustments', [HrmsAppraisalCalibrationController::class, 'applyAdjustments'])
            ->middleware('permission:performance.calibration.manage')
            ->name('hrms.performance.calibration.adjustments');
        Route::post('hrms/performance/calibration/{calibration}/approve', [HrmsAppraisalCalibrationController::class, 'approve'])
            ->middleware('permission:performance.calibration.manage')
            ->name('hrms.performance.calibration.approve');

        Route::get('hrms/performance/talent-matrix', [HrmsTalentMatrixController::class, 'index'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.talent-matrix.index');
        Route::post('hrms/performance/talent-matrix/classify', [HrmsTalentMatrixController::class, 'classify'])
            ->middleware('permission:performance.talent.manage')
            ->name('hrms.performance.talent-matrix.classify');

        Route::get('hrms/performance/development-plans', [HrmsAppraisalDevelopmentPlanController::class, 'index'])
            ->middleware('permission:performance.appraisal.view')
            ->name('hrms.performance.development-plans.index');

        Route::get('hrms/payroll', HrmsPayrollDashboardController::class)
            ->middleware('permission:payroll.view')
            ->name('hrms.payroll.index');
        Route::resource('hrms/payroll/components', HrmsSalaryComponentController::class)
            ->parameters(['components' => 'component'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:payroll.view')
            ->names('hrms.payroll.components');
        Route::resource('hrms/payroll/structures', HrmsSalaryStructureController::class)
            ->parameters(['structures' => 'structure'])
            ->except(['create', 'show', 'edit'])
            ->middleware('permission:payroll.view')
            ->names('hrms.payroll.structures');
        Route::resource('hrms/payroll/assignments', HrmsEmployeeSalaryAssignmentController::class)
            ->parameters(['assignments' => 'assignment'])
            ->only(['index', 'store'])
            ->middleware('permission:payroll.view')
            ->names('hrms.payroll.assignments');
        Route::resource('hrms/payroll/periods', HrmsPayrollPeriodController::class)
            ->parameters(['periods' => 'period'])
            ->only(['index', 'store'])
            ->middleware('permission:payroll.view')
            ->names('hrms.payroll.periods');
        Route::post('hrms/payroll/periods/{period}/lock', [HrmsPayrollPeriodController::class, 'lock'])
            ->middleware('permission:payroll.manage')
            ->name('hrms.payroll.periods.lock');
        Route::get('hrms/payroll/configuration', [HrmsPayrollConfigurationController::class, 'edit'])
            ->middleware('permission:payroll.configuration')
            ->name('hrms.payroll.configuration.edit');
        Route::put('hrms/payroll/configuration', [HrmsPayrollConfigurationController::class, 'update'])
            ->middleware('permission:payroll.configuration')
            ->name('hrms.payroll.configuration.update');

        Route::get('hrms/payroll/runs/preview', [HrmsPayrollRunController::class, 'previewForm'])
            ->middleware('permission:payroll.calculate')
            ->name('hrms.payroll.runs.preview');
        Route::post('hrms/payroll/runs/preview', [HrmsPayrollRunController::class, 'preview'])
            ->middleware('permission:payroll.calculate')
            ->name('hrms.payroll.runs.preview.submit');
        Route::resource('hrms/payroll/runs', HrmsPayrollRunController::class)
            ->parameters(['runs' => 'run'])
            ->only(['index', 'store', 'show'])
            ->middleware('permission:payroll.view')
            ->names('hrms.payroll.runs');
        Route::post('hrms/payroll/runs/{run}/calculate', [HrmsPayrollRunController::class, 'calculate'])
            ->middleware('permission:payroll.calculate')
            ->name('hrms.payroll.runs.calculate');
        Route::post('hrms/payroll/runs/{run}/recalculate', [HrmsPayrollRunController::class, 'recalculate'])
            ->middleware('permission:payroll.calculate')
            ->name('hrms.payroll.runs.recalculate');
        Route::post('hrms/payroll/runs/{run}/approve', [HrmsPayrollRunController::class, 'approve'])
            ->middleware('permission:payroll.approve')
            ->name('hrms.payroll.runs.approve');
        Route::post('hrms/payroll/runs/{run}/publish', [HrmsPayrollRunController::class, 'publish'])
            ->middleware('permission:payroll.publish')
            ->name('hrms.payroll.runs.publish');
        Route::get('hrms/payroll/results', [HrmsPayrollResultController::class, 'index'])
            ->middleware('permission:payroll.view')
            ->name('hrms.payroll.results.index');
        Route::get('hrms/payroll/results/{result}', [HrmsPayrollResultController::class, 'show'])
            ->middleware('permission:payroll.view')
            ->name('hrms.payroll.results.show');
        Route::get('hrms/payroll/payslips', [HrmsPayslipController::class, 'index'])
            ->middleware('permission:payslip.view')
            ->name('hrms.payroll.payslips.index');
        Route::get('hrms/payroll/payslips/{payslip}', [HrmsPayslipController::class, 'show'])
            ->middleware('permission:payslip.view')
            ->name('hrms.payroll.payslips.show');
        Route::get('hrms/payroll/payslips/{payslip}/download', [HrmsPayslipController::class, 'download'])
            ->middleware('permission:payslip.download')
            ->name('hrms.payroll.payslips.download');
        Route::post('hrms/payroll/payslips/{payslip}/email', [HrmsPayslipController::class, 'resendEmail'])
            ->middleware('permission:payroll.publish')
            ->name('hrms.payroll.payslips.email');

        Route::get('hrms/payroll/statutory', [HrmsStatutoryComplianceController::class, 'index'])
            ->middleware('permission:payroll.statutory.view')
            ->name('hrms.payroll.statutory.index');
        Route::get('hrms/payroll/statutory/profiles', [HrmsStatutoryComplianceController::class, 'profiles'])
            ->middleware('permission:payroll.statutory.view')
            ->name('hrms.payroll.statutory.profiles');
        Route::post('hrms/payroll/statutory/profiles', [HrmsStatutoryComplianceController::class, 'storeProfile'])
            ->middleware('permission:payroll.statutory.manage')
            ->name('hrms.payroll.statutory.profiles.store');
        Route::get('hrms/payroll/statutory/rules', [HrmsStatutoryComplianceController::class, 'rules'])
            ->middleware('permission:payroll.statutory.view')
            ->name('hrms.payroll.statutory.rules');
        Route::post('hrms/payroll/statutory/rules', [HrmsStatutoryComplianceController::class, 'storeRuleSet'])
            ->middleware('permission:payroll.statutory.configuration')
            ->name('hrms.payroll.statutory.rules.store');
        Route::post('hrms/payroll/statutory/rules/seed-india', [HrmsStatutoryComplianceController::class, 'seedIndia'])
            ->middleware('permission:payroll.statutory.configuration')
            ->name('hrms.payroll.statutory.rules.seed-india');
        Route::post('hrms/payroll/statutory/rules/{ruleSet}/activate', [HrmsStatutoryComplianceController::class, 'activateRuleSet'])
            ->middleware('permission:payroll.statutory.configuration')
            ->name('hrms.payroll.statutory.rules.activate');
        Route::get('hrms/payroll/statutory/rules/{ruleSet}/versions/{version}', [HrmsStatutoryComplianceController::class, 'showRuleVersion'])
            ->middleware('permission:payroll.statutory.view')
            ->name('hrms.payroll.statutory.rules.versions.show');
        Route::post('hrms/payroll/statutory/rules/{ruleSet}/versions', [HrmsStatutoryComplianceController::class, 'storeRuleVersion'])
            ->middleware('permission:payroll.statutory.configuration')
            ->name('hrms.payroll.statutory.rules.versions.store');
        Route::get('hrms/payroll/statutory/validation', [HrmsStatutoryComplianceController::class, 'validation'])
            ->middleware('permission:payroll.statutory.view')
            ->name('hrms.payroll.statutory.validation');
        Route::post('hrms/payroll/statutory/validation', [HrmsStatutoryComplianceController::class, 'runValidation'])
            ->middleware('permission:payroll.statutory.manage')
            ->name('hrms.payroll.statutory.validation.run');

        Route::get('hrms/payroll/ledger', [HrmsPayrollFinanceController::class, 'ledgerIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.ledger.index');
        Route::post('hrms/payroll/ledger', [HrmsPayrollFinanceController::class, 'ledgerGenerate'])
            ->middleware('permission:payroll.finance.manage')
            ->name('hrms.payroll.ledger.generate');
        Route::get('hrms/payroll/journals', [HrmsPayrollFinanceController::class, 'journalIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.journals.index');
        Route::get('hrms/payroll/journals/{journal}', [HrmsPayrollFinanceController::class, 'journalShow'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.journals.show');
        Route::get('hrms/payroll/bank-exports', [HrmsPayrollFinanceController::class, 'bankExportIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.bank-exports.index');
        Route::post('hrms/payroll/bank-exports', [HrmsPayrollFinanceController::class, 'bankExportStore'])
            ->middleware('permission:payroll.bank.export')
            ->name('hrms.payroll.bank-exports.store');
        Route::get('hrms/payroll/bank-exports/{export}/download', [HrmsPayrollFinanceController::class, 'bankExportDownload'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.bank-exports.download');
        Route::get('hrms/payroll/loans', [HrmsPayrollFinanceController::class, 'loanIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.loans.index');
        Route::post('hrms/payroll/loans', [HrmsPayrollFinanceController::class, 'loanStore'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.loans.store');
        Route::post('hrms/payroll/loans/{loan}/close', [HrmsPayrollFinanceController::class, 'loanClose'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.loans.close');
        Route::get('hrms/payroll/advances', [HrmsPayrollFinanceController::class, 'advanceIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.advances.index');
        Route::post('hrms/payroll/advances', [HrmsPayrollFinanceController::class, 'advanceStore'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.advances.store');
        Route::post('hrms/payroll/advances/{advance}/approve', [HrmsPayrollFinanceController::class, 'advanceApprove'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.advances.approve');
        Route::post('hrms/payroll/advances/{advance}/reject', [HrmsPayrollFinanceController::class, 'advanceReject'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.advances.reject');
        Route::get('hrms/payroll/reimbursements', [HrmsPayrollFinanceController::class, 'reimbursementIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.reimbursements.index');
        Route::post('hrms/payroll/reimbursements', [HrmsPayrollFinanceController::class, 'reimbursementStore'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.reimbursements.store');
        Route::post('hrms/payroll/reimbursements/{reimbursement}/approve', [HrmsPayrollFinanceController::class, 'reimbursementApprove'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.reimbursements.approve');
        Route::post('hrms/payroll/reimbursements/{reimbursement}/reject', [HrmsPayrollFinanceController::class, 'reimbursementReject'])
            ->middleware('permission:payroll.loan.manage')
            ->name('hrms.payroll.reimbursements.reject');
        Route::get('hrms/payroll/settlements', [HrmsPayrollFinanceController::class, 'settlementIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.settlements.index');
        Route::post('hrms/payroll/settlements', [HrmsPayrollFinanceController::class, 'settlementStore'])
            ->middleware('permission:payroll.settlement.manage')
            ->name('hrms.payroll.settlements.store');
        Route::get('hrms/payroll/settlements/{settlement}', [HrmsPayrollFinanceController::class, 'settlementShow'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.settlements.show');
        Route::get('hrms/payroll/reports', [HrmsPayrollFinanceController::class, 'reportsIndex'])
            ->middleware('permission:payroll.finance.view')
            ->name('hrms.payroll.reports.index');
        Route::post('hrms/payroll/runs/{run}/reverse', [HrmsPayrollFinanceController::class, 'reverseRun'])
            ->middleware('permission:payroll.finance.manage')
            ->name('hrms.payroll.runs.reverse');

        Route::redirect('ess', '/hrms/ess');

        Route::prefix('hrms/ess')->middleware('permission:ess.access')->name('ess.')->group(function () {
            Route::get('/', EssDashboardController::class)->name('dashboard');
            Route::get('/profile', [EssProfileController::class, 'show'])->name('profile');
            Route::put('/profile', [EssProfileController::class, 'update'])->name('profile.update');
            Route::get('/documents', [EssDocumentController::class, 'index'])->name('documents.index');
            Route::get('/documents/{document}', [EssDocumentController::class, 'show'])->name('documents.show');
            Route::get('/documents/{document}/download', [EssDocumentController::class, 'download'])->name('documents.download');
            Route::get('/attendance', [EssAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/attendance/records', [EssAttendanceController::class, 'records'])->name('attendance.records');
            Route::post('/attendance/clock-in', [EssAttendanceController::class, 'clockIn'])->name('attendance.clock-in');
            Route::post('/attendance/clock-out', [EssAttendanceController::class, 'clockOut'])->name('attendance.clock-out');
            Route::post('/attendance/corrections', [EssAttendanceController::class, 'storeCorrection'])->name('attendance.corrections.store');
            Route::get('/leave', [EssLeaveController::class, 'index'])->name('leave.index');
            Route::post('/leave', [EssLeaveController::class, 'store'])->name('leave.store');
            Route::delete('/leave/{application}', [EssLeaveController::class, 'destroy'])->name('leave.destroy');
            Route::get('/payroll', [EssPayrollController::class, 'index'])->name('payroll.index');
            Route::get('/payroll/payslips', [EssPayrollController::class, 'payslips'])->name('payroll.payslips');
            Route::get('/payroll/payslips/{payslip}', [EssPayrollController::class, 'show'])->name('payroll.show');
            Route::get('/payroll/payslips/{payslip}/download', [EssPayrollController::class, 'download'])->name('payroll.download');
        });

        Route::get('crm', CrmHomeController::class)->name('crm.home');
        Route::get('operations', OperationsHomeController::class)->name('operations.home');
        Route::get('crm/activities', CrmActivitiesController::class)->name('crm.activities');
        Route::get('crm/revenue', CrmRevenueController::class)->name('crm.revenue');
        Route::get('crm/reports', CrmReportsController::class)->name('crm.reports');
        Route::get('crm/saved-views', CrmSavedViewsController::class)->name('crm.saved-views');
        Route::get('crm/exports', CrmExportsController::class)->name('crm.exports');

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

        Route::get('tasks/board', [TaskController::class, 'board'])->name('tasks.board');
        Route::get('tasks/list', [TaskController::class, 'list'])->name('tasks.list');
        Route::get('tasks/timeline', [TaskController::class, 'timeline'])->name('tasks.timeline');
        Route::get('projects/{project}/tasks', [TaskController::class, 'projectIndex'])->name('projects.tasks.index');
        Route::resource('tasks', TaskController::class);
        Route::patch('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
        Route::post('tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
        Route::patch('tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');

        Route::get('tasks/{task}/dependencies', [TaskDependencyController::class, 'index'])->name('tasks.dependencies.index');
        Route::post('tasks/{task}/dependencies', [TaskDependencyController::class, 'store'])->name('tasks.dependencies.store');
        Route::delete('tasks/{task}/dependencies/{dependency}', [TaskDependencyController::class, 'destroy'])->name('tasks.dependencies.destroy');

        Route::get('tasks/{task}/checklists', [TaskChecklistController::class, 'index'])->name('tasks.checklists.index');
        Route::post('tasks/{task}/checklists', [TaskChecklistController::class, 'store'])->name('tasks.checklists.store');
        Route::patch('tasks/{task}/checklists/{checklist}', [TaskChecklistController::class, 'update'])->name('tasks.checklists.update');
        Route::delete('tasks/{task}/checklists/{checklist}', [TaskChecklistController::class, 'destroy'])->name('tasks.checklists.destroy');
        Route::patch('tasks/{task}/checklists/{checklist}/complete', [TaskChecklistController::class, 'complete'])->name('tasks.checklists.complete');

        Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('tasks.comments.index');
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::patch('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('tasks.comments.update');
        Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');

        Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index'])->name('tasks.attachments.index');
        Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');
        Route::get('tasks/{task}/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('tasks.attachments.download');
        Route::delete('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('tasks.attachments.destroy');

        Route::get('tasks/{task}/time-logs', [TaskTimeLogController::class, 'index'])->name('tasks.time-logs.index');
        Route::post('tasks/{task}/time-logs', [TaskTimeLogController::class, 'store'])->name('tasks.time-logs.store');
        Route::post('tasks/{task}/time-logs/start', [TaskTimeLogController::class, 'start'])->name('tasks.time-logs.start');
        Route::post('tasks/{task}/time-logs/stop', [TaskTimeLogController::class, 'stop'])->name('tasks.time-logs.stop');
        Route::delete('tasks/{task}/time-logs/{time_log}', [TaskTimeLogController::class, 'destroy'])->name('tasks.time-logs.destroy');

        Route::post('tasks/{task}/watch', [TaskWatcherController::class, 'store'])->name('tasks.watch.store');
        Route::delete('tasks/{task}/watch', [TaskWatcherController::class, 'destroy'])->name('tasks.watch.destroy');
        Route::post('tasks/{task}/recurrence', [TaskRecurrenceController::class, 'store'])->name('tasks.recurrence.store');
        Route::patch('tasks/{task}/recurrence/{recurrence}', [TaskRecurrenceController::class, 'update'])->name('tasks.recurrence.update');
        Route::delete('tasks/{task}/recurrence/{recurrence}', [TaskRecurrenceController::class, 'destroy'])->name('tasks.recurrence.destroy');
        Route::post('tasks/{task}/labels/{label}', [ProjectLabelController::class, 'attach'])->name('tasks.labels.attach');
        Route::delete('tasks/{task}/labels/{label}', [ProjectLabelController::class, 'detach'])->name('tasks.labels.detach');

        Route::resource('task-statuses', TaskStatusController::class)
            ->parameters(['task-statuses' => 'status'])
            ->except(['show']);
        Route::resource('task-priorities', TaskPriorityController::class)
            ->parameters(['task-priorities' => 'priority'])
            ->except(['show']);

        Route::get('projects/home', ProjectsHomeController::class)->name('projects.home');
        Route::get('projects/milestones', ProjectsMilestonesHubController::class)->name('projects.milestones.hub');
        Route::get('projects/budgets', ProjectsBudgetsHubController::class)->name('projects.budgets.hub');
        Route::get('projects/reports-hub', ProjectsReportsController::class)->name('projects.reports.hub');
        Route::get('projects/dashboard', [ProjectController::class, 'dashboard'])->name('projects.dashboard');
        Route::get('projects/executive', [ProjectExecutiveDashboardController::class, 'index'])->name('projects.executive');
        Route::get('projects/watching', [ProjectWatcherController::class, 'index'])->name('projects.watching');
        Route::get('projects/calendar', [ProjectCalendarController::class, 'index'])->name('projects.calendar');
        Route::get('projects/automation', [ProjectAutomationController::class, 'index'])->name('projects.automation');
        Route::post('projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
        Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
        Route::get('projects/{project}/timeline', [ProjectController::class, 'timeline'])->name('projects.timeline');
        Route::resource('projects', ProjectController::class);

        Route::get('projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members.index');
        Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
        Route::patch('projects/{project}/members/{member}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
        Route::delete('projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

        Route::get('projects/{project}/milestones', [ProjectMilestoneController::class, 'index'])->name('projects.milestones.index');
        Route::post('projects/{project}/milestones', [ProjectMilestoneController::class, 'store'])->name('projects.milestones.store');
        Route::patch('projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'update'])->name('projects.milestones.update');
        Route::delete('projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy'])->name('projects.milestones.destroy');
        Route::patch('projects/{project}/milestones/{milestone}/complete', [ProjectMilestoneController::class, 'complete'])->name('projects.milestones.complete');

        Route::get('projects/{project}/progress/dashboard', [ProjectProgressDashboardController::class, 'index'])->name('projects.progress.dashboard');
        Route::get('projects/{project}/progress', [ProjectProgressController::class, 'index'])->name('projects.progress.index');
        Route::post('projects/{project}/progress', [ProjectProgressController::class, 'store'])->name('projects.progress.store');
        Route::patch('projects/{project}/progress/{progressUpdate}', [ProjectProgressController::class, 'update'])->name('projects.progress.update');
        Route::delete('projects/{project}/progress/{progressUpdate}', [ProjectProgressController::class, 'destroy'])->name('projects.progress.destroy');
        Route::get('projects/{project}/health', [ProjectHealthController::class, 'show'])->name('projects.health.show');
        Route::get('projects/{project}/reports', [ProjectReportController::class, 'index'])->name('projects.reports.index');
        Route::post('projects/{project}/reports', [ProjectReportController::class, 'store'])->name('projects.reports.store');
        Route::get('projects/{project}/reports/{report}/download', [ProjectReportController::class, 'download'])->name('projects.reports.download');
        Route::get('projects/{project}/gantt', [ProjectGanttController::class, 'show'])->name('projects.gantt.show');
        Route::get('projects/{project}/statistics', [ProjectProgressDashboardController::class, 'statistics'])->name('projects.statistics.show');

        Route::post('projects/{project}/watch', [ProjectWatcherController::class, 'store'])->name('projects.watch.store');
        Route::delete('projects/{project}/watch', [ProjectWatcherController::class, 'destroy'])->name('projects.watch.destroy');
        Route::post('projects/{project}/save-as-template', [ProjectTemplateController::class, 'saveFromProject'])->name('projects.save-as-template');
        Route::get('projects/{project}/collaboration', [ProjectCollaborationController::class, 'show'])->name('projects.collaboration.show');
        Route::post('projects/{project}/collaboration/pins', [ProjectCollaborationController::class, 'pin'])->name('projects.collaboration.pins.store');
        Route::delete('projects/{project}/collaboration/pins/{pin}', [ProjectCollaborationController::class, 'unpin'])->name('projects.collaboration.pins.destroy');
        Route::post('projects/{project}/calendar/sync', [ProjectCalendarController::class, 'sync'])->name('projects.calendar.sync');

        Route::get('projects/{project}/risks', [ProjectRiskController::class, 'projectIndex'])->name('projects.risks.index');
        Route::post('projects/{project}/risks', [ProjectRiskController::class, 'store'])->name('projects.risks.store');
        Route::patch('projects/{project}/risks/{risk}', [ProjectRiskController::class, 'update'])->name('projects.risks.update');
        Route::delete('projects/{project}/risks/{risk}', [ProjectRiskController::class, 'destroy'])->name('projects.risks.destroy');

        Route::get('projects/{project}/issues', [ProjectIssueController::class, 'projectIndex'])->name('projects.issues.index');
        Route::post('projects/{project}/issues', [ProjectIssueController::class, 'store'])->name('projects.issues.store');
        Route::patch('projects/{project}/issues/{issue}', [ProjectIssueController::class, 'update'])->name('projects.issues.update');
        Route::delete('projects/{project}/issues/{issue}', [ProjectIssueController::class, 'destroy'])->name('projects.issues.destroy');

        Route::get('projects/{project}/baselines', [ProjectBaselineController::class, 'index'])->name('projects.baselines.index');
        Route::post('projects/{project}/baselines', [ProjectBaselineController::class, 'store'])->name('projects.baselines.store');
        Route::get('projects/{project}/baselines/{baseline}', [ProjectBaselineController::class, 'show'])->name('projects.baselines.show');

        Route::get('projects/{project}/budgets', [ProjectBudgetController::class, 'show'])->name('projects.budgets.show');
        Route::put('projects/{project}/budgets', [ProjectBudgetController::class, 'update'])->name('projects.budgets.update');
        Route::patch('projects/{project}/budgets', [ProjectBudgetController::class, 'update']);

        Route::get('projects/{project}/dependencies', [ProjectDependencyController::class, 'projectIndex'])->name('projects.dependencies.index');

        Route::resource('project-labels', ProjectLabelController::class)
            ->parameters(['project-labels' => 'label'])
            ->except(['show']);

        Route::resource('project-templates', ProjectTemplateController::class)
            ->parameters(['project-templates' => 'template']);
        Route::post('project-templates/{template}/create-project', [ProjectTemplateController::class, 'createFromTemplate'])->name('project-templates.create-project');
        Route::post('project-templates/{template}/duplicate', [ProjectTemplateController::class, 'duplicate'])->name('project-templates.duplicate');
        Route::post('project-templates/{template}/favorite', [ProjectTemplateController::class, 'favorite'])->name('project-templates.favorite');

        Route::get('portfolios/executive', [PortfolioExecutiveController::class, 'show'])->name('portfolios.executive');
        Route::get('portfolios/forecasts', [PortfolioForecastController::class, 'index'])->name('portfolios.forecasts.index');
        Route::get('portfolios/forecasts/{portfolio}', [PortfolioForecastController::class, 'show'])->name('portfolios.forecasts.show');
        Route::get('portfolios/{portfolio}/dashboard', [PortfolioController::class, 'dashboard'])->name('portfolios.dashboard');
        Route::post('portfolios/{portfolio}/archive', [PortfolioController::class, 'archive'])->name('portfolios.archive');
        Route::post('portfolios/{portfolio}/projects', [PortfolioController::class, 'attachProject'])->name('portfolios.projects.attach');
        Route::delete('portfolios/{portfolio}/projects/{project}', [PortfolioController::class, 'detachProject'])->name('portfolios.projects.detach');
        Route::resource('portfolios', PortfolioController::class);

        Route::get('programs/{program}/dashboard', [ProgramController::class, 'dashboard'])->name('programs.dashboard');
        Route::post('programs/{program}/projects', [ProgramController::class, 'attachProject'])->name('programs.projects.attach');
        Route::delete('programs/{program}/projects/{project}', [ProgramController::class, 'detachProject'])->name('programs.projects.detach');
        Route::resource('programs', ProgramController::class);

        Route::get('project-dependencies', [ProjectDependencyController::class, 'index'])->name('project-dependencies.index');
        Route::post('project-dependencies', [ProjectDependencyController::class, 'store'])->name('project-dependencies.store');
        Route::patch('project-dependencies/{dependency}', [ProjectDependencyController::class, 'update'])->name('project-dependencies.update');
        Route::delete('project-dependencies/{dependency}', [ProjectDependencyController::class, 'destroy'])->name('project-dependencies.destroy');

        Route::get('risks', [ProjectRiskController::class, 'index'])->name('risks.index');
        Route::post('risks', [ProjectRiskController::class, 'store'])->name('risks.store');
        Route::patch('risks/{risk}', [ProjectRiskController::class, 'update'])->name('risks.update');
        Route::delete('risks/{risk}', [ProjectRiskController::class, 'destroy'])->name('risks.destroy');

        Route::get('issues', [ProjectIssueController::class, 'index'])->name('issues.index');
        Route::post('issues', [ProjectIssueController::class, 'store'])->name('issues.store');
        Route::patch('issues/{issue}', [ProjectIssueController::class, 'update'])->name('issues.update');
        Route::delete('issues/{issue}', [ProjectIssueController::class, 'destroy'])->name('issues.destroy');

        Route::get('portfolio-reports', [PortfolioReportController::class, 'index'])->name('portfolio-reports.index');
        Route::post('portfolio-reports', [PortfolioReportController::class, 'store'])->name('portfolio-reports.store');
        Route::get('portfolio-reports/{report}/download', [PortfolioReportController::class, 'download'])->name('portfolio-reports.download');

        Route::get('notification-preferences', [NotificationPreferenceController::class, 'edit'])->name('notification-preferences.edit');
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
        Route::get('mentions', [ProjectMentionController::class, 'index'])->name('mentions.index');
        Route::get('mentions/autocomplete', [ProjectMentionController::class, 'autocomplete'])->name('mentions.autocomplete');

        Route::resource('project-categories', ProjectCategoryController::class)
            ->parameters(['project-categories' => 'category'])
            ->except(['show']);
        Route::resource('project-types', ProjectTypeController::class)
            ->parameters(['project-types' => 'type'])
            ->except(['show']);
        Route::resource('project-statuses', ProjectStatusController::class)
            ->parameters(['project-statuses' => 'status'])
            ->except(['show']);
        Route::resource('project-lifecycle-stages', ProjectLifecycleStageController::class)
            ->parameters(['project-lifecycle-stages' => 'stage'])
            ->except(['show']);

        Route::prefix('resources')->name('resources.')->group(function () {
            Route::get('planner', [ResourcePlannerController::class, 'planner'])->name('planner');
            Route::get('capacity', [ResourcePlannerController::class, 'capacity'])->name('capacity');
            Route::get('employees/{employee}/workload', [ResourcePlannerController::class, 'employeeWorkload'])->name('employees.workload');
            Route::get('timeline', [ResourcePlannerController::class, 'timeline'])->name('timeline');
            Route::get('forecast', [ResourcePlannerController::class, 'forecast'])->name('forecast');

            Route::resource('calendars', ResourceCalendarController::class)
                ->parameters(['calendars' => 'calendar'])
                ->except(['show']);
            Route::resource('allocations', ResourceAllocationController::class)
                ->parameters(['allocations' => 'allocation']);
        });

        Route::post('saved-filters', [SavedFilterController::class, 'store'])->name('saved-filters.store');
        Route::patch('saved-filters/{saved_filter}', [SavedFilterController::class, 'update'])->name('saved-filters.update');
        Route::delete('saved-filters/{saved_filter}', [SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');
        Route::post('saved-filters/{saved_filter}/duplicate', [SavedFilterController::class, 'duplicate'])->name('saved-filters.duplicate');

        Route::get('search', [SearchController::class, 'index'])->name('search.index');
        Route::get('knowledge/search', [KnowledgeCenterController::class, 'search'])->name('knowledge.search');
        Route::get('knowledge/health', [KnowledgeCenterController::class, 'health'])
            ->name('knowledge.health')
            ->middleware('permission:'.config('documentation.validation.health_permission', 'settings.manage'));
        Route::get('knowledge', [KnowledgeCenterController::class, 'index'])->name('knowledge.index');
        Route::get('knowledge/{module}', [KnowledgeCenterController::class, 'module'])->name('knowledge.module');
        Route::get('knowledge/{module}/{page}', [KnowledgeCenterController::class, 'page'])
            ->where('page', '.*')
            ->name('knowledge.page');
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

        Route::get('administration', AdministrationHomeController::class)->name('administration.home');
        Route::prefix('administration')->name('administration.')->group(function () {
            Route::get('modules', [AdministrationModulesController::class, 'index'])->name('modules.index');
            Route::put('modules', [AdministrationModulesController::class, 'update'])->name('modules.update');
            Route::get('security', [AdministrationSecurityController::class, 'index'])->name('security.index');
            Route::put('security', [AdministrationSecurityController::class, 'updatePolicies'])->name('security.update');
            Route::get('branding', [AdministrationBrandingController::class, 'edit'])->name('branding.edit');
            Route::put('branding', [AdministrationBrandingController::class, 'update'])->name('branding.update');
            Route::get('developer', [AdministrationDeveloperController::class, 'index'])->name('developer.index');

            Route::prefix('imports')->name('imports.')->group(function () {
                Route::get('/', [ImportCenterController::class, 'index'])->name('index');
                Route::get('history', [ImportCenterController::class, 'history'])->name('history');
                Route::get('sessions/{session}', [ImportCenterController::class, 'show'])->name('show');
                Route::get('sessions/{session}/preview', [ImportCenterController::class, 'preview'])->name('preview');
                Route::post('sessions/{session}/map', [ImportCenterController::class, 'map'])->name('map');
                Route::post('sessions/{session}/execute', [ImportCenterController::class, 'execute'])->name('execute');
                Route::get('sessions/{session}/errors', [ImportCenterController::class, 'errors'])->name('errors');
                Route::get('{entity}/create', [ImportCenterController::class, 'create'])->name('create');
                Route::get('{entity}/template/{format}', [ImportCenterController::class, 'downloadTemplate'])
                    ->whereIn('format', ['csv', 'xlsx'])
                    ->name('template');
                Route::post('{entity}', [ImportCenterController::class, 'store'])->name('store');
            });

            Route::prefix('bulk')->name('bulk.')->group(function () {
                Route::get('/', [BulkOperationsController::class, 'index'])->name('index');
                Route::get('history', [BulkOperationsController::class, 'history'])->name('history');
                Route::post('/', [BulkOperationsController::class, 'store'])->name('store');
                Route::get('operations/{operation}', [BulkOperationsController::class, 'show'])->name('show');
                Route::get('operations/{operation}/errors', [BulkOperationsController::class, 'errors'])->name('errors');
            });

            Route::prefix('exports')->name('exports.')->group(function () {
                Route::get('/', [ExportCenterController::class, 'index'])->name('index');
                Route::get('history', [ExportCenterController::class, 'history'])->name('history');
                Route::get('{entity}/create', [ExportCenterController::class, 'create'])->name('create');
                Route::post('/', [ExportCenterController::class, 'store'])->name('store');
                Route::get('sessions/{session}', [ExportCenterController::class, 'show'])->name('show');
                Route::get('sessions/{session}/download', [ExportCenterController::class, 'download'])->name('download');
                Route::post('sessions/{session}/revoke', [ExportCenterController::class, 'revoke'])->name('revoke');
                Route::post('sessions/{session}/regenerate', [ExportCenterController::class, 'regenerate'])->name('regenerate');
                Route::delete('sessions/{session}', [ExportCenterController::class, 'destroy'])->name('destroy');
            });
        });

        Route::get('marketing', MarketingHomeController::class)->name('marketing.home');
        Route::prefix('marketing')->name('marketing.')->group(function () {
            Route::get('attribution', [MarketingAttributionController::class, 'index'])->name('attribution.index');
            Route::get('providers', [MarketingProvidersController::class, 'index'])->name('providers.index');
            Route::get('campaigns', [MarketingCampaignController::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/create', [MarketingCampaignController::class, 'create'])->name('campaigns.create');
            Route::post('campaigns', [MarketingCampaignController::class, 'store'])->name('campaigns.store');
            Route::get('campaigns/{campaign}', [MarketingCampaignController::class, 'show'])->name('campaigns.show');
            Route::get('campaigns/{campaign}/edit', [MarketingCampaignController::class, 'edit'])->name('campaigns.edit');
            Route::put('campaigns/{campaign}', [MarketingCampaignController::class, 'update'])->name('campaigns.update');
            Route::delete('campaigns/{campaign}', [MarketingCampaignController::class, 'destroy'])->name('campaigns.destroy');
        });

        Route::get('analytics', AnalyticsHomeController::class)->name('analytics.home');
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('executive', [AnalyticsPagesController::class, 'executive'])->name('executive');
            Route::get('crm', [AnalyticsPagesController::class, 'crm'])->name('crm');
            Route::get('projects', [AnalyticsPagesController::class, 'projects'])->name('projects');
            Route::get('hr', [AnalyticsPagesController::class, 'hr'])->name('hr');
            Route::get('ai-insights', [AnalyticsPagesController::class, 'aiInsights'])->name('ai-insights');
            Route::get('dashboards', [AnalyticsPagesController::class, 'dashboards'])->name('dashboards.index');
            Route::get('kpis', [AnalyticsPagesController::class, 'kpis'])->name('kpis.index');
            Route::get('reports', [AnalyticsPagesController::class, 'reports'])->name('reports.index');
        });

        Route::patch('workspace/dashboard-preferences', [WorkspaceDashboardPreferenceController::class, 'update'])
            ->name('workspace.dashboard-preferences.update');

        Route::get('organization/settings', [OrganizationController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('organization.edit');
        Route::patch('organization/settings', [OrganizationController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('organization.update');
        Route::post('organization/settings/test-mail', [OrganizationController::class, 'sendTestMail'])
            ->middleware('permission:settings.manage')
            ->name('organization.test-mail');

        Route::get('organization/settings/hub', OrganizationSettingsHubController::class)
            ->middleware('permission:settings.manage')
            ->name('organization.settings.hub');

        Route::prefix('organization/settings')->name('organization.settings.')->group(function () {
            Route::get('subscription', [HrConfigurationController::class, 'subscription'])->name('subscription');
            Route::get('billing', [HrConfigurationController::class, 'billing'])->name('billing');

            Route::get('working-days', [HrConfigurationController::class, 'editWorkingDays'])->name('working-days.edit');
            Route::put('working-days', [HrConfigurationController::class, 'updateWorkingDays'])->name('working-days.update');
            Route::get('attendance-rules', [HrConfigurationController::class, 'editAttendanceRules'])->name('attendance-rules.edit');
            Route::put('attendance-rules', [HrConfigurationController::class, 'updateAttendanceRules'])->name('attendance-rules.update');
            Route::get('leave-policies', [HrConfigurationController::class, 'editLeavePolicies'])->name('leave-policies.edit');
            Route::put('leave-policies', [HrConfigurationController::class, 'updateLeavePolicies'])->name('leave-policies.update');
            Route::get('leave-approvers', [HrConfigurationController::class, 'editLeaveApprovers'])->name('leave-approvers.edit');
            Route::put('leave-approvers', [HrConfigurationController::class, 'updateLeaveApprovers'])->name('leave-approvers.update');
            Route::get('notifications', [HrConfigurationController::class, 'editNotifications'])->name('notifications.edit');
            Route::put('notifications', [HrConfigurationController::class, 'updateNotifications'])->name('notifications.update');

            // Aliases into existing HR controllers (operational services stay shared; nav lives under settings).
            Route::redirect('branches', '/hrms/branches')->name('branches.index');
            Route::redirect('departments', '/hrms/departments')->name('departments.index');
            Route::redirect('designations', '/hrms/designations')->name('designations.index');
            Route::redirect('shifts', '/hrms/shifts')->name('shifts.index');
            Route::redirect('holidays', '/hrms/holidays')->name('holidays.index');
            Route::redirect('leave-types', '/hrms/leave-types')->name('leave-types.index');
        });

        Route::post('organization/switch/{organization}', OrganizationSwitchController::class)->name('organization.switch');

        require __DIR__.'/rbac.php';

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
