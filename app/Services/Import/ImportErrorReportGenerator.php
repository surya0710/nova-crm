<?php

namespace App\Services\Import;

/**
 * Generates downloadable CSV error reports for import validation failures.
 */
class ImportErrorReportGenerator
{
    /**
     * @param  list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>  $errors
     */
    public function toCsvString(array $errors): string
    {
        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create import error report stream.');
        }

        try {
            fputcsv($handle, ['row_number', 'column', 'field', 'error', 'original_value']);

            foreach ($errors as $error) {
                fputcsv($handle, [
                    $error['row_number'],
                    $error['column'] ?? '',
                    $error['field'] ?? '',
                    $error['error'],
                    $error['value'] ?? '',
                ]);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);

            return $csv === false ? '' : $csv;
        } finally {
            fclose($handle);
        }
    }
}
