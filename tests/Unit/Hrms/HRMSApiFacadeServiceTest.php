<?php

namespace Tests\Unit\Hrms;

use App\Models\Employee;
use App\Models\User;
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\AttendanceDashboardService;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\EmployeeDirectoryService;
use App\Services\Hrms\EmployeeDocumentService;
use App\Services\Hrms\EmployeeProfileService;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Services\Hrms\HrmsDashboardService;
use App\Services\Hrms\LeaveService;
use App\Services\Hrms\MobileNotificationInboxService;
use App\Services\Hrms\PayrollEnterpriseDashboardService;
use App\Services\Hrms\TaxDashboardService;
use App\Services\Hrms\TaxFacadeService;
use App\Services\Recruitment\RecruitmentApiService;
use App\Services\TenantContext;
use Mockery;
use Tests\TestCase;

class HRMSApiFacadeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_employee_dashboard_composes_service_outputs_without_extra_logic(): void
    {
        $employee = new Employee(['id' => 1]);
        $user = new User(['id' => 2]);

        $dashboard = Mockery::mock(HrmsDashboardService::class);
        $dashboard->shouldReceive('employeeDashboard')->once()->with($employee)->andReturn([
            'employee' => $employee,
            'leaveBalances' => [],
        ]);

        $profiles = Mockery::mock(EmployeeProfileService::class);
        $profiles->shouldReceive('profileCompletion')->once()->with($employee)->andReturn([
            'percentage' => 40,
            'sections' => [],
        ]);

        $notifications = Mockery::mock(MobileNotificationInboxService::class);
        $notifications->shouldReceive('unreadCount')->once()->with($user)->andReturn(3);

        $facade = $this->makeFacade(
            dashboard: $dashboard,
            profiles: $profiles,
            notificationInbox: $notifications,
        );

        $payload = $facade->employeeDashboard($employee, $user);

        $this->assertSame(3, $payload['notification_count']);
        $this->assertSame(40, $payload['profile_completion']['percentage']);
        $this->assertSame($employee, $payload['dashboard']['employee']);
    }

    public function test_manager_and_hr_dashboards_delegate_to_hrms_dashboard_service(): void
    {
        $manager = new Employee(['id' => 10]);
        $user = new User(['id' => 11]);

        $dashboard = Mockery::mock(HrmsDashboardService::class);
        $dashboard->shouldReceive('managerDashboard')->once()->with($manager)->andReturn(['teamCount' => 4]);
        $dashboard->shouldReceive('hrDashboard')->once()->andReturn(['employeeCount' => 20]);

        $notifications = Mockery::mock(MobileNotificationInboxService::class);
        $notifications->shouldReceive('unreadCount')->twice()->andReturn(1);

        $facade = $this->makeFacade(dashboard: $dashboard, notificationInbox: $notifications);

        $this->assertSame(4, $facade->managerDashboard($manager, $user)['dashboard']['teamCount']);
        $this->assertSame(20, $facade->hrDashboard($user)['dashboard']['employeeCount']);
    }

    protected function makeFacade(
        ?HrmsDashboardService $dashboard = null,
        ?EmployeeProfileService $profiles = null,
        ?MobileNotificationInboxService $notificationInbox = null,
    ): HRMSApiFacadeService {
        return new HRMSApiFacadeService(
            $dashboard ?? Mockery::mock(HrmsDashboardService::class),
            Mockery::mock(AttendanceDashboardService::class),
            Mockery::mock(AttendanceCalendarService::class),
            Mockery::mock(AttendanceService::class),
            Mockery::mock(LeaveService::class),
            $profiles ?? Mockery::mock(EmployeeProfileService::class),
            Mockery::mock(EmployeeDirectoryService::class),
            Mockery::mock(EmployeeDocumentService::class),
            Mockery::mock(PayrollEnterpriseDashboardService::class),
            Mockery::mock(TaxFacadeService::class),
            Mockery::mock(TaxDashboardService::class),
            Mockery::mock(RecruitmentApiService::class),
            Mockery::mock(EssContext::class),
            $notificationInbox ?? Mockery::mock(MobileNotificationInboxService::class),
            Mockery::mock(TenantContext::class),
        );
    }
}
