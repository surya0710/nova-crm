<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationQuotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'users' => ['nullable', 'integer', 'min:0'],
            'storage_mb' => ['nullable', 'integer', 'min:0'],
            'api_requests_per_day' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
