<?php

namespace App\Services\Import;

/**
 * Preview payload produced after parsing, mapping, and validation.
 *
 * Contains no persisted entity records.
 */
final class ImportPreview
{
    /**
     * @param  list<string>  $detectedColumns
     * @param  array<string, string|null>  $mappedFields
     * @param  list<array{row_number: int, values: array<string, string|null>, valid: bool, errors: list<string>}>  $rows
     * @param  list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>  $errors
     * @param  list<string>  $unknownColumns
     * @param  list<string>  $duplicateColumns
     */
    public function __construct(
        public readonly array $detectedColumns,
        public readonly array $mappedFields,
        public readonly int $validRows,
        public readonly int $invalidRows,
        public readonly int $totalRows,
        public readonly array $rows,
        public readonly array $errors,
        public readonly array $unknownColumns,
        public readonly array $duplicateColumns,
        public readonly ?string $worksheetName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'detected_columns' => $this->detectedColumns,
            'mapped_fields' => $this->mappedFields,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'total_rows' => $this->totalRows,
            'rows' => $this->rows,
            'errors' => $this->errors,
            'unknown_columns' => $this->unknownColumns,
            'duplicate_columns' => $this->duplicateColumns,
            'worksheet_name' => $this->worksheetName,
        ];
    }
}
