<?php

namespace App\Services\Import;

use App\Models\ImportSession;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entity-agnostic import validation report generator.
 *
 * Turns an ImportSession's stored validation errors into a downloadable,
 * human-readable report (CSV, optionally XLSX). It never re-runs validation,
 * never persists entities, and reuses the errors already materialized on the
 * session by ImportPlatformService. Reusable across Lead, Customer, etc.
 */
class ImportValidationReportService
{
    /** @var list<string> */
    public const COLUMNS = ['Row Number', 'Column', 'Imported Value', 'Error Message'];

    public const ERRORS_SHEET = 'Validation Errors';

    public const SUMMARY_SHEET = 'Summary';

    public function __construct(
        protected ImportEntityRegistry $registry,
    ) {}

    /**
     * Whether the session has any validation errors to report.
     */
    public function hasErrors(ImportSession $session): bool
    {
        return $this->errorsFor($session) !== [];
    }

    /**
     * Build normalized, deterministically ordered report rows.
     *
     * Ordering: Row Number ASC, then Column Name ASC (case-insensitive).
     *
     * @return list<array{row_number: int, column: string, value: string, error: string}>
     */
    public function buildRows(ImportSession $session): array
    {
        $labels = $this->fieldLabels($session->entity_type);

        $rows = [];
        foreach ($this->errorsFor($session) as $error) {
            $rows[] = [
                'row_number' => (int) ($error['row_number'] ?? 0),
                'column' => $this->columnName($error, $labels),
                'value' => $this->stringValue($error['value'] ?? null),
                'error' => (string) ($error['error'] ?? ''),
            ];
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => [$a['row_number'], mb_strtolower($a['column']), $a['error']]
                <=> [$b['row_number'], mb_strtolower($b['column']), $b['error']]
        );

        return $rows;
    }

    /**
     * Build the report summary block.
     *
     * @return array{
     *     organization: string,
     *     generated: string,
     *     import_session: string,
     *     rows_processed: int,
     *     rows_valid: int,
     *     rows_invalid: int,
     *     total_errors: int
     * }
     */
    public function summary(ImportSession $session): array
    {
        $validationSummary = is_array($session->validation_summary) ? $session->validation_summary : [];

        $total = (int) ($session->total_rows ?? 0);
        $valid = (int) ($validationSummary['valid_rows'] ?? 0);
        $invalid = (int) ($validationSummary['invalid_rows'] ?? max(0, $total - $valid));

        return [
            'organization' => (string) ($session->organization?->name ?? ''),
            'generated' => now()->toDateTimeString(),
            'import_session' => (string) $session->original_filename.' (#'.$session->id.')',
            'rows_processed' => $total,
            'rows_valid' => $valid,
            'rows_invalid' => $invalid,
            'total_errors' => count($this->errorsFor($session)),
        ];
    }

    public function toCsvString(ImportSession $session): string
    {
        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create validation report stream.');
        }

