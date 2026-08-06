<?php

namespace App\Services\Recruitment;

use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentExportService
{
    public function __construct(
        protected RecruitmentReportService $reports,
        protected AuditLogger $auditLogger,
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(string $reportType, string $format, array $filters = [], ?User $actor = null): StreamedResponse|BinaryFileResponse
    {
        $formats = config('hrms.recruitment.analytics.export_formats', []);
        if (! array_key_exists($format, $formats) || $format === 'pdf') {
            throw ValidationException::withMessages([
                'format' => $format === 'pdf'
                    ? __('PDF export is not available yet.')
                    : __('Invalid export format.'),
            ]);
        }

        $report = $this->reports->compile($reportType, $filters, $actor);
        $rows = $this->flattenReport($report);

        $organization = $this->tenant->get();
        if ($organization instanceof Organization && $actor) {
            $this->auditLogger->log($organization, 'recruitment_report_exported', [
                'report_type' => $reportType,
                'format' => $format,
                'filters' => collect($filters)->except('_department_ids')->all(),
                'row_count' => max(0, count($rows) - 1),
            ], $actor);
        }

        $filename = 'recruitment-'.$reportType.'-'.now()->format('Y-m-d-His');

        return $format === 'xlsx'
            ? $this->downloadXlsx($filename.'.xlsx', $rows)
            : $this->streamCsv($filename.'.csv', $rows);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<list<string|int|float|null>>
     */
    protected function flattenReport(array $report): array
    {
        $rows = [
            [__('Report'), $report['report_label'] ?? $report['report_type']],
            [__('Generated At'), $report['generated_at'] ?? ''],
            [__('Period'), json_encode($report['filters'] ?? [])],
            [],
        ];

        $data = $report['data'] ?? [];

        if (isset($data['rows']) && is_array($data['rows'])) {
            return array_merge($rows, $this->tableFromAssociativeRows($data['rows']));
        }

        if (isset($data['kpis']) && is_array($data['kpis'])) {
            $rows[] = [__('Metric'), __('Value')];
            foreach ($data['kpis'] as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $rows[] = [str_replace('_', ' ', (string) $key), $value];
            }
            $rows[] = [];
        }

        if (isset($data['funnel']['stages']) && is_array($data['funnel']['stages'])) {
            $rows = array_merge($rows, $this->tableFromAssociativeRows($data['funnel']['stages']));
            $rows[] = [];
        }

        if (isset($data['metrics']) && is_array($data['metrics'])) {
            $rows[] = [__('Metric'), __('Value')];
            foreach ($this->flattenArray($data['metrics']) as $key => $value) {
                $rows[] = [$key, $value];
            }
            $rows[] = [];
        }

        if (isset($data['vacancy_aging']) && is_array($data['vacancy_aging'])) {
            $rows = array_merge($rows, $this->tableFromAssociativeRows($data['vacancy_aging']));
        }

        if (isset($data['openings']) && is_array($data['openings'])) {
            $rows[] = [__('Metric'), __('Value')];
            foreach ($data['openings'] as $key => $value) {
                $rows[] = [str_replace('_', ' ', (string) $key), is_scalar($value) ? $value : json_encode($value)];
            }
        }

        if (count($rows) <= 4) {
            $rows[] = [__('Section'), __('Payload')];
            $rows[] = [__('data'), json_encode($data)];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<list<string|int|float|null>>
     */
    protected function tableFromAssociativeRows(array $items): array
    {
        if ($items === []) {
            return [[__('No data')]];
        }

        $headers = array_keys($items[0]);
        $rows = [array_map(fn ($h) => str_replace('_', ' ', (string) $h), $headers)];

        foreach ($items as $item) {
            $rows[] = array_map(function ($header) use ($item) {
                $value = $item[$header] ?? '';

                return is_scalar($value) || $value === null ? $value : json_encode($value);
            }, $headers);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string  $prefix
     * @return array<string, scalar|null>
     */
    protected function flattenArray(array $data, string $prefix = ''): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            $label = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenArray($value, $label));
            } else {
                $flat[$label] = $value;
            }
        }

        return $flat;
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function streamCsv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function downloadXlsx(string $filename, array $rows): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'rx');
        (new Xlsx($spreadsheet))->save($tmp);

        return Response::download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
