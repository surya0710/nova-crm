<?php

namespace App\Http\Requests\Hrms;

use App\Models\Competency;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Competency $competency */
        $competency = $this->route('competency');

        return $this->user()?->can('update', $competency) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        /** @var Competency $competency */
        $competency = $this->route('competency');

        return [
            'competency_category_id' => [
                'required', 'integer',
                Rule::exists('competency_categories', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('competencies', 'code')
                    ->where('organization_id', $org?->id)
                    ->ignore($competency->id),
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