        try {
            // UTF-8 BOM so Excel opens the file with correct encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            $this->writeCsv($handle, $session);

            rewind($handle);
            $csv = stream_get_contents($handle);

            return $csv === false ? '' : $csv;
        } finally {
            fclose($handle);
        }
    }

    public function downloadCsv(ImportSession $session, ?string $filename = null): StreamedResponse
    {
        $filename ??= $this->filename($session, 'csv');

        // Stream directly to output so large reports never fully buffer in memory.
        return response()->streamDownload(function () use ($session): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new \RuntimeException('Unable to open validation report output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            $this->writeCsv($handle, $session);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function toXlsxBinary(ImportSession $session): string
    {
        $spreadsheet = $this->buildWorkbook($session);
        $tmp = tempnam(sys_get_temp_dir(), 'import_report_');

        if ($tmp === false) {
            $spreadsheet->disconnectWorksheets();
            throw new \RuntimeException('Unable to allocate temporary file for validation report.');
        }

        $path = $tmp.'.xlsx';
        @unlink($tmp);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $binary = file_get_contents($path);

            if ($binary === false) {
                throw new \RuntimeException('Unable to read generated validation report.');
            }

            return $binary;
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function downloadXlsx(ImportSession $session, ?string $filename = null): StreamedResponse
    {
        $filename ??= $this->filename($session, 'xlsx');
        $binary = $this->toXlsxBinary($session);

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function buildWorkbook(ImportSession $session): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle(self::SUMMARY_SHEET);
        $summarySheet->setCellValue([1, 1], 'Import Validation Report');

        $row = 3;
        foreach ($this->summaryLines($session) as [$label, $value]) {
            $summarySheet->setCellValue([1, $row], $label);
            $summarySheet->setCellValue([2, $row], $value);
            $row++;
        }

        $errorsSheet = $spreadsheet->createSheet();
        $errorsSheet->setTitle(self::ERRORS_SHEET);

        foreach (self::COLUMNS as $index => $header) {
            $errorsSheet->setCellValue([$index + 1, 1], $header);
        }

        $line = 2;
        foreach ($this->buildRows($session) as $reportRow) {
            $errorsSheet->setCellValue([1, $line], $reportRow['row_number']);
            $errorsSheet->setCellValueExplicit(
                [2, $line],
                $reportRow['column'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $errorsSheet->setCellValueExplicit(
                [3, $line],
                $reportRow['value'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $errorsSheet->setCellValueExplicit(
                [4, $line],
                $reportRow['error'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $line++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function filename(ImportSession $session, string $extension): string
    {
        return 'validation_report_'.$session->id.'.'.$extension;
    }

    /**
     * @param  resource  $handle
     */
    protected function writeCsv($handle, ImportSession $session): void
    {
        foreach ($this->summaryLines($session) as [$label, $value]) {
            fputcsv($handle, [$label, $value]);
        }

        // Blank spacer row between the summary block and the error table.
        fputcsv($handle, []);

        fputcsv($handle, self::COLUMNS);

        foreach ($this->buildRows($session) as $reportRow) {
            fputcsv($handle, [
                $reportRow['row_number'],
                $reportRow['column'],
                $reportRow['value'],
                $reportRow['error'],
            ]);
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    protected function summaryLines(ImportSession $session): array
    {
        $summary = $this->summary($session);

        return [
            ['Import Validation Report', ''],
            ['Organization', $summary['organization']],
            ['Generated', $summary['generated']],
            ['Import Session', $summary['import_session']],
            ['Rows Processed', (string) $summary['rows_processed']],
            ['Rows Valid', (string) $summary['rows_valid']],
            ['Rows Invalid', (string) $summary['rows_invalid']],
            ['Total Errors', (string) $summary['total_errors']],
        ];
    }

    /**
     * Read the validation errors already stored on the session by the platform.
     *
     * @return list<array{row_number?: int, column?: string|null, field?: string|null, error?: string, value?: string|null}>
     */
    protected function errorsFor(ImportSession $session): array
    {
        $errors = $session->validation_summary['errors'] ?? null;

        return is_array($errors) ? array_values($errors) : [];
    }

    /**
     * Resolve a human-readable column name for an error.
     *
     * Prefers the original spreadsheet header; falls back to the field label,
     * then the raw field key.
     *
     * @param  array{column?: string|null, field?: string|null}  $error
     * @param  array<string, string>  $labels
     */
    protected function columnName(array $error, array $labels): string
    {
        $column = $error['column'] ?? null;
        if (is_string($column) && $column !== '') {
            return $column;
        }

        $field = $error['field'] ?? null;
        if (is_string($field) && $field !== '') {
            return $labels[$field] ?? $field;
        }

        return '';
    }

    /**
     * Map field keys to human-readable labels for the entity (tenant-scoped
     * metadata comes from the adapter's current TenantContext).
     *
     * @return array<string, string>
     */
    protected function fieldLabels(string $entityType): array
    {
        if (! $this->registry->has($entityType)) {
            return [];
        }

        $labels = [];
        foreach ($this->registry->resolve($entityType)->fieldDefinitions() as $field) {
            $labels[$field->key] = $field->label;
        }

        return $labels;
    }

    protected function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }
}
