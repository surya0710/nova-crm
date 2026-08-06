<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hrms\PayrollResource;
use App\Http\Resources\Hrms\PayslipResource;
use App\Models\Payslip;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\PayrollPublicationService;
use App\Services\Hrms\PayrollService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected PayrollPublicationService $publicationService,
        protected PayrollService $payrollService,
    ) {}

    public function payslips(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', Payslip::class);

        $payslips = $this->publicationService->employeePayslips(
            $employee,
            $request->only(['year', 'month', 'period_id']),
        );

        return ApiResponse::success(
            PayslipResource::collection($payslips->load(['payrollRun.period']))
        );
    }

    public function showPayslip(Request $request, Payslip $payslip): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $payslip->employee_id === (int) $employee->id, 404);
        $this->authorize('view', $payslip);

        $payslip->load(['payrollRun.period']);

        return ApiResponse::success(new PayslipResource($payslip));
    }

    public function downloadPayslip(Request $request, Payslip $payslip): StreamedResponse|JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $payslip->employee_id === (int) $employee->id, 404);
        $this->authorize('download', $payslip);

        if (! $payslip->hasPdf() || ! $payslip->pdfExists()) {
            $this->publicationService->storePayslipPdf($payslip);
            $payslip->refresh();
        }

        if (! $payslip->hasPdf() || ! $payslip->pdfExists()) {
            return ApiResponse::error(__('Payslip PDF is not available.'), 404);
        }

        $this->publicationService->recordDownload($payslip, $request->user());

        return Storage::disk($payslip->pdf_disk)->download(
            $payslip->pdf_path,
            $payslip->payslip_number.'.pdf'
        );
    }

    public function salaryStructure(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $history = $this->payrollService->salaryRevisionHistory($employee);
        $current = $history->first();

        return ApiResponse::success([
            'current' => $current ? new PayrollResource($current->loadMissing('salaryStructure')) : null,
            'history' => PayrollResource::collection($history->loadMissing('salaryStructure')),
        ]);
    }
}
