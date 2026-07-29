<?php

namespace App\Services\Hrms;

use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportExportService
{
    public function __construct(
        protected AttendanceReportService $reports,
        protected AuditLogger $auditLogger,
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(string $reportType, string $format, array $filters = [], ?User $actor = null): StreamedResponse|BinaryFileResponse
    {
        $formats = config('hrms.attendance_reports.export_formats', []);
        if (! array_key_exists($format, $formats)) {
            throw ValidationException::withMessages([
                'format' => __('Invalid export format.'),
            ]);
        }

        $report = $this->reports->compile($reportType, $filters);
        $rows = $this->tableRows($report);

        $organization = $this->tenant->get();
        if ($organization instanceof Organization && $actor) {
            $this->auditLogger->log($organization, 'attendance_report_exported', [
                'report_type' => $reportType,
                'format' => $format,
                'filters' => $report['filters'],
                'row_count' => count($report['rows']),
            ], $actor);
        }

        $filename = 'attendance-'.$reportType.'-'.now()->format('Y-m-d-His');

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
            [__('Period'), $report['filters']['month_label'] ?? ''],
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
            fwrite($handle, "\xEF\xBB\xBF");
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
        $sheet->fromArray($rows, null, 'A1');

        $directory = storage_path('app/tmp');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function downloadPdf(string $filename, array $report, array $rows): BinaryFileResponse
    {
        $html = view('pdf.attendance-report', [
            'report' => $report,
            'rows' => $rows,
        ])->render();

        $directory = 'tmp';
        Storage::disk('local')->makeDirectory($directory);
        $relative = $directory.'/'.$filename;
        Pdf::loadHTML($html)->setPaper('a4', 'landscape')->save(Storage::disk('local')->path($relative));

        return response()->download(
            Storage::disk('local')->path($relative),
            $filename,
            ['Content-Type' => 'application/pdf']
        )->deleteFileAfterSend(true);
    }
}
