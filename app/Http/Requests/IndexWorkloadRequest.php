<?php

namespace App\Http\Requests;

use App\Models\WorkloadSnapshot;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWorkloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', WorkloadSnapshot::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'date' => ['sometimes', 'date'],
            'employee_id' => [
                'sometimes',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
