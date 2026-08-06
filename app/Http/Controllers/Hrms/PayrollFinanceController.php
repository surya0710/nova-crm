<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApproveExpenseReimbursementRequest;
use App\Http\Requests\Hrms\ApproveSalaryAdvanceRequest;
use App\Http\Requests\Hrms\CloseEmployeeLoanRequest;
use App\Http\Requests\Hrms\CreateEmployeeLoanRequest;
use App\Http\Requests\Hrms\CreateEmployeeSettlementRequest;
use App\Http\Requests\Hrms\CreateExpenseReimbursementRequest;
use App\Http\Requests\Hrms\CreateSalaryAdvanceRequest;
use App\Http\Requests\Hrms\GenerateBankExportRequest;
use App\Http\Requests\Hrms\GeneratePayrollLedgerRequest;
use App\Http\Requests\Hrms\RejectExpenseReimbursementRequest;
use App\Http\Requests\Hrms\RejectSalaryAdvanceRequest;
use App\Http\Requests\Hrms\ReversePayrollRunRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSettlement;
use App\Models\ExpenseReimbursement;
use App\Models\PayrollBankExport;
use App\Models\PayrollJournal;
use App\Models\PayrollLedgerEntry;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Services\Hrms\PayrollFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollFinanceController extends Controller
{
    public function __construct(protected PayrollFinanceService $service) {}

    public function ledgerIndex(Request $request): View
    {
        $this->authorize('viewAny', PayrollLedgerEntry::class);

        return view('hrms.payroll.ledger.index', [
            'entries' => PayrollLedgerEntry::query()
                ->with(['employee', 'payrollRun.period'])
                ->when($request->integer('payroll_run_id'), fn ($q, $id) => $q->where('payroll_run_id', $id))
                ->latest('id')
                ->paginate(50)
                ->withQueryString(),
            'publishedRuns' => PayrollRun::query()
                ->where('status', 'published')
                ->with('period')
                ->orderByDesc('id')
                ->get(),
            'filters' => $request->only(['payroll_run_id']),
        ]);
    }

    public function ledgerGenerate(GeneratePayrollLedgerRequest $request): RedirectResponse
    {
        $run = $request->payrollRun();
        $this->service->generateLedgerForRun($run, $request->user());

        return redirect()->route('hrms.payroll.ledger.index', ['payroll_run_id' => $run->id])
            ->with('status', 'hrms-payroll-ledger-generated');
    }

    public function journalIndex(Request $request): View
    {
        $this->authorize('viewAny', PayrollJournal::class);

        return view('hrms.payroll.journals.index', [
            'journals' => PayrollJournal::query()
                ->with(['payrollRun.period', 'createdBy'])
                ->when($request->integer('payroll_run_id'), fn ($q, $id) => $q->where('payroll_run_id', $id))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['payroll_run_id']),
        ]);
    }

    public function journalShow(PayrollJournal $journal): View
    {
        $this->authorize('view', $journal);
        $journal->load(['payrollRun.period', 'lines.employee', 'createdBy', 'reversesJournal']);

        return view('hrms.payroll.journals.show', [
            'journal' => $journal,
        ]);
    }

    public function bankExportIndex(Request $request): View
    {
        $this->authorize('viewAny', PayrollBankExport::class);

        return view('hrms.payroll.bank-exports.index', [
            'exports' => PayrollBankExport::query()
                ->with(['payrollRun.period', 'exportedBy'])
                ->when($request->integer('payroll_run_id'), fn ($q, $id) => $q->where('payroll_run_id', $id))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'publishedRuns' => PayrollRun::query()
                ->where('status', 'published')
                ->with('period')
                ->orderByDesc('id')
                ->get(),
            'filters' => $request->only(['payroll_run_id']),
        ]);
    }

    public function bankExportStore(GenerateBankExportRequest $request): RedirectResponse
    {
        $run = $request->payrollRun();
        $export = $this->service->generateBankExport($run, $request->user(), $request->validated('format'));

        return redirect()->route('hrms.payroll.bank-exports.index', ['payroll_run_id' => $run->id])
            ->with('status', 'hrms-payroll-bank-export-generated');
    }

    public function bankExportDownload(PayrollBankExport $export): StreamedResponse
    {
        $this->authorize('view', $export);

        abort_unless($export->fileExists(), 404);

        return Storage::disk($export->file_disk)->download(
            $export->file_path,
            $export->export_number.'.'.$export->format
        );
    }

    public function loanIndex(): View
    {
        $this->authorize('viewAny', EmployeeLoan::class);

        return view('hrms.payroll.loans.index', [
            'loans' => EmployeeLoan::query()
                ->with(['employee', 'createdBy'])
                ->latest('id')
                ->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(['id', 'employee_code', 'first_name', 'last_name']),
        ]);
    }

    public function loanStore(CreateEmployeeLoanRequest $request): RedirectResponse
    {
        $this->service->createLoan($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.loans.index')
            ->with('status', 'hrms-employee-loan-created');
    }

    public function loanClose(CloseEmployeeLoanRequest $request, EmployeeLoan $loan): RedirectResponse
    {
        $this->service->closeLoan($loan, $request->user(), $request->validated('reason'));

        return redirect()->route('hrms.payroll.loans.index')
            ->with('status', 'hrms-employee-loan-closed');
    }

    public function advanceIndex(): View
    {
        $this->authorize('viewAny', SalaryAdvance::class);

        return view('hrms.payroll.advances.index', [
            'advances' => SalaryAdvance::query()
                ->with(['employee', 'requestedBy', 'approvedBy'])
                ->latest('id')
                ->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(['id', 'employee_code', 'first_name', 'last_name']),
        ]);
    }

    public function advanceStore(CreateSalaryAdvanceRequest $request): RedirectResponse
    {
        $this->service->createAdvance($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.advances.index')
            ->with('status', 'hrms-salary-advance-created');
    }

    public function advanceApprove(ApproveSalaryAdvanceRequest $request, SalaryAdvance $advance): RedirectResponse
    {
        $this->service->approveAdvance($advance, $request->user());

        return redirect()->route('hrms.payroll.advances.index')
            ->with('status', 'hrms-salary-advance-approved');
    }

    public function advanceReject(RejectSalaryAdvanceRequest $request, SalaryAdvance $advance): RedirectResponse
    {
        $this->service->rejectAdvance($advance, $request->user(), $request->validated('rejection_reason'));

        return redirect()->route('hrms.payroll.advances.index')
            ->with('status', 'hrms-salary-advance-rejected');
    }

    public function reimbursementIndex(): View
    {
        $this->authorize('viewAny', ExpenseReimbursement::class);

        return view('hrms.payroll.reimbursements.index', [
            'reimbursements' => ExpenseReimbursement::query()
                ->with(['employee', 'requestedBy', 'approvedBy', 'payrollRun.period'])
                ->latest('id')
                ->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(['id', 'employee_code', 'first_name', 'last_name']),
        ]);
    }

    public function reimbursementStore(CreateExpenseReimbursementRequest $request): RedirectResponse
    {
        $this->service->createReimbursement($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.reimbursements.index')
            ->with('status', 'hrms-reimbursement-created');
    }

    public function reimbursementApprove(ApproveExpenseReimbursementRequest $request, ExpenseReimbursement $reimbursement): RedirectResponse
    {
        $this->service->approveReimbursement($reimbursement, $request->user());

        return redirect()->route('hrms.payroll.reimbursements.index')
            ->with('status', 'hrms-reimbursement-approved');
    }

    public function reimbursementReject(RejectExpenseReimbursementRequest $request, ExpenseReimbursement $reimbursement): RedirectResponse
    {
        $this->service->rejectReimbursement($reimbursement, $request->user(), $request->validated('rejection_reason'));

        return redirect()->route('hrms.payroll.reimbursements.index')
            ->with('status', 'hrms-reimbursement-rejected');
    }

    public function settlementIndex(): View
    {
        $this->authorize('viewAny', EmployeeSettlement::class);

        return view('hrms.payroll.settlements.index', [
            'settlements' => EmployeeSettlement::query()
                ->with(['employee', 'completedBy'])
                ->latest('id')
                ->paginate(20),
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->get(['id', 'employee_code', 'first_name', 'last_name', 'status']),
        ]);
    }

    public function settlementStore(CreateEmployeeSettlementRequest $request): RedirectResponse
    {
        $settlement = $this->service->generateSettlement(
            $request->employee(),
            $request->user(),
            $request->safe()->only([
                'pending_salary',
                'leave_encashment',
                'asset_deductions',
                'statutory_deductions',
                'notes',
            ])
        );

        return redirect()->route('hrms.payroll.settlements.show', $settlement)
            ->with('status', 'hrms-settlement-created');
    }

    public function settlementShow(EmployeeSettlement $settlement): View
    {
        $this->authorize('view', $settlement);
        $settlement->load(['employee', 'completedBy', 'exitProcess']);

        return view('hrms.payroll.settlements.show', [
            'settlement' => $settlement,
        ]);
    }

    public function reportsIndex(Request $request): View
    {
        $this->authorize('viewAny', PayrollLedgerEntry::class);

        $report = $request->string('report')->toString() ?: 'summary';
        $runId = $request->integer('payroll_run_id') ?: null;

        $data = match ($report) {
            'statutory' => $this->service->reportStatutoryLiability($runId),
            'salary_register' => $this->service->reportSalaryRegister($runId),
            'department' => $this->service->reportDepartmentSalary($runId),
            'branch' => $this->service->reportBranchSalary($runId),
            'cost_center' => $this->service->reportCostCenterSummary($runId),
            'ledger' => $this->service->reportLedger($runId),
            default => $this->service->reportPayrollSummary($runId),
        };

        return view('hrms.payroll.reports.index', [
            'report' => $report,
            'data' => $data,
            'publishedRuns' => PayrollRun::query()
                ->whereIn('status', ['published', 'paid', 'reversed'])
                ->with('period')
                ->orderByDesc('id')
                ->get(),
            'filters' => $request->only(['report', 'payroll_run_id']),
        ]);
    }

    public function reportsExport(Request $request)
    {
        $this->authorize('viewAny', PayrollLedgerEntry::class);

        $report = $request->string('report')->toString() ?: 'summary';
        $format = $request->string('format')->toString() ?: 'csv';
        $runId = $request->integer('payroll_run_id') ?: null;

        $export = $this->service->exportReport($report, $format, $runId);

        return Storage::disk($export['disk'])->download($export['path'], $export['filename']);
    }

    public function reverseRun(ReversePayrollRunRequest $request, PayrollRun $run): RedirectResponse
    {
        $this->service->reversePayroll($run, $request->user(), $request->validated('reason'));

        return redirect()->route('hrms.payroll.runs.show', $run)
            ->with('status', 'hrms-payroll-reversed');
    }
}
