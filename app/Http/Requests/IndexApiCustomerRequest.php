<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use App\Http\Requests\Concerns\ValidatesGeographicFilters;
use Illuminate\Foundation\Http\FormRequest;

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
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
