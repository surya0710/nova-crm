<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('offer_letter')) ?? false;
    }

    public function rules(): array
    {
        return [
            'proposed_salary' => ['sometimes', 'numeric', 'min:0'],
            'variable_pay' => ['nullable', 'numeric', 'min:0'],
            'benefits' => ['nullable', 'string'],
            'joining_date' => ['sometimes', 'date'],
            'expiry_date' => ['sometimes', 'date'],
        ];
    }
}
