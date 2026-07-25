<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\PayrollPublicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EssPayrollController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected PayrollPublicationService $publicationService,
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewAny', Payslip::class);

        $filters = $request->only(['year', 'month']);

        return view('ess.payroll.index', [
            'employee' => $employee,
            'payslips' => $this->publicationService->employeePayslips($employee, $filters),
            'filters' => $filters,
        ]);
    }

    public function payslips(Request $request): View
    {
        return $this->index($request);
    }

    public function show(Payslip $payslip): View
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless((int) $payslip->employee_id === (int) $employee->id, 403);
        $this->authorize('view', $payslip);

        $payslip->load(['payrollRun.period']);

        return view('ess.payroll.show', [
            'employee' => $employee,
            'payslip' => $payslip,
            'earnings' => collect($payslip->snapshot['earnings'] ?? []),
            'deductions' => collect($payslip->snapshot['deductions'] ?? [])
                ->filter(fn (array $line) => ($line['component_type'] ?? '') !== 'employer_contribution'),
            'employerContributions' => collect($payslip->snapshot['deductions'] ?? [])
                ->filter(fn (array $line) => ($line['component_type'] ?? '') === 'employer_contribution'),
            'statutory' => $payslip->snapshot['statutory'] ?? null,
        ]);
    }

    public function download(Request $request, Payslip $payslip): StreamedResponse
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless((int) $payslip->employee_id === (int) $employee->id, 403);
        $this->authorize('download', $payslip);

        if (! $payslip->hasPdf() || ! $payslip->pdfExists()) {
            $this->publicationService->storePayslipPdf($payslip);
            $payslip->refresh();
        }

        $this->publicationService->recordDownload($payslip, $request->user());

        return Storage::disk($payslip->pdf_disk)->download(
            $payslip->pdf_path,
            $payslip->payslip_number.'.pdf'
        );
    }
}
