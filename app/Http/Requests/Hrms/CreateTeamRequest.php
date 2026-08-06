<?php

namespace App\Http\Requests\Hrms;

use App\Models\HrmsTeam;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HrmsTeam::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_teams', 'code')->where('organization_id', $org?->id)],
            'department_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'team_lead_employee_id' => ['nullable', Rule::exists('employees', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
