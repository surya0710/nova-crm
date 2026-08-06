<?php

namespace App\Http\Requests\Hrms;

use App\Models\TaxDeclaration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaxDeclaration::class) ?? false;
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['required', 'string', 'max:50'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.declared_amount' => ['required', 'numeric', 'min:0'],
            'items.*.section' => ['nullable', 'string', 'max:30'],
        ];
    }
}
