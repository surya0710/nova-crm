<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeEmergencyContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'alternate_mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
