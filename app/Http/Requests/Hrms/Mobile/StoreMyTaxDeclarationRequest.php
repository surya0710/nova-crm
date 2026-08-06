<?php

namespace App\Http\Requests\Hrms\Mobile;

use App\Models\TaxDeclaration;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMyTaxDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('create', TaxDeclaration::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $orgId = session('current_organization_id');

        return [
            'tax_financial_year_id' => [
                'nullable',
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
