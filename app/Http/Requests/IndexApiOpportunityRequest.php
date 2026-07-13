<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiOpportunityRequest extends FormRequest
{
    use ValidatesApiMetadataQuery;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('opportunities.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->metadataQueryRules(), [
            'stage' => ['sometimes', 'string', 'max:50'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
