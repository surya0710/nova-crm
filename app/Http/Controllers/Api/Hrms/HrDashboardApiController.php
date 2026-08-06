<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hrms\EmployeeResource;
use App\Models\Employee;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrDashboardApiController extends Controller
{
    public function __construct(protected HRMSApiFacadeService $facade) {}

    public function dashboard(Request $request): JsonResponse
    {
        $payload = $this->facade->hrDashboard($request->user());
        $dashboard = $payload['dashboard'];

        return ApiResponse::success([
            'notification_count' => $payload['notification_count'],
            'stats' => $this->extractStats($dashboard),
            'dashboard' => $this->sanitizeDashboard($dashboard),
        ]);
    }

    public function directory(Request $request): JsonResponse
    {
        $paginator = $this->facade->directory()->search(
            $request->only(['q', 'department_id', 'designation_id', 'branch_id', 'team_id']),
            ApiQuery::perPage($request),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (Employee $e) => (new EmployeeResource($e))->resolve(),
            meta: [
                'filter_options' => $this->facade->directory()->filterOptions(),
            ],
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $payload = $this->facade->hrDashboard($request->user());
        $dashboard = $payload['dashboard'];

        return ApiResponse::success([
            'employee_count' => $dashboard['employeeCount'] ?? 0,
            'active_employees' => $dashboard['activeEmployees'] ?? 0,
            'new_joiners' => $dashboard['newJoiners'] ?? 0,
            'on_leave_today' => $dashboard['onLeaveToday'] ?? 0,
            'attendance_summary' => $dashboard['attendanceSummary'] ?? null,
            'leave_stats' => $dashboard['leaveStats'] ?? null,
            'asset_stats' => $dashboard['assetStats'] ?? null,
            'exit_stats' => $dashboard['exitStats'] ?? null,
            'payroll_widgets' => $this->facade->payrollWidgets(),
            'tax_widgets' => $this->facade->taxWidgets(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    protected function extractStats(array $dashboard): array
    {
        return [
            'employee_count' => $dashboard['employeeCount'] ?? 0,
            'active_employees' => $dashboard['activeEmployees'] ?? 0,
            'new_joiners' => $dashboard['newJoiners'] ?? 0,
            'on_leave_today' => $dashboard['onLeaveToday'] ?? 0,
            'pending_leave_approvals' => is_countable($dashboard['pendingLeaveApprovals'] ?? null)
                ? count($dashboard['pendingLeaveApprovals'])
                : 0,
            'pending_corrections' => is_countable($dashboard['pendingCorrections'] ?? null)
                ? count($dashboard['pendingCorrections'])
                : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    protected function sanitizeDashboard(array $dashboard): array
    {
        unset($dashboard['engine_version'], $dashboard['calculation_dump'], $dashboard['internal']);

        return $dashboard;
    }
}
