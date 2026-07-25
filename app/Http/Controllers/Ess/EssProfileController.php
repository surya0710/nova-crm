<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\UpdateEmployeeProfileRequest;
use App\Services\Hrms\EmployeeService;
use App\Services\Hrms\EssContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EssProfileController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected EmployeeService $employeeService,
    ) {}

    public function show(): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewOwn', $employee);

        $employee->load(['department', 'designation', 'reportingManager', 'emergencyContacts']);

        return view('ess.profile', ['employee' => $employee]);
    }

    public function update(UpdateEmployeeProfileRequest $request): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->employeeService->updateOwnProfile($employee, $request->validated(), $request->user());

        return redirect()->route('ess.profile')->with('status', 'ess-profile-updated');
    }
}
