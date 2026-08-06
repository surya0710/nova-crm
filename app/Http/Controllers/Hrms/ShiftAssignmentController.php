<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AssignShiftRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Services\Hrms\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShiftAssignmentController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', HrmsShift::class);

        return view('hrms.shift-assignments.index', [
            'assignments' => EmployeeShiftAssignment::query()
                ->with(['employee', 'shift'])
                ->latest('effective_from')
                ->paginate(15),
            'employees' => Employee::query()->orderBy('first_name')->get(),
            'shifts' => HrmsShift::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(AssignShiftRequest $request): RedirectResponse
    {
        $this->service->assignShift($request->employee(), $request->validated(), $request->user());

        return redirect()->route('hrms.shift-assignments.index')->with('status', 'hrms-shift-assigned');
    }
}
