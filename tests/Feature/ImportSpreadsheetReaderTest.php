<?php

namespace Tests\Feature;

use App\Services\Import\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class ImportSpreadsheetReaderTest extends TestCase
{
    use RefreshDatabase;

    protected SpreadsheetReader $reader;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = app(SpreadsheetReader::class);
        $this->tempDir = storage_path('framework/testing/imports');
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_reads_csv_headers_and_rows(): void
    {
        $path = $this->writeCsv("Email,Name\njane@example.com,Jane\n");

        $parsed = $this->reader->read($path);

        $this->assertSame(SpreadsheetReader::FORMAT_CSV, $parsed->format);
        $this->assertSame(['Email', 'Name'], $parsed->headers);
        $this->assertSame(1, $parsed->rowCount());
        $this->assertSame(2, $parsed->rows[0]['row_number']);
        $this->assertSame('jane@example.com', $parsed->rows[0]['values']['Email']);
        $this->assertSame('Jane', $parsed->rows[0]['values']['Name']);
    }

    public function test_reads_xlsx_headers_and_rows(): void
    {
        $path = $this->writeXlsx([
            ['Email', 'Name'],
            ['john@example.com', 'John'],
            ['', ''],
            ['ada@example.com', 'Ada'],
        ]);

        $parsed = $this->reader->read($path);

        $this->assertSame(SpreadsheetReader::FORMAT_XLSX, $parsed->format);
        $this->assertSame(['Email', 'Name'], $parsed->headers);
        $this->assertCount(2, $parsed->rows);
        $this->assertSame('john@example.com', $parsed->rows[0]['values']['Email']);
        $this->assertSame('ada@example.com', $parsed->rows[1]['values']['Email']);
        $this->assertSame(4, $parsed->rows[1]['row_number']);
    }

    public function test_lists_xlsx_worksheets(): void
    {
        $path = $this->writeXlsx([
            ['Email'],
            ['a@example.com'],
        ], 'Leads');

        $this->assertSame(['Leads'], $this->reader->listWorksheets($path));
    }

    public function test_rejects_empty_csv(): void
    {
        $path = $this->writeCsv('');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('empty or missing a header row');

        $this->reader->read($path);
    }

    public function test_rejects_malformed_xlsx(): void
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.'broken.xlsx';
        file_put_contents($path, 'not-a-real-xlsx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to read XLSX');

        $this->reader->read($path);
    }

    public function test_rejects_legacy_xls_extension(): void
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.'legacy.xls';
        file_put_contents($path, 'fake');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Legacy XLS files are not supported');

        $this->reader->detectFormat($path);
    }

    protected function writeCsv(string $contents): string
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.uniqid('csv_', true).'.csv';
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    protected function writeXlsx(array $rows, string $sheetName = 'Sheet1'): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        foreach ($rows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
            }
        }

        $path = $this->tempDir.DIRECTORY_SEPARATOR.uniqid('xlsx_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
