<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiMetadataQuery;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiCustomerRequest extends FormRequest
{
    use ValidatesApiMetadataQuery;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customers.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->metadataQueryRules(), [
            'search' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
