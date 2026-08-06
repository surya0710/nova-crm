<?php

namespace App\Http\Requests\Hrms;

class UpdateEmployeeRequest extends CreateEmployeeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'employee_code' => ['prohibited'],
        ];
    }
}
