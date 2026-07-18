<?php

namespace Tests\Feature;

use App\Services\Import\ColumnDetector;
use App\Services\Import\ImportFieldDefinition;
use App\Services\Import\ImportValidationEngine;
use App\Services\Import\ParsedSpreadsheet;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeImportableEntity;
use Tests\TestCase;

class ImportValidationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ImportValidationEngine $validator;

    protected ColumnDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(ImportValidationEngine::class);
        $this->detector = app(ColumnDetector::class);
    }

    public function test_column_detection_normalizes_header_variants(): void
    {
        $fields = (new FakeImportableEntity)->fieldDefinitions();

        $result = $this->detector->detect(
            ['EMAIL', 'Email Address', 'Full Name', 'Phone Number'],
            $fields
        );

        $this->assertSame('EMAIL', $result['mapping']['email']);
        $this->assertSame('Full Name', $result['mapping']['full_name']);
        $this->assertSame('Phone Number', $result['mapping']['phone']);
        $this->assertContains('Email Address', $result['unknown_columns']);
    }

    public function test_required_fields_and_invalid_values_are_collected(): void
    {
        $fields = (new FakeImportableEntity)->fieldDefinitions();
        $spreadsheet = new ParsedSpreadsheet(
            format: SpreadsheetReader::FORMAT_CSV,
            activeWorksheet: 'Sheet1',
            worksheetNames: ['Sheet1'],
            headers: ['Email', 'Name', 'Phone', 'Amount', 'Start Date'],
            rows: [
                [
                    'row_number' => 2,
                    'values' => [
                        'Email' => 'not-an-email',
                        'Name' => 'Jane',
                        'Phone' => '123',
                        'Amount' => 'abc',
                        'Start Date' => 'not-a-date',
                    ],
                ],
                [
                    'row_number' => 3,
                    'values' => [
                        'Email' => null,
                        'Name' => null,
                        'Phone' => null,
                        'Amount' => null,
                        'Start Date' => null,
                    ],
                ],
            ],
        );

        $detection = $this->detector->detect($spreadsheet->headers, $fields);
        $result = $this->validator->validate(
            $spreadsheet,
            $fields,
            $detection['mapping'],
            $detection['unknown_columns'],
            $detection['duplicate_columns'],
        );

        $this->assertSame(0, $result['valid_rows']);
        $this->assertSame(2, $result['invalid_rows']);

        $messages = collect($result['errors'])->pluck('error')->all();
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'valid email')));
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'valid phone')));
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'valid number')));
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'valid date')));
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'Email is required')));
        $this->assertTrue(collect($messages)->contains(fn ($m) => str_contains($m, 'Full Name is required')));
    }

    public function test_duplicate_and_unknown_columns_are_reported(): void
    {
        $fields = [
            new ImportFieldDefinition('email', 'Email', true, ImportFieldDefinition::TYPE_EMAIL),
        ];

        $spreadsheet = new ParsedSpreadsheet(
            format: SpreadsheetReader::FORMAT_CSV,
            activeWorksheet: 'Sheet1',
            worksheetNames: ['Sheet1'],
            headers: ['Email', 'Email', 'Nickname'],
            rows: [
                [
                    'row_number' => 2,
                    'values' => [
                        'Email' => 'ok@example.com',
                        'Nickname' => 'Ace',
                    ],
                ],
            ],
        );

        $detection = $this->detector->detect($spreadsheet->headers, $fields);
        $result = $this->validator->validate(
            $spreadsheet,
            $fields,
            $detection['mapping'],
            $detection['unknown_columns'],
            $detection['duplicate_columns'],
        );

        $this->assertNotEmpty($detection['duplicate_columns']);
        $this->assertContains('Nickname', $result['unknown_columns']);
        $this->assertTrue(
            collect($result['errors'])->contains(fn ($e) => $e['error'] === 'Duplicate column header.')
        );
        $this->assertTrue(
            collect($result['errors'])->contains(
                fn ($e) => $e['error'] === 'Unknown column is not mapped to an import field.'
            )
        );
    }

    public function test_valid_row_passes_without_errors(): void
    {
        $fields = (new FakeImportableEntity)->fieldDefinitions();
        $spreadsheet = new ParsedSpreadsheet(
            format: SpreadsheetReader::FORMAT_CSV,
            activeWorksheet: 'Sheet1',
            worksheetNames: ['Sheet1'],
            headers: ['Email', 'Full Name', 'Phone', 'Amount', 'Started On'],
            rows: [
                [
                    'row_number' => 2,
                    'values' => [
                        'Email' => 'valid@example.com',
                        'Full Name' => 'Valid User',
                        'Phone' => '+1 (555) 123-4567',
                        'Amount' => '19.99',
                        'Started On' => '2026-07-01',
                    ],
                ],
            ],
        );

        $detection = $this->detector->detect($spreadsheet->headers, $fields);
        $result = $this->validator->validate(
            $spreadsheet,
            $fields,
            $detection['mapping'],
            $detection['unknown_columns'],
            $detection['duplicate_columns'],
        );

        $this->assertSame(1, $result['valid_rows']);
        $this->assertSame(0, $result['invalid_rows']);
        $this->assertSame([], $result['errors']);
    }
}
