<?php

namespace App\Services\Documentation;

class ValidationIssue
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $type,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $module = null,
        public readonly ?string $slug = null,
        public readonly array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'code' => $this->code,
            'message' => $this->message,
            'module' => $this->module,
            'slug' => $this->slug,
            'context' => $this->context !== [] ? $this->context : null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
