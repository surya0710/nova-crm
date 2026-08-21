<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use App\Http\Requests\Concerns\ValidatesGeographicFilters;
use App\Services\CustomerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexApiCustomerRequest extends FormRequest
{
    use ValidatesApiMetadataQuery;
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
        return array_merge($this->metadataQueryRules(), $this->geographicFilterRules(), [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('customers.statuses')))],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('customers.types')))],
            'lifecycle_stage' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('customers.lifecycle_stages')))],
            'segment' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('customers.segments')))],
            'source' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('customers.sources')))],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'assigned_to' => ['sometimes', 'nullable', 'integer'],
            'tags' => ['sometimes', 'nullable', 'string', 'max:255'],
            'created_from' => ['sometimes', 'nullable', 'date'],
            'created_to' => ['sometimes', 'nullable', 'date'],
            'last_activity_from' => ['sometimes', 'nullable', 'date'],
            'last_activity_to' => ['sometimes', 'nullable', 'date'],
            'value_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'value_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(CustomerService::SORTABLE)],
            'sort_direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);
    }
}
