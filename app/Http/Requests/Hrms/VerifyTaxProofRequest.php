<?php

namespace App\Http\Requests\Hrms;

use App\Models\TaxProof;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyTaxProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proof = $this->route('proof');

        return $proof instanceof TaxProof
            && ($this->user()?->can('verify', $proof) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['verified', 'partial'])],
        ];
    }
}
