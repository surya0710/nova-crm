<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\MarkPayrollPaidRequest;
use App\Http\Requests\Hrms\StorePayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayrollAdjustment;
use App\Models\PayrollBankExport;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\Hrms\PayrollAdjustmentService;
use App\Services\Hrms\PayrollEnterpriseDashboardService;
use App\Services\Hrms\PayrollPublicationService;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollApiController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService,
        protected PayrollAdjustmentService $adjustmentService,
        protected PayrollPublicationService $publicationService,
        protected PayrollEnterpriseDashboardService $dashboardService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payroll.view'), 403);

        return response()->json(['data' => $this->dashboardService->widgets()]);
    }

    public function runs(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payroll.view'), 403);

        $runs = PayrollRun::query()
            ->with('period')
            ->latest()
            ->paginate(min(100, max(1, (int) $request->input('per_page', 20))));

        return response()->json(['data' => $runs]);
    }

    public function showRun(Request $request, PayrollRun $run): JsonResponse
    {
        $this->authorize('view', $run);

        return response()->json([
            'data' => $run->load(['period', 'results.employee', 'approvals', 'publication', 'paidBy']),
        ]);
    }

    public function assignments(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payroll.view'), 403);

        $query = EmployeeSalaryAssignment::query()->with(['employee', 'salaryStructure'])->latest('effective_from');
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function revisions(Request $request, Employee $employee): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payroll.view'), 403);

        $history = $this->payrollService->salaryRevisionHistory($employee);

        return response()->json([
            'data' => [
                'employee_id' => $employee->id,
                'history' => $history,
                'comparison' => $history->count() >= 2
                    ? $this->payrollService->compareSalaryAssignments($history->first(), $history->skip(1)->first())
                    : null,
            ],
        ]);
    }

    public function adjustments(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('payroll.view')
            || $request->user()?->hasPermission('payroll.adjustment.manage'),
            403
        );

        return response()->json([
            'data' => PayrollAdjustment::query()
                ->with(['employee', 'payrollPeriod'])
                ->latest()
                ->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function storeAdjustment(StorePayrollAdjustmentRequest $request): JsonResponse
    {
        $adjustment = $this->adjustmentService->create($request->validated(), $request->user());

        return response()->json(['message' => __('Adjustment created.'), 'data' => $adjustment], 201);
    }

    public function payslips(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('payslip.view')
            || $request->user()?->hasPermission('payroll.view'),
            403
        );

        return response()->json([
            'data' => Payslip::query()
                ->with(['employee', 'payrollRun.period'])
                ->latest()
                ->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function bankExports(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('payroll.bank.export')
            || $request->user()?->hasPermission('payroll.finance.view'),
            403
        );

        return response()->json([
            'data' => PayrollBankExport::query()
                ->with('payrollRun.period')
                ->latest()
                ->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function markPaid(MarkPayrollPaidRequest $request, PayrollRun $run): JsonResponse
    {
        $paid = $this->publicationService->markPaid($run, $request->user(), $request->validated());

        return response()->json(['message' => __('Payroll marked as paid.'), 'data' => $paid]);
    }
}
