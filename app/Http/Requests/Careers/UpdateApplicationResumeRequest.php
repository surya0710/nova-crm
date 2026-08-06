<?php

namespace App\Http\Requests\Careers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'candidate_resume_id' => ['required', 'integer', 'exists:candidate_resumes,id'],
        ];
    }
}
