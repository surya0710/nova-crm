<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Hrms\EmployeeTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeTimelineController extends Controller
{
    public function show(Request $request, Employee $employee, EmployeeTimelineService $timelineService): View
    {
        $this->authorize('view', $employee);

        return view('hrms.employees.timeline', [
            'employee' => $employee->load(['department', 'designation']),
            'timeline' => $timelineService->timelineForEmployee($employee),
        ]);
    }
}
