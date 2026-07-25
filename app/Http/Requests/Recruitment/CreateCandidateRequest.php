<?php

namespace App\Http\Requests\Recruitment;

use App\Models\Candidate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Candidate::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $maxKb = (int) config('hrms.recruitment.documents.max_size_kb', 10240);

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('candidates', 'email')->where('organization_id', $org?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'current_designation' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:100'],
            'notice_period' => ['nullable', 'string', 'max:100'],
            'current_salary' => ['nullable', 'numeric', 'min:0'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'linkedin' => ['nullable', 'string', 'max:500'],
            'portfolio' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.candidate_sources', [])))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'resume' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }
}
