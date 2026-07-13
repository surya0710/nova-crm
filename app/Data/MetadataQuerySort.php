<?php

namespace App\Data;

use InvalidArgumentException;

class MetadataQuerySort
{
    public function __construct(
        public readonly string $key,
        public readonly string $direction = 'asc',
    ) {
        if (! in_array(strtolower($this->direction), ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Metadata query sort direction must be asc or desc.');
        }
    }

    public function normalizedDirection(): string
    {
        return strtolower($this->direction);
    }
}
