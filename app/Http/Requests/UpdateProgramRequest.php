<?php

namespace App\Http\Requests;

use App\Models\Program;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            && ($this->user()?->can('update', $program) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        /** @var Program|null $program */
        $program = $this->route('program');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('programs', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($program?->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'portfolio_id' => [
                'nullable',
                'integer',
                Rule::exists('portfolios', 'id')->where('organization_id', $organizationId),
            ],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => [
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
