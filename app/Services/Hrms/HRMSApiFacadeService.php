<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\User;
use App\Services\Recruitment\RecruitmentApiService;
use App\Services\TenantContext;

/**
 * Mobile API orchestration only. No calculations, persistence, SQL, or permissions.
 */
class HRMSApiFacadeService
{
    public function __construct(
        protected HrmsDashboardService $dashboard,
        protected AttendanceDashboardService $attendanceDashboard,
        protected AttendanceCalendarService $attendanceCalendar,
        protected AttendanceService $attendance,
        protected LeaveService $leave,
        protected EmployeeProfileService $profiles,
        protected EmployeeDirectoryService $directory,
        protected EmployeeDocumentService $documents,
        protected PayrollEnterpriseDashboardService $payrollDashboard,
        protected TaxFacadeService $tax,
        protected TaxDashboardService $taxDashboard,
        protected RecruitmentApiService $recruitment,
        protected EssContext $ess,
        protected MobileNotificationInboxService $notificationInbox,
        protected TenantContext $tenant,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function employeeDashboard(Employee $employee, User $user): array
    {
        return [
            'dashboard' => $this->dashboard->employeeDashboard($employee),
            'notification_count' => $this->notificationInbox->unreadCount($user),
            'profile_completion' => $this->profiles->profileCompletion($employee),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function managerDashboard(Employee $manager, User $user): array
    {
        return [
            'dashboard' => $this->dashboard->managerDashboard($manager),
            'notification_count' => $this->notificationInbox->unreadCount($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hrDashboard(User $user): array
    {
        return [
            'dashboard' => $this->dashboard->hrDashboard(),
            'notification_count' => $this->notificationInbox->unreadCount($user),
        ];
    }

    public function notifications(): MobileNotificationInboxService
    {
        return $this->notificationInbox;
    }

    public function attendanceSummary(Employee $employee): array
    {
        return $this->attendanceDashboard->employeeSummary($employee);
    }

    public function teamAttendanceSummary(Employee $manager): array
    {
        return $this->attendanceDashboard->teamSummary($manager);
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollWidgets(): array
    {
        return $this->payrollDashboard->widgets();
    }

    /**
     * @return array<string, mixed>
     */
    public function taxWidgets(): array
    {
        return $this->taxDashboard->widgets();
    }

    public function taxFacade(): TaxFacadeService
    {
        return $this->tax;
    }

    public function leaveService(): LeaveService
    {
        return $this->leave;
    }

    public function attendanceService(): AttendanceService
    {
        return $this->attendance;
    }

    public function attendanceCalendar(): AttendanceCalendarService
    {
        return $this->attendanceCalendar;
    }

    public function documents(): EmployeeDocumentService
    {
        return $this->documents;
    }

    public function profiles(): EmployeeProfileService
    {
        return $this->profiles;
    }

    public function directory(): EmployeeDirectoryService
    {
        return $this->directory;
    }

    public function recruitment(): RecruitmentApiService
    {
        return $this->recruitment;
    }

    public function ess(): EssContext
    {
        return $this->ess;
    }
}
