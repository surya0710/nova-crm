<?php

namespace App\Services\Import;

use Carbon\Carbon;
use Throwable;

/**
 * Validates parsed import rows against entity field definitions.
 *
 * Collects row-level errors only. Does not persist entity records.
 */
class ImportValidationEngine
{
    /**
     * @param  list<ImportFieldDefinition>  $fields
     * @param  array<string, string|null>  $mapping  field key => original header
     * @param  list<string>  $unknownColumns
     * @param  list<string>  $duplicateColumns
     * @return array{
     *     valid_rows: int,
     *     invalid_rows: int,
     *     total_rows: int,
     *     errors: list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>,
     *     preview_rows: list<array{row_number: int, values: array<string, string|null>, valid: bool, errors: list<string>}>,
     *     unknown_columns: list<string>,
     *     duplicate_columns: list<string>
     * }
     */
    public function validate(
        ParsedSpreadsheet $spreadsheet,
        array $fields,
        array $mapping,
        array $unknownColumns = [],
        array $duplicateColumns = [],
    ): array {
        $fieldsByKey = [];
        foreach ($fields as $field) {
            $fieldsByKey[$field->key] = $field;
        }

        $errors = [];

        foreach ($duplicateColumns as $column) {
            $errors[] = [
                'row_number' => 1,
                'column' => $column,
                'field' => null,
                'error' => 'Duplicate column header.',
                'value' => $column,
            ];
        }

        foreach ($unknownColumns as $column) {
            $errors[] = [
                'row_number' => 1,
                'column' => $column,
                'field' => null,
                'error' => 'Unknown column is not mapped to an import field.',
                'value' => $column,
            ];
        }

        foreach ($fields as $field) {
            if ($field->required && ($mapping[$field->key] ?? null) === null) {
                $errors[] = [
                    'row_number' => 1,
                    'column' => null,
                    'field' => $field->key,
                    'error' => "Required field [{$field->label}] is not mapped.",
                    'value' => null,
                ];
            }
        }

        $previewRows = [];
        $validRows = 0;
        $invalidRows = 0;

        foreach ($spreadsheet->rows as $row) {
            $mappedValues = [];
            $rowErrors = [];

            foreach ($fields as $field) {
                $header = $mapping[$field->key] ?? null;
                $value = $header !== null ? ($row['values'][$header] ?? null) : null;
                $mappedValues[$field->key] = $value;

                foreach ($this->validateValue($field, $value) as $message) {
                    $rowErrors[] = $message;
                    $errors[] = [
                        'row_number' => $row['row_number'],
                        'column' => $header,
                        'field' => $field->key,
                        'error' => $message,
                        'value' => $value,
                    ];
                }
            }

            $isValid = $rowErrors === [];

            if ($isValid) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            $previewRows[] = [
                'row_number' => $row['row_number'],
                'values' => $mappedValues,
                'valid' => $isValid,
                'errors' => $rowErrors,
            ];
        }

        return [
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'total_rows' => count($spreadsheet->rows),
            'errors' => $errors,
            'preview_rows' => $previewRows,
            'unknown_columns' => array_values($unknownColumns),
            'duplicate_columns' => array_values($duplicateColumns),
        ];
    }

    /**
     * Merge entity-specific errors into a validation result and recalculate row validity.
     *
     * @param  array{
     *     valid_rows: int,
     *     invalid_rows: int,
     *     total_rows: int,
     *     errors: list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>,
     *     preview_rows: list<array{row_number: int, values: array<string, string|null>, valid: bool, errors: list<string>}>,
     *     unknown_columns: list<string>,
     *     duplicate_columns: list<string>
     * }  $result
     * @param  list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>  $entityErrors
     * @return array{
     *     valid_rows: int,
     *     invalid_rows: int,
     *     total_rows: int,
     *     errors: list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>,
     *     preview_rows: list<array{row_number: int, values: array<string, string|null>, valid: bool, errors: list<string>}>,
     *     unknown_columns: list<string>,
     *     duplicate_columns: list<string>
     * }
     */
    public function mergeEntityErrors(array $result, array $entityErrors): array
    {
        if ($entityErrors === []) {
            return $result;
        }

        $errorsByRow = [];
        foreach ($entityErrors as $error) {
            $rowNumber = (int) $error['row_number'];
            $errorsByRow[$rowNumber][] = $error['error'];
            $result['errors'][] = $error;
        }

        $validRows = 0;
        $invalidRows = 0;

        foreach ($result['preview_rows'] as $index => $row) {
            $extra = $errorsByRow[$row['row_number']] ?? [];
            if ($extra !== []) {
                $row['errors'] = array_values(array_merge($row['errors'], $extra));
                $row['valid'] = false;
            }

            if ($row['valid']) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            $result['preview_rows'][$index] = $row;
        }

        $result['valid_rows'] = $validRows;
        $result['invalid_rows'] = $invalidRows;

        return $result;
    }

    /**
     * @return list<string>
     */
    public function validateValue(ImportFieldDefinition $field, mixed $value): array
    {
        $stringValue = $value === null ? null : trim((string) $value);
        $isEmpty = $stringValue === null || $stringValue === '';

        if ($field->required && $isEmpty) {
            return ["{$field->label} is required."];
        }

        if ($isEmpty) {
            return [];
        }

        return match ($field->dataType) {
            ImportFieldDefinition::TYPE_EMAIL => $this->validateEmail($field, $stringValue),
            ImportFieldDefinition::TYPE_PHONE => $this->validatePhone($field, $stringValue),
            ImportFieldDefinition::TYPE_DATE => $this->validateDate($field, $stringValue),
            ImportFieldDefinition::TYPE_NUMBER => $this->validateNumber($field, $stringValue),
            ImportFieldDefinition::TYPE_INTEGER => $this->validateInteger($field, $stringValue),
            ImportFieldDefinition::TYPE_BOOLEAN => $this->validateBoolean($field, $stringValue),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function validateEmail(ImportFieldDefinition $field, string $value): array
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ["{$field->label} must be a valid email address."];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validatePhone(ImportFieldDefinition $field, string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return ["{$field->label} must be a valid phone number."];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateDate(ImportFieldDefinition $field, string $value): array
    {
        try {
            Carbon::parse($value);

            return [];
        } catch (Throwable) {
            return ["{$field->label} must be a valid date."];
        }
    }

    /**
     * @return list<string>
     */
    private function validateNumber(ImportFieldDefinition $field, string $value): array
    {
        if (! is_numeric($value)) {
            return ["{$field->label} must be a valid number."];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateInteger(ImportFieldDefinition $field, string $value): array
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            return ["{$field->label} must be a valid integer."];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function validateBoolean(ImportFieldDefinition $field, string $value): array
    {
        $normalized = strtolower($value);

        if (! in_array($normalized, ['1', '0', 'true', 'false', 'yes', 'no', 'y', 'n', 'on', 'off'], true)) {
            return ["{$field->label} must be a valid boolean."];
        }

        return [];
    }
}
