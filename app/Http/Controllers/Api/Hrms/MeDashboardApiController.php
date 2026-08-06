<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeDashboardApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $payload = $this->facade->employeeDashboard($employee, $request->user());

        return ApiResponse::success([
            'notification_count' => $payload['notification_count'],
            'profile_completion' => $payload['profile_completion'],
            'attendance' => $this->serializeAttendance($payload['dashboard']['attendance'] ?? []),
            'leave_balances' => collect($payload['dashboard']['leaveBalances'] ?? [])->map(fn ($b) => [
                'id' => $b->id,
                'leave_type_id' => $b->leave_type_id,
                'leave_type' => $b->leaveType?->name,
                'balance' => $b->balance,
                'used' => $b->used,
                'entitled' => $b->entitled,
                'pending' => $b->pending,
                'year' => $b->year,
            ])->values()->all(),
            'pending_leave' => collect($payload['dashboard']['pendingLeave'] ?? [])->map(fn ($a) => [
                'id' => $a->id,
                'leave_type' => $a->leaveType?->name,
                'start_date' => $a->start_date?->toDateString(),
                'end_date' => $a->end_date?->toDateString(),
                'days' => $a->days,
                'status' => $a->status,
            ])->values()->all(),
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'department' => $employee->department?->name,
                'designation' => $employee->designation?->name,
                'branch' => $employee->branch?->name,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    protected function serializeAttendance(array $summary): array
    {
        return [
            'date' => $summary['date'] ?? null,
            'state' => $summary['state'] ?? null,
            'state_label' => $summary['state_label'] ?? null,
            'working_hours' => $summary['working_hours'] ?? null,
            'shift_info' => $summary['shift_info'] ?? null,
            'indicator' => $summary['indicator'] ?? null,
            'actions' => $summary['actions'] ?? null,
            'on_leave_today' => $summary['on_leave_today'] ?? false,
        ];
    }
}
