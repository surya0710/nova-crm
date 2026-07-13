<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiLeadRequest extends FormRequest
{
    use ValidatesApiMetadataQuery;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('leads.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->metadataQueryRules(), [
            'status' => ['sometimes', 'string', 'max:50'],
        ]);
    }
}
