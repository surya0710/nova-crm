<?php

namespace App\Http\Requests\Hrms;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('team')) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $team = $this->route('team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_teams', 'code')->where('organization_id', $org?->id)->ignore($team?->id)],
            'department_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'team_lead_employee_id' => ['nullable', Rule::exists('employees', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
