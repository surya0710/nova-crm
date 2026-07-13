<?php

namespace App\Data;

class MetadataQueryFilter
{
    public function __construct(
        public readonly string $key,
        public readonly string $operator,
        public readonly mixed $value = null,
    ) {}
}
