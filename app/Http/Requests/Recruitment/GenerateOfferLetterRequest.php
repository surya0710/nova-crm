<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class GenerateOfferLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\OfferLetter::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'job_application_id' => ['required', 'integer', 'exists:job_applications,id'],
            'offer_template_id' => ['nullable', 'integer', 'exists:offer_templates,id'],
            'reporting_manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'proposed_salary' => ['required', 'numeric', 'min:0'],
            'variable_pay' => ['nullable', 'numeric', 'min:0'],
            'benefits' => ['nullable', 'string'],
            'joining_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after:joining_date'],
        ];
    }
}
