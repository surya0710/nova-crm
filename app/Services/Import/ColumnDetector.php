<?php

namespace App\Services\Import;

/**
 * Automatic column-to-field detection with normalized header matching.
 *
 * Examples that resolve to the same field key "email":
 * Email, email, Email Address, EMAIL
 */
class ColumnDetector
{
    /**
     * @param  list<string>  $headers
     * @param  list<ImportFieldDefinition>  $fields
     * @return array{
     *     mapping: array<string, string|null>,
     *     matched_headers: array<string, string>,
     *     unknown_columns: list<string>,
     *     duplicate_columns: list<string>,
     *     unmapped_required: list<string>
     * }
     */
    public function detect(array $headers, array $fields): array
    {
        $normalizedHeaders = [];
        $duplicateColumns = [];
        $seenNormalized = [];

        foreach ($headers as $header) {
            $normalized = self::normalizeHeader($header);

            if ($normalized === '') {
                continue;
            }

            if (isset($seenNormalized[$normalized])) {
                $duplicateColumns[] = $header;
            }

            $seenNormalized[$normalized] = true;
            $normalizedHeaders[$header] = $normalized;
        }

        $mapping = [];
        $matchedHeaders = [];
        $claimedNormalized = [];

        foreach ($fields as $field) {
            $mapping[$field->key] = null;

            foreach ($field->detectionLabels() as $candidate) {
                foreach ($normalizedHeaders as $originalHeader => $normalizedHeader) {
                    if ($normalizedHeader !== $candidate) {
                        continue;
                    }

                    if (isset($claimedNormalized[$normalizedHeader])) {
                        continue;
                    }

                    $mapping[$field->key] = $originalHeader;
                    $matchedHeaders[$originalHeader] = $field->key;
                    $claimedNormalized[$normalizedHeader] = $field->key;
                    break 2;
                }
            }
        }

        $unknownColumns = [];

        foreach ($headers as $header) {
            if (! isset($matchedHeaders[$header])) {
                $unknownColumns[] = $header;
            }
        }

        $unmappedRequired = [];

        foreach ($fields as $field) {
            if ($field->required && ($mapping[$field->key] ?? null) === null) {
                $unmappedRequired[] = $field->key;
            }
        }

        return [
            'mapping' => $mapping,
            'matched_headers' => $matchedHeaders,
            'unknown_columns' => array_values($unknownColumns),
            'duplicate_columns' => array_values(array_unique($duplicateColumns)),
            'unmapped_required' => $unmappedRequired,
        ];
    }

    /**
     * Normalize a spreadsheet header for fuzzy matching.
     */
    public static function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized;
    }
}
