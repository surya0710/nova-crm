<?php

namespace App\Http\Requests\Hrms;

use App\Models\Form16Record;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateForm16Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generate', Form16Record::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $orgId = session('current_organization_id');

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'tax_financial_year_id' => [
                'required',
                'integer',
                Rule::exists('tax_financial_years', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
        ];
    }
}
