<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use RuntimeException;

/**
 * Reusable spreadsheet reader for CSV and XLSX files.
 *
 * Detects worksheets, reads headers, normalizes rows into canonical collections.
 * Contains no entity-specific logic.
 */
class SpreadsheetReader
{
    public const FORMAT_CSV = 'csv';

    public const FORMAT_XLSX = 'xlsx';

    /** @var list<string> */
    public const SUPPORTED_EXTENSIONS = ['csv', 'xlsx'];

    /**
     * @throws RuntimeException
     */
    public function read(string $absolutePath, ?string $worksheetName = null): ParsedSpreadsheet
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('Import file does not exist.');
        }

        $format = $this->detectFormat($absolutePath);

        return match ($format) {
            self::FORMAT_CSV => $this->readCsv($absolutePath),
            self::FORMAT_XLSX => $this->readXlsx($absolutePath, $worksheetName),
            default => throw new RuntimeException("Unsupported import file format [{$format}]."),
        };
    }

    public function detectFormat(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => self::FORMAT_CSV,
            'xlsx' => self::FORMAT_XLSX,
            'xls' => throw new RuntimeException('Legacy XLS files are not supported. Use XLSX or CSV.'),
            default => throw new RuntimeException("Unsupported import file extension [{$extension}]."),
        };
    }

    /**
     * @return list<string>
     */
    public function listWorksheets(string $absolutePath): array
    {
        $format = $this->detectFormat($absolutePath);

        if ($format === self::FORMAT_CSV) {
            return ['Sheet1'];
        }

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($absolutePath);
            $names = $spreadsheet->getSheetNames();
            $spreadsheet->disconnectWorksheets();

            return $names;
        } catch (ReaderException $e) {
            throw new RuntimeException('Unable to read XLSX worksheets: '.$e->getMessage(), 0, $e);
        }
    }

    private function readCsv(string $absolutePath): ParsedSpreadsheet
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV import file.');
        }

        try {
            $headerRow = fgetcsv($handle);

            if ($headerRow === false || $this->isEmptyRow($headerRow)) {
                throw new RuntimeException('CSV import file is empty or missing a header row.');
            }

            $headers = $this->normalizeHeaderRow($headerRow);
            $rows = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'values' => $this->mapRowValues($headers, $data),
                ];
            }

            return new ParsedSpreadsheet(
                format: self::FORMAT_CSV,
                activeWorksheet: 'Sheet1',
                worksheetNames: ['Sheet1'],
                headers: $headers,
                rows: $rows,
            );
        } finally {
            fclose($handle);
        }
    }

    private function readXlsx(string $absolutePath, ?string $worksheetName = null): ParsedSpreadsheet
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($absolutePath);
        } catch (ReaderException $e) {
            throw new RuntimeException('Unable to read XLSX import file: '.$e->getMessage(), 0, $e);
        }

        try {
            $worksheetNames = $spreadsheet->getSheetNames();

            if ($worksheetNames === []) {
                throw new RuntimeException('XLSX import file contains no worksheets.');
            }

            $sheet = $worksheetName !== null
                ? $spreadsheet->getSheetByName($worksheetName)
                : $spreadsheet->getSheet(0);

            if ($sheet === null) {
                throw new RuntimeException("XLSX worksheet [{$worksheetName}] was not found.");
            }

            $matrix = $sheet->toArray(null, true, true, false);

            if ($matrix === [] || $this->isEmptyRow($matrix[0] ?? [])) {
                throw new RuntimeException('XLSX import file is empty or missing a header row.');
            }

            $headers = $this->normalizeHeaderRow($matrix[0]);
            $rows = [];

            foreach (array_slice($matrix, 1) as $index => $data) {
                $rowNumber = $index + 2;

                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'values' => $this->mapRowValues($headers, $data),
                ];
            }

            return new ParsedSpreadsheet(
                format: self::FORMAT_XLSX,
                activeWorksheet: $sheet->getTitle(),
                worksheetNames: $worksheetNames,
                headers: $headers,
                rows: $rows,
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return list<string>
     */
    private function normalizeHeaderRow(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $index => $value) {
            $label = trim((string) ($value ?? ''));

            if ($label === '') {
                $label = 'column_'.($index + 1);
            }

            $headers[] = $label;
        }

        return $headers;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $data
     * @return array<string, string|null>
     */
    private function mapRowValues(array $headers, array $data): array
    {
        $values = [];

        foreach ($headers as $index => $header) {
            $raw = $data[$index] ?? null;

            if ($raw === null) {
                $values[$header] = null;

                continue;
            }

            $string = trim((string) $raw);
            $values[$header] = $string === '' ? null : $string;
        }

        return $values;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }

            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
