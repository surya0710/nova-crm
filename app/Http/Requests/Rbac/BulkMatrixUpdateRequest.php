<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class BulkMatrixUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('managePermissions', \App\Models\Permission::class);
    }

    public function rules(): array
    {
        return [
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
