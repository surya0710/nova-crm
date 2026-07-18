<?php

namespace App\Services\Import;

/**
 * A named set of allowed lookup values for the Excel Lookup Values sheet.
 */
final class ImportTemplateLookupGroup
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(
        public readonly string $heading,
        public readonly array $values,
        public readonly ?string $note = null,
    ) {
        if ($heading === '') {
            throw new \InvalidArgumentException('Import template lookup group heading is required.');
        }
    }
}
