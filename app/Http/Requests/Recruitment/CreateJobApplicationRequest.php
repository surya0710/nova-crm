<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobApplication;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobApplication::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'candidate_id' => [
                'required', 'integer',
                Rule::exists('candidates', 'id')->where('organization_id', $org?->id),
            ],
            'job_opening_id' => [
                'required', 'integer',
                Rule::exists('job_openings', 'id')->where('organization_id', $org?->id),
            ],
            'applied_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.candidate_sources', [])))],
            'assigned_recruiter_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id'),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
