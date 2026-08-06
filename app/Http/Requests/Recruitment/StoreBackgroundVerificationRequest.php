<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreBackgroundVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hiring_decision_id' => ['required', 'integer', 'exists:hiring_decisions,id'],
            'provider_slug' => ['nullable', 'string', 'max:80'],
        ];
    }
}
