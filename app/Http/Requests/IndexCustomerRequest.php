<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesGeographicFilters;
use App\Services\CustomerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerRequest extends FormRequest
{
    use ValidatesGeographicFilters;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customers.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->geographicFilterRules(), [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('customers.statuses')))],
            'type' => ['nullable', 'string', Rule::in(array_keys(config('customers.types')))],
            'lifecycle_stage' => ['nullable', 'string', Rule::in(array_keys(config('customers.lifecycle_stages')))],
            'segment' => ['nullable', 'string', Rule::in(array_keys(config('customers.segments')))],
            'source' => ['nullable', 'string', Rule::in(array_keys(config('customers.sources')))],
            'industry' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer'],
            'tags' => ['nullable', 'string', 'max:255'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'last_activity_from' => ['nullable', 'date'],
            'last_activity_to' => ['nullable', 'date', 'after_or_equal:last_activity_from'],
            'value_min' => ['nullable', 'numeric', 'min:0'],
            'value_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', Rule::in(CustomerService::SORTABLE)],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'saved_filter' => ['nullable', 'integer'],
            'view' => ['nullable', 'string', Rule::in(['all'])],
        ]);
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'status', 'type', 'lifecycle_stage', 'segment', 'source', 'search',
            'industry', 'tags', 'assigned_to', 'created_from', 'created_to',
            'last_activity_from', 'last_activity_to', 'value_min', 'value_max',
            'sort', 'sort_direction',
        ] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
