<?php

namespace App\Http\Requests\Hrms;

use App\Models\TaxDeclaration;
use Illuminate\Foundation\Http\FormRequest;

class RejectTaxDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $declaration = $this->route('declaration');

        return $declaration instanceof TaxDeclaration
            && ($this->user()?->can('reject', $declaration) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
