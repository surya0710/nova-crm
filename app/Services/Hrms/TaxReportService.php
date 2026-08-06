<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\Form16Record;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProjection;
use App\Models\TaxProof;
use App\Models\TdsMonthlyCalculation;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TaxReportService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected IncomeTaxService $incomeTaxService,
    ) {}

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    public function report(string $type, ?int $financialYearId = null): array
    {
        $reportTypes = config('hrms.income_tax.report_types', []);

        if (! array_key_exists($type, $reportTypes)) {
            throw ValidationException::withMessages([
                'type' => 'Invalid tax report type.',
            ]);
        }

        return match ($type) {
            'tds_register' => $this->tdsRegister($financialYearId),
            'tax_projection' => $this->taxProjection($financialYearId),
            'employee_tax_summary' => $this->employeeTaxSummary($financialYearId),
            'declaration_status' => $this->declarationStatus($financialYearId),
            'proof_verification' => $this->proofVerification($financialYearId),
            'form16_summary' => $this->form16Summary($financialYearId),
            default => throw ValidationException::withMessages(['type' => 'Unsupported report type.']),
        };
    }

    /**
     * @return array{path: string, filename: string, disk: string}
     */
    public function export(string $type, string $format, ?int $financialYearId = null): array
    {
        $formats = config('hrms.income_tax.export_formats', ['csv', 'xlsx', 'pdf']);

        if (! in_array($format, $formats, true)) {
            throw ValidationException::withMessages([
                'format' => 'Invalid export format.',
            ]);
        }

        $payload = $this->report($type, $financialYearId);
        $disk = config('hrms.payslips.disk', 'local');
        $filename = 'tax-report-'.$type.'-'.now()->format('YmdHis').'.'.$format;
        $path = 'hrms-tax-reports/'.$this->tenantContext->id().'/'.$filename;

        $normalized = $this->normalizeRows($payload['headers'], $payload['data']);

        match ($format) {
            'csv' => $this->writeCsvExport($disk, $path, $payload['headers'], $normalized),
            'xlsx' => $this->writeXlsxExport($disk, $path, $payload['headers'], $normalized),
            'pdf' => $this->writePdfExport($disk, $path, $type, $payload),
            default => throw ValidationException::withMessages(['format' => 'Unsupported export format.']),
        };

        return compact('path', 'filename', 'disk');
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function tdsRegister(?int $financialYearId): array
    {
        $headers = [
            'employee_code', 'employee_name', 'year', 'month', 'regime',
            'gross_salary', 'taxable_income_annual', 'tds_amount', 'tds_ytd', 'status',
        ];

        $rows = TdsMonthlyCalculation::query()
            ->with('employee')
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn (TdsMonthlyCalculation $row) => [
                'employee_code' => $row->employee?->employee_code,
                'employee_name' => $row->employee?->full_name,
                'year' => $row->year,
                'month' => $row->month,
                'regime' => $row->regime,
                'gross_salary' => (float) $row->gross_salary,
                'taxable_income_annual' => (float) $row->taxable_income_annual,
                'tds_amount' => (float) $row->tds_amount,
                'tds_ytd' => (float) $row->tds_ytd,
                'status' => $row->status,
            ])
            ->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function taxProjection(?int $financialYearId): array
    {
        $headers = [
            'employee_code', 'employee_name', 'regime', 'projected_gross',
            'projected_taxable', 'annual_tax_liability', 'tds_already_deducted',
            'remaining_tds', 'monthly_tds', 'calculated_at',
        ];

        $rows = TaxProjection::query()
            ->with('employee')
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->orderBy('employee_id')
            ->get()
            ->map(fn (TaxProjection $row) => [
                'employee_code' => $row->employee?->employee_code,
                'employee_name' => $row->employee?->full_name,
                'regime' => $row->regime,
                'projected_gross' => (float) $row->projected_gross,
                'projected_taxable' => (float) $row->projected_taxable,
                'annual_tax_liability' => (float) $row->annual_tax_liability,
                'tds_already_deducted' => (float) $row->tds_already_deducted,
                'remaining_tds' => (float) $row->remaining_tds,
                'monthly_tds' => (float) $row->monthly_tds,
                'calculated_at' => $row->calculated_at?->toDateTimeString(),
            ])
            ->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function employeeTaxSummary(?int $financialYearId): array
    {
        $fy = $this->resolveFinancialYear($financialYearId);
        $headers = [
            'employee_code', 'employee_name', 'regime', 'declared_total',
            'approved_total', 'annual_tax_liability', 'tds_deducted', 'form16_status',
        ];

        $projections = TaxProjection::query()
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->get()
            ->keyBy('employee_id');

        $declarations = TaxDeclaration::query()
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->where('status', TaxDeclaration::STATUS_VERIFIED)
            ->get()
            ->keyBy('employee_id');

        $tdsTotals = TdsMonthlyCalculation::query()
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->selectRaw('employee_id, SUM(tds_amount) as total_tds')
            ->groupBy('employee_id')
            ->pluck('total_tds', 'employee_id');

        $form16Statuses = Form16Record::query()
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->pluck('status', 'employee_id');

        $employeeIds = collect($projections->keys())
            ->merge($declarations->keys())
            ->merge($tdsTotals->keys())
            ->unique()
            ->values();

        $employees = Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id');

        $rows = $employeeIds->map(function (int $employeeId) use (
            $employees,
            $projections,
            $declarations,
            $tdsTotals,
            $form16Statuses,
            $fy,
        ) {
            $employee = $employees->get($employeeId);
            $projection = $projections->get($employeeId);
            $declaration = $declarations->get($employeeId);
            $regime = $projection?->regime
                ?? ($employee && $fy ? $this->incomeTaxService->resolveEmployeeRegime($employee, $fy) : null);

            return [
                'employee_code' => $employee?->employee_code,
                'employee_name' => $employee?->full_name,
                'regime' => $regime,
                'declared_total' => (float) ($declaration?->declared_total ?? 0),
                'approved_total' => (float) ($declaration?->approved_total ?? 0),
                'annual_tax_liability' => (float) ($projection?->annual_tax_liability ?? 0),
                'tds_deducted' => round((float) ($tdsTotals[$employeeId] ?? 0), 2),
                'form16_status' => $form16Statuses[$employeeId] ?? 'not_generated',
            ];
        })->values()->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function declarationStatus(?int $financialYearId): array
    {
        $headers = [
            'declaration_number', 'employee_code', 'employee_name', 'status',
            'declared_total', 'approved_total', 'submitted_at', 'verified_at',
        ];

        $rows = TaxDeclaration::query()
            ->with('employee')
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->orderByDesc('id')
            ->get()
            ->map(fn (TaxDeclaration $row) => [
                'declaration_number' => $row->declaration_number,
                'employee_code' => $row->employee?->employee_code,
                'employee_name' => $row->employee?->full_name,
                'status' => $row->status,
                'declared_total' => (float) $row->declared_total,
                'approved_total' => (float) $row->approved_total,
                'submitted_at' => $row->submitted_at?->toDateTimeString(),
                'verified_at' => $row->verified_at?->toDateTimeString(),
            ])
            ->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function proofVerification(?int $financialYearId): array
    {
        $headers = [
            'proof_number', 'employee_code', 'employee_name', 'category', 'title',
            'claimed_amount', 'approved_amount', 'status', 'verified_at',
        ];

        $rows = TaxProof::query()
            ->with(['employee', 'declaration'])
            ->when($financialYearId, function ($q) use ($financialYearId) {
                $q->whereHas('declaration', fn ($inner) => $inner->where('tax_financial_year_id', $financialYearId));
            })
            ->orderByDesc('id')
            ->get()
            ->map(fn (TaxProof $row) => [
                'proof_number' => $row->proof_number,
                'employee_code' => $row->employee?->employee_code,
                'employee_name' => $row->employee?->full_name,
                'category' => $row->category,
                'title' => $row->title,
                'claimed_amount' => (float) $row->claimed_amount,
                'approved_amount' => $row->approved_amount !== null ? (float) $row->approved_amount : null,
                'status' => $row->status,
                'verified_at' => $row->verified_at?->toDateTimeString(),
            ])
            ->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    protected function form16Summary(?int $financialYearId): array
    {
        $headers = [
            'form_number', 'employee_code', 'employee_name', 'financial_year',
            'status', 'tax_deducted', 'generated_at',
        ];

        $rows = Form16Record::query()
            ->with(['employee', 'financialYear'])
            ->when($financialYearId, fn ($q) => $q->where('tax_financial_year_id', $financialYearId))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Form16Record $row) => [
                'form_number' => $row->form_number,
                'employee_code' => $row->employee?->employee_code,
                'employee_name' => $row->employee?->full_name,
                'financial_year' => $row->financialYear?->code,
                'status' => $row->status,
                'tax_deducted' => (float) ($row->tax_paid['tds_deducted'] ?? 0),
                'generated_at' => $row->generated_at?->toDateTimeString(),
            ])
            ->all();

        return ['headers' => $headers, 'data' => $rows];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, mixed>>  $data
     * @return list<array<string, string>>
     */
    protected function normalizeRows(array $headers, array $data): array
    {
        return array_map(function (array $row) use ($headers) {
            $normalized = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $normalized[$header] = is_scalar($value) || $value === null
                    ? (string) ($value ?? '')
                    : json_encode($value);
            }

            return $normalized;
        }, $data);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows
     */
    protected function writeCsvExport(string $disk, string $path, array $headers, array $rows): void
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $header) => $row[$header] ?? '', $headers));
        }
        rewind($handle);
        Storage::disk($disk)->put($path, stream_get_contents($handle) ?: '');
        fclose($handle);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows
     */
    protected function writeXlsxExport(string $disk, string $path, array $headers, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $row[$header] ?? '');
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tax');
        (new Xlsx($spreadsheet))->save($tmp);
        Storage::disk($disk)->put($path, file_get_contents($tmp) ?: '');
        @unlink($tmp);
    }

    /**
     * @param  array{headers: list<string>, data: list<array<string, mixed>>}  $payload
     */
    protected function writePdfExport(string $disk, string $path, string $type, array $payload): void
    {
        $reportLabel = config("hrms.income_tax.report_types.{$type}", $type);
        $lines = collect($payload['data'])->take(100)->map(function (array $row) use ($payload) {
            return collect($payload['headers'])
                ->map(fn (string $header) => $header.': '.($row[$header] ?? ''))
                ->implode(' | ');
        })->implode("\n");

        $html = '<html><body style="font-family: DejaVu Sans, sans-serif; font-size: 10px;">'
            .'<h2>'.e($reportLabel).'</h2>'
            .'<p>Generated: '.e(now()->toDateTimeString()).'</p>'
            .'<pre>'.e($lines ?: 'No data').'</pre>'
            .'</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        Storage::disk($disk)->put($path, $pdf->output());
    }

    protected function resolveFinancialYear(?int $financialYearId): ?TaxFinancialYear
    {
        if ($financialYearId) {
            return TaxFinancialYear::query()->find($financialYearId);
        }

        return $this->incomeTaxService->resolveFinancialYear();
    }
}
