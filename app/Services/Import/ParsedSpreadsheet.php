<?php

namespace App\Services\Import;

/**
 * Canonical spreadsheet parse result with no entity semantics.
 *
 * @phpstan-type CanonicalRow array{row_number: int, values: array<string, string|null>}
 */
final class ParsedSpreadsheet
{
    /**
     * @param  list<string>  $worksheetNames
     * @param  list<string>  $headers  Original header labels in column order
     * @param  list<CanonicalRow>  $rows
     */
    public function __construct(
        public readonly string $format,
        public readonly ?string $activeWorksheet,
        public readonly array $worksheetNames,
        public readonly array $headers,
        public readonly array $rows,
    ) {}

    public function rowCount(): int
    {
        return count($this->rows);
    }

    /**
     * @return list<string>
     */
    public function headerLabels(): array
    {
        return $this->headers;
    }
}
