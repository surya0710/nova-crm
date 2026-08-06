<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hrms\AttendanceResource;
use App\Http\Resources\Hrms\EmployeeResource;
use App\Http\Resources\Hrms\LeaveResource;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');
        $payload = $this->facade->managerDashboard($manager, $request->user());
        $dashboard = $payload['dashboard'];

        return ApiResponse::success([
            'notification_count' => $payload['notification_count'],
            'team_count' => $dashboard['teamCount'] ?? 0,
            'team_present_today' => $dashboard['teamPresentToday'] ?? 0,
            'pending_leave' => collect($dashboard['pendingLeave'] ?? [])->map(
                fn ($a) => (new LeaveResource($a))->resolve()
            )->values()->all(),
            'on_leave_today' => collect($dashboard['onLeaveToday'] ?? [])->map(
                fn ($a) => (new LeaveResource($a))->resolve()
            )->values()->all(),
            'team_attendance' => $dashboard['teamSummary'] ?? $this->facade->teamAttendanceSummary($manager),
        ]);
    }

    public function teamAttendance(Request $request): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');
        $teamIds = Employee::query()
            ->where('reporting_manager_id', $manager->id)
            ->pluck('id');

        $query = AttendanceRecord::query()
            ->whereIn('employee_id', $teamIds)
            ->with(['employee', 'shift']);

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->input('date'));
        } else {
            $query->whereDate('attendance_date', now()->toDateString());
        }

        $paginator = $query->orderBy('employee_id')->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (AttendanceRecord $r) => (new AttendanceResource($r))->resolve(),
            meta: ['summary' => $this->facade->teamAttendanceSummary($manager)],
        );
    }

    public function pendingLeave(Request $request): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');
        $teamIds = Employee::query()
            ->where('reporting_manager_id', $manager->id)
            ->pluck('id');

        $paginator = LeaveApplication::query()
            ->whereIn('employee_id', $teamIds)
            ->where('status', 'pending')
            ->with(['employee', 'leaveType'])
            ->latest('submitted_at')
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (LeaveApplication $a) => (new LeaveResource($a))->resolve(),
        );
    }

    public function approveLeave(Request $request, LeaveApplication $application): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');
        $application->loadMissing('employee');
        abort_unless($this->essContext->managesEmployee($manager, $application->employee), 404);
        $this->authorize('approve', $application);

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);
        $updated = $this->facade->leaveService()->approveLeave(
            $application,
            $request->user(),
            $data['remarks'] ?? null,
        );

        return ApiResponse::success(
            new LeaveResource($updated->load(['employee', 'leaveType'])),
            __('Leave approved.'),
        );
    }

    public function rejectLeave(Request $request, LeaveApplication $application): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');
        $application->loadMissing('employee');
        abort_unless($this->essContext->managesEmployee($manager, $application->employee), 404);
        $this->authorize('approve', $application);

        $data = $request->validate([
            'remarks' => ['required', 'string', 'max:1000'],
        ]);
        $updated = $this->facade->leaveService()->rejectLeave(
            $application,
            $request->user(),
            $data['remarks'],
        );

        return ApiResponse::success(
            new LeaveResource($updated->load(['employee', 'leaveType'])),
            __('Leave rejected.'),
        );
    }

    public function directory(Request $request): JsonResponse
    {
        $manager = $this->essContext->requireEmployee($request->user(), 'manager');

        $filters = $request->only(['q', 'department_id', 'designation_id', 'branch_id', 'team_id']);
        // Scope to direct reports by default unless broader directory permission is present.
        $paginator = Employee::query()
            ->with(['department', 'designation', 'branch', 'reportingManager'])
            ->where('reporting_manager_id', $manager->id)
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->input('q'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (Employee $e) => (new EmployeeResource($e))->resolve(),
            meta: ['filters' => $filters],
        );
    }
}
