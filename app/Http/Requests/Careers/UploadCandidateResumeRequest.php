<?php

namespace App\Http\Requests\Careers;

use Illuminate\Foundation\Http\FormRequest;

class UploadCandidateResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = config('hrms.recruitment.portal.resume_max_kb', 5120);

        return [
            'name' => ['required', 'string', 'max:120'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.$maxKb],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
