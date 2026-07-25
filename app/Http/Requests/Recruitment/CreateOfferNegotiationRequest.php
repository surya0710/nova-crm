<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferNegotiationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\OfferNegotiation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'offer_letter_id' => ['required', 'integer', 'exists:offer_letters,id'],
            'requested_salary' => ['nullable', 'numeric', 'min:0'],
            'requested_joining_date' => ['nullable', 'date'],
            'candidate_comments' => ['nullable', 'string'],
            'recruiter_notes' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('hrms.recruitment.negotiation_outcomes', [])))],
        ];
    }
}
