<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\Hrms\PayrollPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends Controller
{
    public function __construct(protected PayrollPublicationService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payslip::class);

        $filters = $request->only(['employee_id', 'payroll_run_id', 'year', 'month', 'period_id']);

        return view('hrms.payroll.payslips.index', [
            'payslips' => $this->service->listPayslips($filters),
            'periods' => PayrollPeriod::query()->orderByDesc('start_date')->limit(24)->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Payslip $payslip): View
    {
        $this->authorize('view', $payslip);
        $payslip->load(['employee', 'payrollRun.period', 'publication']);

        return view('hrms.payroll.payslips.show', [
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
        $this->authorize('download', $payslip);

        if (! $payslip->hasPdf() || ! $payslip->pdfExists()) {
            $this->service->storePayslipPdf($payslip);
            $payslip->refresh();
        }

        $this->service->recordDownload($payslip, $request->user());

        return Storage::disk($payslip->pdf_disk)->download(
            $payslip->pdf_path,
            $payslip->payslip_number.'.pdf'
        );
    }

    public function resendEmail(Request $request, Payslip $payslip): RedirectResponse
    {
        $this->authorize('email', $payslip);
        $this->service->resendPayslipEmail($payslip, $request->user());

        return redirect()->route('hrms.payroll.payslips.show', $payslip)
            ->with('status', 'hrms-payslip-emailed');
    }
}
