<?php

namespace App\Http\Requests;

use App\Models\ProjectMember;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectMember::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId),
            ],
            'project_role' => ['required', 'string', Rule::in(array_keys(config('projects.roles')))],
        ];
    }
}
