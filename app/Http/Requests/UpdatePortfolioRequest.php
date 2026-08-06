<?php

namespace App\Http\Requests;

use App\Models\Portfolio;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portfolio = $this->route('portfolio');

        return $portfolio instanceof Portfolio
            && ($this->user()?->can('update', $portfolio) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        /** @var Portfolio|null $portfolio */
        $portfolio = $this->route('portfolio');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('portfolios', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($portfolio?->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
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
            'settings' => ['nullable', 'array'],
        ];
    }
}
