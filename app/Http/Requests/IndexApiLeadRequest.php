<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use App\Http\Requests\Concerns\ValidatesGeographicFilters;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiLeadRequest extends FormRequest
{
    use ValidatesApiMetadataQuery;
    use ValidatesGeographicFilters;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leads.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->metadataQueryRules(), $this->geographicFilterRules(), [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
        ]);
    }
}
