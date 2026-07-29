<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanningReportExportService
{
    public function __construct(
        protected PlanningReportService $reports,
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(
        Organization $organization,
        string $reportType,
        string $format,
        array $filters = [],
        ?User $actor = null,
    ): StreamedResponse|BinaryFileResponse {
        $formats = config('projects.planning_reports.export_formats', []);
        if (! array_key_exists($format, $formats)) {
            throw ValidationException::withMessages([
                'format' => __('Invalid export format.'),
            ]);
        }

        $report = $this->reports->compile($organization, $reportType, $filters);
        $rows = $this->tableRows($report);
        $filename = 'planning-'.$reportType.'-'.now()->format('Y-m-d-His');

        return match ($format) {
            'xlsx' => $this->downloadXlsx($filename.'.xlsx', $rows),
            'pdf' => $this->downloadPdf($filename.'.pdf', $report, $rows),
            default => $this->streamCsv($filename.'.csv', $rows),
        };
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<list<string|int|float|null>>
     */
    protected function tableRows(array $report): array
    {
        $columns = $report['columns'] ?? [];
        $header = array_map(fn (array $column) => $column['label'], $columns);
        $rows = [
            [__('Report'), $report['report_label'] ?? ''],
            [__('From'), $report['filters']['from'] ?? ''],
            [__('To'), $report['filters']['to'] ?? ''],
            [__('Generated At'), $report['generated_at'] ?? ''],
            [],
            $header,
        ];

        foreach ($report['rows'] as $row) {
            $rows[] = array_map(
                fn (array $column) => $row[$column['key']] ?? '',
                $columns
            );
        }

        return $rows;
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function streamCsv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function downloadXlsx(string $filename, array $rows): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValue([$c + 1, $r + 1], $value);
            }
        }

        $path = storage_path('app/tmp/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function downloadPdf(string $filename, array $report, array $rows): BinaryFileResponse
    {
        $pdf = Pdf::loadView('projects.planning.reports.pdf', [
            'report' => $report,
            'rows' => $rows,
        ]);

        $path = storage_path('app/tmp/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        Storage::disk('local')->put('tmp/'.$filename, $pdf->output());

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
