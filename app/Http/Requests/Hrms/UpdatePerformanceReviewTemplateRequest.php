<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceReviewTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceReviewTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceReviewTemplate $template */
        $template = $this->route('template');

        return $this->user()?->can('update', $template) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        /** @var PerformanceReviewTemplate $template */
        $template = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('performance_review_templates', 'code')
                    ->where('organization_id', $org?->id)
                    ->ignore($template->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'sections' => ['sometimes', 'array'],
            'sections.*.name' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.key' => ['nullable', 'string', 'max:50'],
            'sections.*.instructions' => ['nullable', 'string', 'max:2000'],
            'sections.*.weightage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'competencies' => ['sometimes', 'array'],
            'competencies.*.competency_id' => [
                'required_with:competencies', 'integer',
                Rule::exists('competencies', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'competencies.*.section_key' => ['nullable', 'string', 'max:50'],
            'competencies.*.section_name' => ['nullable', 'string', 'max:255'],
            'competencies.*.weightage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'competencies.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $competencies = collect($this->input('competencies', []))
            ->filter(fn ($row) => filled($row['competency_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'competencies' => $competencies,
        ]);
    }
}
