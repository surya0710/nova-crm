<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApprovePayrollRunRequest;
use App\Http\Requests\Hrms\CreatePayrollRunRequest;
use App\Http\Requests\Hrms\PreviewPayrollRequest;
use App\Http\Requests\Hrms\PublishPayrollRunRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function __construct(
        protected PayrollCalculationService $service,
        protected PayrollPublicationService $publicationService,
    ) {
        $this->authorizeResource(PayrollRun::class, 'run');
    }

    public function index(): View
    {
        return view('hrms.payroll.runs.index', [
            'runs' => PayrollRun::query()
                ->with(['period', 'triggeredBy'])
                ->latest()
                ->paginate(20),
            'periods' => PayrollPeriod::query()
                ->whereIn('status', ['draft', 'open'])
                ->orderByDesc('start_date')
                ->get(),
        ]);
    }

    public function store(CreatePayrollRunRequest $request): RedirectResponse
    {
        $period = PayrollPeriod::query()->findOrFail($request->validated('payroll_period_id'));
        $run = $this->service->createRun($period, $request->user());

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-run-created');
    }

    public function show(PayrollRun $run): View
    {
        $run->load(['period', 'triggeredBy', 'results.employee', 'validationErrors.employee', 'approvals.approvedBy', 'publication.publishedBy']);

        return view('hrms.payroll.runs.show', [
            'run' => $run,
            'approvals' => $this->publicationService->approvalsForRun($run),
            'publication' => $this->publicationService->publicationForRun($run),
        ]);
    }

    public function approve(ApprovePayrollRunRequest $request, PayrollRun $run): RedirectResponse
    {
        $this->publicationService->approveRun($run, $request->user(), $request->validated());

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-run-approved');
    }

    public function publish(PublishPayrollRunRequest $request, PayrollRun $run): RedirectResponse
    {
        $this->publicationService->publishRun($run, $request->user(), [
            'send_emails' => $request->boolean('send_emails', true),
        ]);

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-run-published');
    }

    public function calculate(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('calculate', $run);
        $this->service->calculateRun($run, $request->user());

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-run-calculated');
    }

    public function recalculate(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('recalculate', $run);
        $this->service->recalculateRun($run, $request->user());

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-run-recalculated');
    }

    public function previewForm(): View
    {
        $this->authorize('create', PayrollRun::class);

        return view('hrms.payroll.preview', [
            'periods' => PayrollPeriod::query()
                ->whereIn('status', ['draft', 'open'])
                ->orderByDesc('start_date')
                ->get(),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(),
            'preview' => null,
        ]);
    }

    public function preview(PreviewPayrollRequest $request): View
    {
        $period = PayrollPeriod::query()->findOrFail($request->validated('payroll_period_id'));
        $employeeId = $request->validated('employee_id');

        if ($employeeId) {
            $employee = Employee::query()->findOrFail($employeeId);
            $preview = [
                'mode' => 'employee',
                'result' => $this->service->previewEmployee($employee, $period),
            ];
        } else {
            $preview = [
                'mode' => 'period',
                'result' => $this->service->previewPeriod($period),
            ];
        }

        return view('hrms.payroll.preview', [
            'periods' => PayrollPeriod::query()
                ->whereIn('status', ['draft', 'open'])
                ->orderByDesc('start_date')
                ->get(),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(),
            'preview' => $preview,
            'selectedPeriodId' => $period->id,
            'selectedEmployeeId' => $employeeId,
        ]);
    }
}
