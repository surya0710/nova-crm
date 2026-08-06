<?php

namespace App\Http\Requests\Hrms;

use App\Models\StatutoryRuleSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStatutoryRuleSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StatutoryRuleSet::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('statutory_rule_sets', 'code')->where(
                    fn ($q) => $q->where('organization_id', session('current_organization_id'))->whereNull('deleted_at')
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'jurisdiction' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:2000'],
            'version' => ['nullable', 'string', 'max:40'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
