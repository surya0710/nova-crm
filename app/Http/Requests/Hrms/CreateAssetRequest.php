<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeAsset::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'asset_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(config('hrms.asset_categories', [])))],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(config('hrms.asset_statuses', [])))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
