<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportTemplateProviderInterface;
use App\Models\Organization;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entity-agnostic import template generator.
 *
 * Builds CSV and XLSX templates on demand from an ImportTemplateProviderInterface.
 * Never persists template files. Reusable for Lead, Customer, Product, etc.
 */
class ImportTemplateService
{
    public const INSTRUCTIONS_SHEET = 'Instructions';

    public const LOOKUP_SHEET = 'Lookup Values';

    /**
     * @return list<string>
     */
    public function headers(ImportTemplateProviderInterface $provider, Organization $organization): array
    {
        return array_map(
            static fn (ImportTemplateColumn $column): string => $column->label,
            $provider->columns($organization)
        );
    }

    /**
     * @return list<string|null>
     */
    public function sampleRow(ImportTemplateProviderInterface $provider, Organization $organization): array
    {
        $samples = $provider->sampleValues($organization);
        $row = [];

        foreach ($provider->columns($organization) as $column) {
            $row[] = $samples[$column->key] ?? null;
        }

        return $row;
    }

    public function toCsvString(ImportTemplateProviderInterface $provider, Organization $organization): string
    {
        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create import template CSV stream.');
        }

        try {
            // UTF-8 BOM so Excel opens the file with correct encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->headers($provider, $organization));
            fputcsv($handle, $this->sampleRow($provider, $organization));

            rewind($handle);
            $csv = stream_get_contents($handle);

            return $csv === false ? '' : $csv;
        } finally {
            fclose($handle);
        }
    }

    public function toXlsxBinary(ImportTemplateProviderInterface $provider, Organization $organization): string
    {
        $spreadsheet = $this->buildWorkbook($provider, $organization);
        $tmp = tempnam(sys_get_temp_dir(), 'import_tpl_');

        if ($tmp === false) {
            $spreadsheet->disconnectWorksheets();
            throw new \RuntimeException('Unable to allocate temporary file for import template.');
        }

        $path = $tmp.'.xlsx';
        @unlink($tmp);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $binary = file_get_contents($path);

            if ($binary === false) {
                throw new \RuntimeException('Unable to read generated import template.');
            }

            return $binary;
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($path);
        }
    }

    public function downloadCsv(
        ImportTemplateProviderInterface $provider,
        Organization $organization,
        ?string $filename = null
    ): StreamedResponse {
        $filename ??= $this->filename($provider, 'csv');
        $csv = $this->toCsvString($provider, $organization);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadXlsx(
        ImportTemplateProviderInterface $provider,
        Organization $organization,
        ?string $filename = null
    ): StreamedResponse {
        $filename ??= $this->filename($provider, 'xlsx');
        $binary = $this->toXlsxBinary($provider, $organization);

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function buildWorkbook(
        ImportTemplateProviderInterface $provider,
        Organization $organization
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle($this->safeSheetTitle($provider->dataSheetName()));

        $headers = $this->headers($provider, $organization);
        $sample = $this->sampleRow($provider, $organization);

        foreach ($headers as $index => $header) {
            $dataSheet->setCellValue([$index + 1, 1], $header);
            $dataSheet->setCellValue([$index + 1, 2], $sample[$index] ?? null);
        }

        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle(self::INSTRUCTIONS_SHEET);
        $instructionsSheet->setCellValue([1, 1], 'Instructions');
        $row = 3;
        foreach ($provider->instructionLines($organization) as $line) {
            $instructionsSheet->setCellValue([1, $row], $line);
            $row++;
        }

        $lookupSheet = $spreadsheet->createSheet();
        $lookupSheet->setTitle(self::LOOKUP_SHEET);
        $lookupSheet->setCellValue([1, 1], 'Lookup Values');
        $lookupSheet->setCellValue([1, 2], 'Use these values in the matching columns on the import sheet.');

        $col = 1;
        foreach ($provider->lookupGroups($organization) as $group) {
            $lookupSheet->setCellValue([$col, 4], $group->heading);
            if ($group->note !== null && $group->note !== '') {
                $lookupSheet->setCellValue([$col, 5], $group->note);
            }

            $valueRow = 6;
            foreach ($group->values as $value) {
                $lookupSheet->setCellValue([$col, $valueRow], $value);
                $valueRow++;
            }

            $col++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function filename(ImportTemplateProviderInterface $provider, string $extension): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $provider->entityLabel()) ?? 'entity');
        $slug = trim($slug, '_') ?: 'entity';

        return $slug.'_import_template.'.$extension;
    }

    protected function safeSheetTitle(string $title): string
    {
        $title = trim(str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $title));

        if ($title === '') {
            return 'Import';
        }

        return mb_substr($title, 0, 31);
    }
}
