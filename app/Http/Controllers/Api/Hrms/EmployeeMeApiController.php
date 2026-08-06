<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\UpdateEmployeeProfileRequest;
use App\Http\Resources\Hrms\EmployeeResource;
use App\Services\Hrms\EmployeeService;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected EmployeeService $employeeService,
        protected HRMSApiFacadeService $facade,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewOwn', $employee);

        $employee->load([
            'department',
            'designation',
            'branch',
            'reportingManager',
            'emergencyContacts',
        ]);

        return ApiResponse::success([
            'employee' => new EmployeeResource($employee),
            'profile_completion' => $this->facade->profiles()->profileCompletion($employee),
        ]);
    }

    public function update(UpdateEmployeeProfileRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $updated = $this->employeeService->updateOwnProfile($employee, $request->validated(), $request->user());
        $updated->load([
            'department',
            'designation',
            'branch',
            'reportingManager',
            'emergencyContacts',
        ]);

        return ApiResponse::success(
            new EmployeeResource($updated),
            __('Profile updated.'),
        );
    }
}
