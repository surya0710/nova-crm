<?php

namespace App\Http\Requests\Hrms;

use App\Models\Competency;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Competency::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'competency_category_id' => [
                'required', 'integer',
                Rule::exists('competency_categories', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('competencies', 'code')->where('organization_id', $org?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
