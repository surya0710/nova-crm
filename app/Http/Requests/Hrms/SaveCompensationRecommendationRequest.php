<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class SaveCompensationRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appraisal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'increment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bonus_recommendation' => ['nullable', 'numeric', 'min:0'],
            'equity_recommendation' => ['nullable', 'numeric', 'min:0'],
            'adjustment_notes' => ['nullable', 'string'],
        ];
    }
}
