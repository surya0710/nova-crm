<?php

namespace App\Http\Requests\Concerns;

trait ValidatesApiMetadataQuery
{
    /**
     * @return array<string, mixed>
     */
    protected function metadataQueryRules(): array
    {
        return [
            'metadata_filters' => ['sometimes', 'array'],
            'metadata_filters.*' => ['array'],
            'metadata_filters.*.key' => ['required_with:metadata_filters.*.operator', 'string', 'max:255'],
            'metadata_filters.*.operator' => ['required_with:metadata_filters.*.key', 'string', 'max:50'],
            'metadata_filters.*.value' => ['nullable'],
            'metadata_sort' => ['sometimes', 'array'],
            'metadata_sort.key' => ['sometimes', 'string', 'max:255'],
            'metadata_sort.direction' => ['sometimes', 'in:asc,desc'],
            'metadata_sort_key' => ['sometimes', 'string', 'max:255'],
            'metadata_sort_direction' => ['sometimes', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
