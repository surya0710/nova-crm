<?php

namespace App\Http\Requests;

use App\Models\Program;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachProjectToProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return $program instanceof Program
            && ($this->user()?->can('attachProject', $program) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
