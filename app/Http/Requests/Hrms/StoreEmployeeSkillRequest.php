<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    public function rules(): array
    {
        return [
            'skill' => ['required', 'string', 'max:255'],
            'proficiency' => ['required', Rule::in(array_keys(config('hrms.skill_proficiencies')))],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'last_used' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
