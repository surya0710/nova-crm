<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssApplyLeaveRequest;
use App\Http\Resources\Hrms\LeaveResource;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
    ) {}

    public function balances(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $balances = $this->facade->leaveService()->getBalancesForEmployee(
            $employee,
            $request->filled('year') ? $request->integer('year') : null,
        );

        return ApiResponse::success(
            $balances->loadMissing('leaveType')->map(fn ($balance) => [
                'id' => $balance->id,
                'leave_type_id' => $balance->leave_type_id,
                'leave_type' => [
                    'id' => $balance->leaveType?->id,
                    'name' => $balance->leaveType?->name,
                    'code' => $balance->leaveType?->code,
                ],
                'year' => $balance->year,
                'entitled' => $balance->entitled,
                'used' => $balance->used,
                'pending' => $balance->pending,
                'balance' => $balance->balance,
            ])->values()->all()
        );
    }

    public function types(Request $request): JsonResponse
    {
        $this->essContext->requireEmployee($request->user());

        $types = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_paid', 'requires_approval', 'allow_half_day', 'max_days_per_year']);

        return ApiResponse::success($types);
    }

    public function history(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', LeaveApplication::class);

        $query = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->with('leaveType');

        ApiQuery::applyFilters($query, $request, [
            'status' => 'status',
            'leave_type_id' => 'leave_type_id',
        ]);

        $paginator = $query->latest('submitted_at')->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (LeaveApplication $app) => (new LeaveResource($app))->resolve(),
        );
    }

    public function store(EssApplyLeaveRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $application = $this->facade->leaveService()->applyLeave(
            $employee,
            $request->validated(),
            $request->user(),
            true,
        );

        return ApiResponse::success(
            new LeaveResource($application->load('leaveType')),
            __('Leave applied.'),
            status: 201,
        );
    }

    public function cancel(Request $request, LeaveApplication $application): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $application->employee_id === (int) $employee->id, 404);
        $this->authorize('withdrawOwn', $application);

        $updated = $this->facade->leaveService()->withdrawLeave($application, $request->user());

        return ApiResponse::success(
            new LeaveResource($updated->load('leaveType')),
            __('Leave cancelled.'),
        );
    }
}
