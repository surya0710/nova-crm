<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CancelExitProcessRequest;
use App\Http\Requests\Hrms\StartExitProcessRequest;
use App\Http\Requests\Hrms\UpdateExitProcessRequest;
use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Services\Hrms\EmployeeExitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeExitController extends Controller
{
    public function __construct(protected EmployeeExitService $service)
    {
        $this->authorizeResource(EmployeeExitProcess::class, 'exitProcess');
    }

    public function index(Request $request): View
    {
        $query = EmployeeExitProcess::query()->with('employee')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('hrms.exit-processes.index', [
            'exitProcesses' => $query->paginate(15)->withQueryString(),
            'employees' => Employee::query()->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))->orderBy('first_name')->get(),
            'exitTypes' => config('hrms.exit_types', []),
            'statuses' => config('hrms.exit_process_statuses', []),
        ]);
    }

    public function show(EmployeeExitProcess $exitProcess): View
    {
        $exitProcess->load(['employee.department', 'employee.designation', 'initiatedByUser']);

        return view('hrms.exit-processes.show', [
            'exitProcess' => $exitProcess,
        ]);
    }

    public function store(StartExitProcessRequest $request): RedirectResponse
    {
        $exitProcess = $this->service->start($request->employee(), $request->validated(), $request->user());

        return redirect()->route('hrms.exit-processes.show', $exitProcess)->with('status', 'hrms-exit-started');
    }

    public function update(UpdateExitProcessRequest $request, EmployeeExitProcess $exitProcess): RedirectResponse
    {
        $this->service->update($exitProcess, $request->validated(), $request->user());

        return redirect()->route('hrms.exit-processes.show', $exitProcess)->with('status', 'hrms-exit-updated');
    }

    public function complete(Request $request, EmployeeExitProcess $exitProcess): RedirectResponse
    {
        $this->authorize('update', $exitProcess);
        $this->service->complete($exitProcess, $request->user());

        return redirect()->route('hrms.exit-processes.show', $exitProcess)->with('status', 'hrms-exit-completed');
    }

    public function cancel(CancelExitProcessRequest $request, EmployeeExitProcess $exitProcess): RedirectResponse
    {
        $this->service->cancel($exitProcess, $request->validated(), $request->user());

        return redirect()->route('hrms.exit-processes.index')->with('status', 'hrms-exit-cancelled');
    }
}
