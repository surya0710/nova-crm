<?php

namespace App\Http\Requests\Hrms\Mobile;

use App\Models\EmployeeTaxRegime;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectMyTaxRegimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('select', EmployeeTaxRegime::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'regime' => ['required', Rule::in(['old', 'new'])],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
