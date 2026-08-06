<?php

namespace App\Services\Import;

/**
 * A single column in a generated import template.
 */
final class ImportTemplateColumn
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $required = false,
        public readonly bool $isMetadata = false,
        public readonly ?string $metadataType = null,
    ) {
        if ($key === '' || $label === '') {
            throw new \InvalidArgumentException('Import template column key and label are required.');
        }
    }
}
