<?php

namespace App\Providers;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementDeleted;
use App\Events\AnnouncementUpdated;
use App\Events\ApplicationSubmitted;
use App\Events\ApplicationWithdrawn;
use App\Events\AppraisalCalibrated;
use App\Events\AppraisalClosed;
use App\Events\AppraisalGenerated;
use App\Events\AppraisalSessionCreated;
use App\Events\AppraisalSubmitted;
use App\Events\AssetAssigned;
use App\Events\AssetLost;
use App\Events\AssetReturned;
use App\Events\AttendanceClockedIn;
use App\Events\AttendanceClockedOut;
use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionSubmitted;
use App\Events\AttendanceOvertimeRecorded;
use App\Events\CandidateCreated;
use App\Events\CandidateLoggedIn;
use App\Events\CandidateProfileUpdated;
use App\Events\CandidateRegistered;
use App\Events\CandidateRecommended;
use App\Events\HiringApproved;
use App\Events\OfferAccepted;
use App\Events\OfferApproved;
use App\Events\OfferExpired;
use App\Events\OfferGenerated;
use App\Events\OfferRejected;
use App\Events\OfferSent;
use App\Events\OverallocationDetected;
use App\Events\ChecklistCompleted;
use App\Events\CommentAdded;
use App\Events\CompensationRecommended;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Events\DependencyCreated;
use App\Events\DependencyRemoved;
use App\Events\CapacityExceeded;
use App\Events\EmployeeCreated;
use App\Events\EmployeeDepartmentChanged;
use App\Events\EmployeeDocumentDeleted;
use App\Events\EmployeeDocumentExpiring;
use App\Events\EmployeeDocumentUpdated;
use App\Events\EmployeeDocumentUploaded;
use App\Events\EmployeeDocumentVerified;
use App\Events\EmployeeExitCancelled;
use App\Events\EmployeeExitCompleted;
use App\Events\EmployeeExited;
use App\Events\EmployeeExitStarted;
use App\Events\EmployeeLoanClosed;
use App\Events\EmployeeLoanCreated;
use App\Events\EmployeeManagerChanged;
use App\Events\EmployeeProfileUpdated;
use App\Events\EmployeeSalaryAssigned;
use App\Events\EmployeeSettlementCompleted;
use App\Events\EvaluationSubmitted;
use App\Events\FeedbackCampaignCreated;
use App\Events\FeedbackClosed;
use App\Events\FeedbackRequestSent;
use App\Events\FeedbackStarted;
use App\Events\FeedbackSubmitted;
use App\Events\GoalAssigned;
use App\Events\GoalCancelled;
use App\Events\GoalCompleted;
use App\Events\GoalCreated;
use App\Events\GoalProgressUpdated;
use App\Events\InterviewCancelled;
use App\Events\InterviewCompleted;
use App\Events\InterviewScheduled;
use App\Events\InvoiceCreated;
use App\Events\JobApplied;
use App\Events\JobOpeningPublished;
use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Events\LeaveApproved;
use App\Events\LeaveBalanceAdjusted;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveSubmitted;
use App\Events\MilestoneCompleted;
use App\Events\MilestoneDelayed;
use App\Events\ProgressUpdated;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Events\PaymentReceived;
use App\Events\PayrollApproved;
use App\Events\PayrollBankExported;
use App\Events\PayrollComplianceFailed;
use App\Events\PayrollEmployeeCalculated;
use App\Events\PayrollLedgerGenerated;
use App\Events\PayrollPeriodCreated;
use App\Events\PayrollPeriodLocked;
use App\Events\PayrollPublished;
use App\Events\PayrollReversed;
use App\Events\PayrollRunCompleted;
use App\Events\PayrollRunStarted;
use App\Events\PayrollStatutoryCalculated;
use App\Events\PayrollValidationFailed;
use App\Events\PayslipEmailed;
use App\Events\PayslipGenerated;
use App\Events\PerformanceConfigurationUpdated;
use App\Events\PerformanceCycleActivated;
use App\Events\PerformanceCycleCreated;
use App\Events\PerformanceReviewAssigned;
use App\Events\PerformanceReviewClosed;
use App\Events\PerformanceReviewReviewed;
use App\Events\PerformanceReviewStarted;
use App\Events\PerformanceReviewSubmitted;
use App\Events\PerformanceTemplateCreated;
use App\Events\ProjectArchived;
use App\Events\ProjectCreated;
use App\Events\ProjectCompleted;
use App\Events\ProjectDelayed;
use App\Events\ProjectHealthChanged;
use App\Events\ProjectLifecycleChanged;
use App\Events\ProjectMemberAssigned;
use App\Events\ProjectMemberRemoved;
use App\Events\ProjectMilestoneCompleted;
use App\Events\ProjectMilestoneCreated;
use App\Events\ProjectRestored;
use App\Events\ProjectUpdated;
use App\Events\PromotionRecommended;
use App\Events\ReportGenerated;
use App\Events\ResumeUploaded;
use App\Events\ResourceAllocated;
use App\Events\ResourceAllocationUpdated;
use App\Events\ResourceReleased;
use App\Events\SalaryStructureCreated;
use App\Events\SalaryStructureUpdated;
use App\Events\StatutoryProfileUpdated;
use App\Events\StatutoryRuleChanged;
use App\Events\TaskArchived;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskReassigned;
use App\Events\TaskRestored;
use App\Events\TaskStarted;
use App\Events\TaskUpdated;
use App\Events\TimelineUpdated;
use App\Events\TimeLogged;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\AppraisalCalibration;
use App\Models\AppraisalSession;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\EmployeeAsset;
use App\Models\EmployeeDocument;
use App\Models\EmployeeExitProcess;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryAssignment;
use App\Models\EmployeeSettlement;
use App\Models\EmployeeStatutoryProfile;
use App\Models\EvaluationTemplate;
use App\Models\ExpenseReimbursement;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackRequest;
use App\Models\FeedbackTemplate;
use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use App\Models\HiringDecision;
use App\Models\RecruitmentSavedReport;
use App\Models\Holiday;
use App\Models\HrmsAnnouncement;
use App\Models\HrmsShift;
use App\Models\HrmsTeam;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\Kpi;
use App\Models\Lead;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\MetadataFieldDefinition;
use App\Models\OfferApproval;
use App\Models\OfferLetter;
use App\Models\OfferNegotiation;
use App\Models\OfferTemplate;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionTemplate;
use App\Models\Payment;
use App\Models\PayrollBankExport;
use App\Models\PayrollConfiguration;
use App\Models\PayrollJournal;
use App\Models\PayrollLedgerEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollReversal;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceReview;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewTemplate;
use App\Models\Product;
use App\Models\NotificationPreference;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\ProjectBudget;
use App\Models\ProjectCategory;
use App\Models\ProjectDependency;
use App\Models\ProjectIssue;
use App\Models\ProjectLabel;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectRisk;
use App\Models\ProjectStatus;
use App\Models\ProjectTemplate;
use App\Models\ProjectType;
use App\Models\ResourceAllocation;
use App\Models\ResourceCalendar;
use App\Models\Role;
use App\Models\Quotation;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\SavedFilter;
use App\Models\StatutoryComplianceError;
use App\Models\StatutoryRuleSet;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\TaskPriority;
use App\Models\TaskRecurrence;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Models\WorkloadSnapshot;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Policies\AppraisalCalibrationPolicy;
use App\Policies\AppraisalSessionPolicy;
use App\Policies\AttendanceCorrectionPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\BranchPolicy;
use App\Policies\CompetencyCategoryPolicy;
use App\Policies\CompetencyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DesignationPolicy;
use App\Policies\EmployeeAppraisalPolicy;
use App\Policies\EmployeeAssetPolicy;
use App\Policies\EmployeeDocumentPolicy;
use App\Policies\EmployeeExitProcessPolicy;
use App\Policies\EmployeeLoanPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EmployeeSalaryAssignmentPolicy;
use App\Policies\EmployeeSettlementPolicy;
use App\Policies\EmployeeStatutoryProfilePolicy;
use App\Policies\ExpenseReimbursementPolicy;
use App\Policies\FeedbackCampaignPolicy;
use App\Policies\FeedbackRequestPolicy;
use App\Policies\FeedbackTemplatePolicy;
use App\Policies\GoalCategoryPolicy;
use App\Policies\GoalPolicy;
use App\Policies\GoalTemplatePolicy;
use App\Policies\HiringDecisionPolicy;
use App\Policies\RecruitmentSavedReportPolicy;
use App\Policies\HolidayPolicy;
use App\Policies\HrmsAnnouncementPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CandidateEvaluationPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\JobOpeningPolicy;
use App\Policies\JobRequisitionPolicy;
use App\Policies\EvaluationTemplatePolicy;
use App\Policies\InterviewRoundPolicy;
use App\Policies\InterviewStagePolicy;
use App\Policies\KpiPolicy;
use App\Policies\LeadPolicy;
use App\Policies\LeavePolicy;
use App\Policies\LeaveTypePolicy;
use App\Policies\MetadataFieldDefinitionPolicy;
use App\Policies\OfferApprovalPolicy;
use App\Policies\OfferLetterPolicy;
use App\Policies\OfferNegotiationPolicy;
use App\Policies\OfferTemplatePolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PayrollBankExportPolicy;
use App\Policies\PayrollConfigurationPolicy;
use App\Policies\PayrollJournalPolicy;
use App\Policies\PayrollLedgerEntryPolicy;
use App\Policies\PayrollPeriodPolicy;
use App\Policies\PayrollResultPolicy;
use App\Policies\PayrollReversalPolicy;
use App\Policies\PayrollRunPolicy;
use App\Policies\PayslipPolicy;
use App\Policies\PerformanceConfigurationPolicy;
use App\Policies\PerformanceCyclePolicy;
use App\Policies\PerformanceRatingScalePolicy;
use App\Policies\PerformanceReviewAssignmentPolicy;
use App\Policies\PerformanceReviewPolicy;
use App\Policies\PerformanceReviewTemplatePolicy;
use App\Policies\ProductPolicy;
use App\Policies\NotificationPreferencePolicy;
use App\Policies\PortfolioPolicy;
use App\Policies\PortfolioReportPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\ProjectBaselinePolicy;
use App\Policies\ProjectBudgetPolicy;
use App\Policies\ProjectCategoryPolicy;
use App\Policies\ProjectDependencyPolicy;
use App\Policies\ProjectIssuePolicy;
use App\Policies\ProjectLabelPolicy;
use App\Policies\ProjectLifecycleStagePolicy;
use App\Policies\ProjectMemberPolicy;
use App\Policies\ProjectMilestonePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ProjectRiskPolicy;
use App\Policies\ProjectStatusPolicy;
use App\Policies\ProjectTemplatePolicy;
use App\Policies\ProjectTypePolicy;
use App\Policies\QuotationPolicy;
use App\Policies\RbacPolicy;
use App\Policies\ResourceAllocationPolicy;
use App\Policies\ResourceCalendarPolicy;
use App\Policies\SalaryAdvancePolicy;
use App\Policies\SalaryComponentPolicy;
use App\Policies\SalaryStructurePolicy;
use App\Policies\SavedFilterPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\StatutoryComplianceErrorPolicy;
use App\Policies\StatutoryRuleSetPolicy;
use App\Policies\TaskAttachmentPolicy;
use App\Policies\TaskChecklistPolicy;
use App\Policies\TaskCommentPolicy;
use App\Policies\TaskDependencyPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskPriorityPolicy;
use App\Policies\TaskRecurrencePolicy;
use App\Policies\TaskStatusPolicy;
use App\Policies\TaskTimeLogPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkloadPolicy;
use App\Policies\WorkflowExecutionPolicy;
use App\Policies\WorkflowPolicy;
use App\Services\Assignment\AssignmentStrategyRegistry;
use App\Services\CommandPalette\CommandPaletteRegistry;
use App\Services\CommandPalette\AdminCommandProvider;
use App\Services\CommandPalette\AnalyticsCommandProvider;
use App\Services\CommandPalette\CrmCommandProvider;
use App\Services\CommandPalette\HrmsCommandProvider;
use App\Services\CommandPalette\MarketingCommandProvider;
use App\Services\CommandPalette\NavigationCommandProvider;
use App\Services\CommandPalette\ProjectsCommandProvider;
use App\Services\CommandPalette\ThemeCommandProvider;
use App\Services\Import\Adapters\CustomerImportAdapter;
use App\Services\Import\Adapters\LeadImportAdapter;
use App\Services\Import\ImportEntityRegistry;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\Search\AdminBranchSearchProvider;
use App\Services\Search\AdminDepartmentSearchProvider;
use App\Services\Search\AdminIntegrationSearchProvider;
use App\Services\Search\AdminRoleSearchProvider;
use App\Services\Search\AdminSettingsSearchProvider;
use App\Services\Search\AdminTemplateSearchProvider;
use App\Services\Search\AdminUserSearchProvider;
use App\Services\Search\AnalyticsKpiSearchProvider;
use App\Services\Search\AnalyticsViewSearchProvider;
use App\Services\Search\CrmActivitySearchProvider;
use App\Services\Search\CrmCustomerSearchProvider;
use App\Services\Search\CrmLeadSearchProvider;
use App\Services\Search\CrmOpportunitySearchProvider;
use App\Services\Search\CrmRevenueSearchProvider;
use App\Services\Search\CrmSavedViewSearchProvider;
use App\Services\Search\HrmsAssetSearchProvider;
use App\Services\Search\HrmsAttendanceSearchProvider;
use App\Services\Search\HrmsCandidateSearchProvider;
use App\Services\Search\HrmsDocumentSearchProvider;
use App\Services\Search\HrmsEmployeeSearchProvider;
use App\Services\Search\HrmsJobOpeningSearchProvider;
use App\Services\Search\HrmsLeaveSearchProvider;
use App\Services\Search\HrmsPerformanceReviewSearchProvider;
use App\Services\Search\LegacySearchProvider;
use App\Services\Search\MarketingCampaignSearchProvider;
use App\Services\Search\MarketingProviderSearchProvider;
use App\Services\Search\ProjectsIssueSearchProvider;
use App\Services\Search\ProjectsMilestoneSearchProvider;
use App\Services\Search\ProjectsPortfolioSearchProvider;
use App\Services\Search\ProjectsProgramSearchProvider;
use App\Services\Search\ProjectsProjectSearchProvider;
use App\Services\Search\ProjectsRiskSearchProvider;
use App\Services\Search\ProjectsTaskSearchProvider;
use App\Services\Search\SearchProviderRegistry;
use App\Services\TenantContext;
use App\View\Composers\ShellComposer;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Lead::class => LeadPolicy::class,
        MetadataFieldDefinition::class => MetadataFieldDefinitionPolicy::class,
        Customer::class => CustomerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Opportunity::class => OpportunityPolicy::class,
        Payment::class => PaymentPolicy::class,
        Product::class => ProductPolicy::class,
        Project::class => ProjectPolicy::class,
        ProjectCategory::class => ProjectCategoryPolicy::class,
        ProjectType::class => ProjectTypePolicy::class,
        ProjectStatus::class => ProjectStatusPolicy::class,
        ProjectLifecycleStage::class => ProjectLifecycleStagePolicy::class,
        ProjectMember::class => ProjectMemberPolicy::class,
        ProjectMilestone::class => ProjectMilestonePolicy::class,
        ProjectLabel::class => ProjectLabelPolicy::class,
        ProjectTemplate::class => ProjectTemplatePolicy::class,
        TaskRecurrence::class => TaskRecurrencePolicy::class,
        NotificationPreference::class => NotificationPreferencePolicy::class,
        Portfolio::class => PortfolioPolicy::class,
        Program::class => ProgramPolicy::class,
        ProjectDependency::class => ProjectDependencyPolicy::class,
        ProjectRisk::class => ProjectRiskPolicy::class,
        ProjectIssue::class => ProjectIssuePolicy::class,
        ProjectBaseline::class => ProjectBaselinePolicy::class,
        ProjectBudget::class => ProjectBudgetPolicy::class,
        PortfolioReport::class => PortfolioReportPolicy::class,
        Quotation::class => QuotationPolicy::class,
        SavedFilter::class => SavedFilterPolicy::class,
        Task::class => TaskPolicy::class,
        TaskStatus::class => TaskStatusPolicy::class,
        TaskPriority::class => TaskPriorityPolicy::class,
        TaskDependency::class => TaskDependencyPolicy::class,
        TaskChecklist::class => TaskChecklistPolicy::class,
        TaskComment::class => TaskCommentPolicy::class,
        TaskAttachment::class => TaskAttachmentPolicy::class,
        TaskTimeLog::class => TaskTimeLogPolicy::class,
        User::class => UserPolicy::class,
        Workflow::class => WorkflowPolicy::class,
        WorkflowExecution::class => WorkflowExecutionPolicy::class,
        Employee::class => EmployeePolicy::class,
        Branch::class => BranchPolicy::class,
        Department::class => DepartmentPolicy::class,
        Designation::class => DesignationPolicy::class,
        HrmsTeam::class => TeamPolicy::class,
        AttendanceRecord::class => AttendancePolicy::class,
        AttendanceCorrection::class => AttendanceCorrectionPolicy::class,
        HrmsShift::class => ShiftPolicy::class,
        LeaveApplication::class => LeavePolicy::class,
        LeaveType::class => LeaveTypePolicy::class,
        Holiday::class => HolidayPolicy::class,
        HrmsAnnouncement::class => HrmsAnnouncementPolicy::class,
        EmployeeDocument::class => EmployeeDocumentPolicy::class,
        SalaryComponent::class => SalaryComponentPolicy::class,
        SalaryStructure::class => SalaryStructurePolicy::class,
        EmployeeSalaryAssignment::class => EmployeeSalaryAssignmentPolicy::class,
        PayrollPeriod::class => PayrollPeriodPolicy::class,
        PayrollConfiguration::class => PayrollConfigurationPolicy::class,
        PayrollRun::class => PayrollRunPolicy::class,
        PayrollResult::class => PayrollResultPolicy::class,
        Payslip::class => PayslipPolicy::class,
        EmployeeStatutoryProfile::class => EmployeeStatutoryProfilePolicy::class,
        StatutoryRuleSet::class => StatutoryRuleSetPolicy::class,
        StatutoryComplianceError::class => StatutoryComplianceErrorPolicy::class,
        PayrollLedgerEntry::class => PayrollLedgerEntryPolicy::class,
        PayrollJournal::class => PayrollJournalPolicy::class,
        PayrollBankExport::class => PayrollBankExportPolicy::class,
        EmployeeLoan::class => EmployeeLoanPolicy::class,
        SalaryAdvance::class => SalaryAdvancePolicy::class,
        ExpenseReimbursement::class => ExpenseReimbursementPolicy::class,
        EmployeeSettlement::class => EmployeeSettlementPolicy::class,
        PayrollReversal::class => PayrollReversalPolicy::class,
        PerformanceConfiguration::class => PerformanceConfigurationPolicy::class,
        PerformanceRatingScale::class => PerformanceRatingScalePolicy::class,
        CompetencyCategory::class => CompetencyCategoryPolicy::class,
        Competency::class => CompetencyPolicy::class,
        PerformanceCycle::class => PerformanceCyclePolicy::class,
        PerformanceReviewTemplate::class => PerformanceReviewTemplatePolicy::class,
        PerformanceReviewAssignment::class => PerformanceReviewAssignmentPolicy::class,
        PerformanceReview::class => PerformanceReviewPolicy::class,
        FeedbackCampaign::class => FeedbackCampaignPolicy::class,
        FeedbackRequest::class => FeedbackRequestPolicy::class,
        FeedbackTemplate::class => FeedbackTemplatePolicy::class,
        AppraisalSession::class => AppraisalSessionPolicy::class,
        EmployeeAppraisal::class => EmployeeAppraisalPolicy::class,
        AppraisalCalibration::class => AppraisalCalibrationPolicy::class,
        PermissionGroup::class => RbacPolicy::class,
        Permission::class => RbacPolicy::class,
        Role::class => RbacPolicy::class,
        PermissionTemplate::class => RbacPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(\App\Services\Rbac\AuthorizationService::class);
        $this->app->singleton(WorkflowRuntimeContext::class);

        $this->app->singleton(ImportEntityRegistry::class);

        $this->app->singleton(MarketingProviderRegistry::class, function ($app) {
            $registry = new MarketingProviderRegistry;

            foreach (config('marketing.providers.drivers', []) as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });

        $this->app->singleton(\App\Services\Recruitment\Providers\RecruitmentProviderRegistry::class, function ($app) {
            $registry = new \App\Services\Recruitment\Providers\RecruitmentProviderRegistry;

            foreach (config('recruitment.providers.drivers', []) as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });

        $this->app->singleton(AssignmentStrategyRegistry::class, function ($app) {
            $registry = new AssignmentStrategyRegistry;

            foreach (config('assignment.strategies', []) as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });

        $this->app->singleton(CommandPaletteRegistry::class, function ($app) {
            $registry = new CommandPaletteRegistry;
            $registry->register($app->make(NavigationCommandProvider::class));
            $registry->register($app->make(ThemeCommandProvider::class));
            $registry->register($app->make(CrmCommandProvider::class));
            $registry->register($app->make(ProjectsCommandProvider::class));
            $registry->register($app->make(HrmsCommandProvider::class));
            $registry->register($app->make(AdminCommandProvider::class));
            $registry->register($app->make(MarketingCommandProvider::class));
            $registry->register($app->make(AnalyticsCommandProvider::class));

            return $registry;
        });

        $this->app->singleton(SearchProviderRegistry::class, function ($app) {
            $registry = new SearchProviderRegistry;
            $registry->register($app->make(LegacySearchProvider::class));
            $registry->register($app->make(CrmLeadSearchProvider::class));
            $registry->register($app->make(CrmCustomerSearchProvider::class));
            $registry->register($app->make(CrmOpportunitySearchProvider::class));
            $registry->register($app->make(CrmRevenueSearchProvider::class));
            $registry->register($app->make(CrmSavedViewSearchProvider::class));
            $registry->register($app->make(CrmActivitySearchProvider::class));
            $registry->register($app->make(ProjectsProjectSearchProvider::class));
            $registry->register($app->make(ProjectsTaskSearchProvider::class));
            $registry->register($app->make(ProjectsPortfolioSearchProvider::class));
            $registry->register($app->make(ProjectsProgramSearchProvider::class));
            $registry->register($app->make(ProjectsRiskSearchProvider::class));
            $registry->register($app->make(ProjectsIssueSearchProvider::class));
            $registry->register($app->make(ProjectsMilestoneSearchProvider::class));
            $registry->register($app->make(HrmsEmployeeSearchProvider::class));
            $registry->register($app->make(HrmsCandidateSearchProvider::class));
            $registry->register($app->make(HrmsJobOpeningSearchProvider::class));
            $registry->register($app->make(HrmsLeaveSearchProvider::class));
            $registry->register($app->make(HrmsAttendanceSearchProvider::class));
            $registry->register($app->make(HrmsAssetSearchProvider::class));
            $registry->register($app->make(HrmsDocumentSearchProvider::class));
            $registry->register($app->make(HrmsPerformanceReviewSearchProvider::class));
            $registry->register($app->make(AdminUserSearchProvider::class));
            $registry->register($app->make(AdminDepartmentSearchProvider::class));
            $registry->register($app->make(AdminBranchSearchProvider::class));
            $registry->register($app->make(AdminRoleSearchProvider::class));
            $registry->register($app->make(AdminSettingsSearchProvider::class));
            $registry->register($app->make(AdminIntegrationSearchProvider::class));
            $registry->register($app->make(AdminTemplateSearchProvider::class));
            $registry->register($app->make(MarketingCampaignSearchProvider::class));
            $registry->register($app->make(MarketingProviderSearchProvider::class));
            $registry->register($app->make(AnalyticsViewSearchProvider::class));
            $registry->register($app->make(AnalyticsKpiSearchProvider::class));

            return $registry;
        });
    }

    public function boot(): void
    {
        require_once app_path('helpers.php');

        View::composer([
            'layouts.app',
            'components.nav.sidebar',
            'components.shell.header',
        ], ShellComposer::class);

        Event::listen([
            LeadCreated::class,
            LeadUpdated::class,
            LeadAssigned::class,
            LeadConverted::class,
            CustomerCreated::class,
            CustomerUpdated::class,
            OpportunityCreated::class,
            OpportunityStageChanged::class,
            InvoiceCreated::class,
            PaymentReceived::class,
            MarketingLeadImported::class,
            EmployeeCreated::class,
            EmployeeUpdated::class,
            EmployeeProfileUpdated::class,
            EmployeeExited::class,
            EmployeeManagerChanged::class,
            EmployeeDepartmentChanged::class,
            EmployeeDocumentUploaded::class,
            EmployeeDocumentUpdated::class,
            EmployeeDocumentDeleted::class,
            EmployeeDocumentVerified::class,
            EmployeeDocumentExpiring::class,
            AttendanceClockedIn::class,
            AttendanceClockedOut::class,
            AttendanceCorrectionSubmitted::class,
            AttendanceCorrectionApproved::class,
            AttendanceCorrectionRejected::class,
            AttendanceOvertimeRecorded::class,
            LeaveSubmitted::class,
            LeaveApproved::class,
            LeaveRejected::class,
            LeaveCancelled::class,
            LeaveBalanceAdjusted::class,
            AnnouncementCreated::class,
            AnnouncementUpdated::class,
            AnnouncementDeleted::class,
            AssetAssigned::class,
            AssetReturned::class,
            AssetLost::class,
            EmployeeExitStarted::class,
            EmployeeExitCompleted::class,
            EmployeeExitCancelled::class,
            SalaryStructureCreated::class,
            SalaryStructureUpdated::class,
            EmployeeSalaryAssigned::class,
            PayrollPeriodCreated::class,
            PayrollPeriodLocked::class,
            PayrollRunStarted::class,
            PayrollRunCompleted::class,
            PayrollEmployeeCalculated::class,
            PayrollValidationFailed::class,
            StatutoryProfileUpdated::class,
            StatutoryRuleChanged::class,
            PayrollStatutoryCalculated::class,
            PayrollComplianceFailed::class,
            PayrollApproved::class,
            PayrollPublished::class,
            PayslipGenerated::class,
            PayslipEmailed::class,
            PayrollLedgerGenerated::class,
            PayrollBankExported::class,
            EmployeeLoanCreated::class,
            EmployeeLoanClosed::class,
            EmployeeSettlementCompleted::class,
            PayrollReversed::class,
            PerformanceCycleCreated::class,
            PerformanceCycleActivated::class,
            PerformanceTemplateCreated::class,
            PerformanceConfigurationUpdated::class,
            GoalCreated::class,
            GoalAssigned::class,
            GoalProgressUpdated::class,
            GoalCompleted::class,
            GoalCancelled::class,
            PerformanceReviewAssigned::class,
            PerformanceReviewStarted::class,
            PerformanceReviewSubmitted::class,
            PerformanceReviewReviewed::class,
            PerformanceReviewClosed::class,
            FeedbackCampaignCreated::class,
            FeedbackRequestSent::class,
            FeedbackStarted::class,
            FeedbackSubmitted::class,
            FeedbackClosed::class,
            AppraisalSessionCreated::class,
            AppraisalGenerated::class,
            AppraisalSubmitted::class,
            AppraisalCalibrated::class,
            AppraisalClosed::class,
            PromotionRecommended::class,
            CompensationRecommended::class,
            RequisitionApproved::class,
            JobOpeningPublished::class,
            CandidateCreated::class,
            ApplicationSubmitted::class,
            ApplicationWithdrawn::class,
            CandidateRegistered::class,
            CandidateLoggedIn::class,
            CandidateProfileUpdated::class,
            ResumeUploaded::class,
            JobApplied::class,
            InterviewScheduled::class,
            InterviewCancelled::class,
            InterviewCompleted::class,
            EvaluationSubmitted::class,
            CandidateRecommended::class,
            OfferGenerated::class,
            OfferApproved::class,
            OfferSent::class,
            OfferAccepted::class,
            OfferRejected::class,
            OfferExpired::class,
            HiringApproved::class,
            ProjectCreated::class,
            ProjectUpdated::class,
            ProjectArchived::class,
            ProjectRestored::class,
            ProjectMemberAssigned::class,
            ProjectMemberRemoved::class,
            ProjectMilestoneCreated::class,
            ProjectMilestoneCompleted::class,
            MilestoneCompleted::class,
            MilestoneDelayed::class,
            ProjectHealthChanged::class,
            ProgressUpdated::class,
            ProjectCompleted::class,
            ProjectDelayed::class,
            TimelineUpdated::class,
            ReportGenerated::class,
            ProjectLifecycleChanged::class,
            TaskCreated::class,
            TaskUpdated::class,
            TaskAssigned::class,
            TaskReassigned::class,
            TaskStarted::class,
            TaskCompleted::class,
            TaskArchived::class,
            TaskRestored::class,
            DependencyCreated::class,
            DependencyRemoved::class,
            ChecklistCompleted::class,
            CommentAdded::class,
            TimeLogged::class,
            ResourceAllocated::class,
            ResourceAllocationUpdated::class,
            ResourceReleased::class,
            CapacityExceeded::class,
            OverallocationDetected::class,
        ], RunTriggeredWorkflows::class);

        Event::listen(
            \App\Listeners\DispatchRecruitmentOutboundIntegrations::subscribedEvents(),
            \App\Listeners\DispatchRecruitmentOutboundIntegrations::class,
        );

        $this->app->make(ImportEntityRegistry::class)
            ->register($this->app->make(LeadImportAdapter::class));
        $this->app->make(ImportEntityRegistry::class)
            ->register($this->app->make(CustomerImportAdapter::class));

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(MetadataFieldDefinition::class, MetadataFieldDefinitionPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectCategory::class, ProjectCategoryPolicy::class);
        Gate::policy(ProjectType::class, ProjectTypePolicy::class);
        Gate::policy(ProjectStatus::class, ProjectStatusPolicy::class);
        Gate::policy(ProjectLifecycleStage::class, ProjectLifecycleStagePolicy::class);
        Gate::policy(ProjectMember::class, ProjectMemberPolicy::class);
        Gate::policy(ProjectMilestone::class, ProjectMilestonePolicy::class);
        Gate::policy(ProjectLabel::class, ProjectLabelPolicy::class);
        Gate::policy(ProjectTemplate::class, ProjectTemplatePolicy::class);
        Gate::policy(TaskRecurrence::class, TaskRecurrencePolicy::class);
        Gate::policy(NotificationPreference::class, NotificationPreferencePolicy::class);
        Gate::policy(Portfolio::class, PortfolioPolicy::class);
        Gate::policy(Program::class, ProgramPolicy::class);
        Gate::policy(ProjectDependency::class, ProjectDependencyPolicy::class);
        Gate::policy(ProjectRisk::class, ProjectRiskPolicy::class);
        Gate::policy(ProjectIssue::class, ProjectIssuePolicy::class);
        Gate::policy(ProjectBaseline::class, ProjectBaselinePolicy::class);
        Gate::policy(ProjectBudget::class, ProjectBudgetPolicy::class);
        Gate::policy(PortfolioReport::class, PortfolioReportPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(SavedFilter::class, SavedFilterPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskStatus::class, TaskStatusPolicy::class);
        Gate::policy(TaskPriority::class, TaskPriorityPolicy::class);
        Gate::policy(TaskDependency::class, TaskDependencyPolicy::class);
        Gate::policy(TaskChecklist::class, TaskChecklistPolicy::class);
        Gate::policy(TaskComment::class, TaskCommentPolicy::class);
        Gate::policy(TaskAttachment::class, TaskAttachmentPolicy::class);
        Gate::policy(TaskTimeLog::class, TaskTimeLogPolicy::class);
        Gate::policy(ResourceCalendar::class, ResourceCalendarPolicy::class);
        Gate::policy(ResourceAllocation::class, ResourceAllocationPolicy::class);
        Gate::policy(WorkloadSnapshot::class, WorkloadPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowExecution::class, WorkflowExecutionPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Designation::class, DesignationPolicy::class);
        Gate::policy(HrmsTeam::class, TeamPolicy::class);
        Gate::policy(AttendanceRecord::class, AttendancePolicy::class);
        Gate::policy(AttendanceCorrection::class, AttendanceCorrectionPolicy::class);
        Gate::policy(HrmsShift::class, ShiftPolicy::class);
        Gate::policy(LeaveApplication::class, LeavePolicy::class);
        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(Holiday::class, HolidayPolicy::class);
        Gate::policy(HrmsAnnouncement::class, HrmsAnnouncementPolicy::class);
        Gate::policy(EmployeeDocument::class, EmployeeDocumentPolicy::class);
        Gate::policy(EmployeeAsset::class, EmployeeAssetPolicy::class);
        Gate::policy(EmployeeExitProcess::class, EmployeeExitProcessPolicy::class);
        Gate::policy(SalaryComponent::class, SalaryComponentPolicy::class);
        Gate::policy(SalaryStructure::class, SalaryStructurePolicy::class);
        Gate::policy(EmployeeSalaryAssignment::class, EmployeeSalaryAssignmentPolicy::class);
        Gate::policy(PayrollPeriod::class, PayrollPeriodPolicy::class);
        Gate::policy(PayrollConfiguration::class, PayrollConfigurationPolicy::class);
        Gate::policy(PayrollRun::class, PayrollRunPolicy::class);
        Gate::policy(PayrollResult::class, PayrollResultPolicy::class);
        Gate::policy(Payslip::class, PayslipPolicy::class);
        Gate::policy(EmployeeStatutoryProfile::class, EmployeeStatutoryProfilePolicy::class);
        Gate::policy(StatutoryRuleSet::class, StatutoryRuleSetPolicy::class);
        Gate::policy(StatutoryComplianceError::class, StatutoryComplianceErrorPolicy::class);
        Gate::policy(PayrollLedgerEntry::class, PayrollLedgerEntryPolicy::class);
        Gate::policy(PayrollJournal::class, PayrollJournalPolicy::class);
        Gate::policy(PayrollBankExport::class, PayrollBankExportPolicy::class);
        Gate::policy(EmployeeLoan::class, EmployeeLoanPolicy::class);
        Gate::policy(SalaryAdvance::class, SalaryAdvancePolicy::class);
        Gate::policy(ExpenseReimbursement::class, ExpenseReimbursementPolicy::class);
        Gate::policy(EmployeeSettlement::class, EmployeeSettlementPolicy::class);
        Gate::policy(PayrollReversal::class, PayrollReversalPolicy::class);
        Gate::policy(PerformanceConfiguration::class, PerformanceConfigurationPolicy::class);
        Gate::policy(PerformanceRatingScale::class, PerformanceRatingScalePolicy::class);
        Gate::policy(CompetencyCategory::class, CompetencyCategoryPolicy::class);
        Gate::policy(Competency::class, CompetencyPolicy::class);
        Gate::policy(PerformanceCycle::class, PerformanceCyclePolicy::class);
        Gate::policy(PerformanceReviewTemplate::class, PerformanceReviewTemplatePolicy::class);
        Gate::policy(GoalCategory::class, GoalCategoryPolicy::class);
        Gate::policy(GoalTemplate::class, GoalTemplatePolicy::class);
        Gate::policy(Kpi::class, KpiPolicy::class);
        Gate::policy(Goal::class, GoalPolicy::class);
        Gate::policy(PerformanceReviewAssignment::class, PerformanceReviewAssignmentPolicy::class);
        Gate::policy(PerformanceReview::class, PerformanceReviewPolicy::class);
        Gate::policy(FeedbackCampaign::class, FeedbackCampaignPolicy::class);
        Gate::policy(FeedbackRequest::class, FeedbackRequestPolicy::class);
        Gate::policy(FeedbackTemplate::class, FeedbackTemplatePolicy::class);
        Gate::policy(AppraisalSession::class, AppraisalSessionPolicy::class);
        Gate::policy(EmployeeAppraisal::class, EmployeeAppraisalPolicy::class);
        Gate::policy(AppraisalCalibration::class, AppraisalCalibrationPolicy::class);
        Gate::policy(JobRequisition::class, JobRequisitionPolicy::class);
        Gate::policy(JobOpening::class, JobOpeningPolicy::class);
        Gate::policy(Candidate::class, CandidatePolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(InterviewStage::class, InterviewStagePolicy::class);
        Gate::policy(InterviewRound::class, InterviewRoundPolicy::class);
        Gate::policy(EvaluationTemplate::class, EvaluationTemplatePolicy::class);
        Gate::policy(CandidateEvaluation::class, CandidateEvaluationPolicy::class);
        Gate::policy(OfferTemplate::class, OfferTemplatePolicy::class);
        Gate::policy(OfferLetter::class, OfferLetterPolicy::class);
        Gate::policy(OfferApproval::class, OfferApprovalPolicy::class);
        Gate::policy(OfferNegotiation::class, OfferNegotiationPolicy::class);
        Gate::policy(HiringDecision::class, HiringDecisionPolicy::class);
        Gate::policy(RecruitmentSavedReport::class, RecruitmentSavedReportPolicy::class);
        Gate::policy(PermissionGroup::class, RbacPolicy::class);
        Gate::policy(Permission::class, RbacPolicy::class);
        Gate::policy(Role::class, RbacPolicy::class);
        Gate::policy(PermissionTemplate::class, RbacPolicy::class);

        foreach (\App\Services\Recruitment\RecruitmentAnalyticsCache::observedModels() as $modelClass) {
            $modelClass::observe(\App\Observers\RecruitmentAnalyticsCacheObserver::class);
        }

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->is_super_admin) {
                return true;
            }

            return null;
        });

        Gate::define('permission', function (User $user, string $permission) {
            return $user->hasPermission($permission);
        });

        RateLimiter::for('api', function (Request $request) {
            $tokenId = $request->user()?->currentAccessToken()?->id;

            return Limit::perMinute(120)->by($tokenId ?? $request->ip());
        });

        RateLimiter::for('api-lead-intake', function (Request $request) {
            $tokenId = $request->user()?->currentAccessToken()?->id;

            return Limit::perMinute(60)->by($tokenId ?? $request->ip());
        });

        RateLimiter::for('marketing-tracking', function (Request $request) {
            return Limit::perMinute((int) config('marketing.tracking.rate_limit_per_minute'))
                ->by($request->ip());
        });

        RateLimiter::for('marketing-webhooks', function (Request $request) {
            return Limit::perMinute((int) config('marketing.providers.webhook_rate_limit_per_minute', 120))
                ->by($request->ip());
        });

        RateLimiter::for('candidate-auth', function (Request $request) {
            $organization = $request->route('organization');
            $orgKey = is_object($organization) ? $organization->id : (string) $organization;

            return Limit::perMinute(10)->by('candidate-auth|'.$orgKey.'|'.$request->ip());
        });

        RateLimiter::for('careers-apply', function (Request $request) {
            $organization = $request->route('organization');
            $orgKey = is_object($organization) ? $organization->id : (string) $organization;

            return Limit::perMinute(5)->by('careers-apply|'.$orgKey.'|'.$request->ip());
        });
    }
}
