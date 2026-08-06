<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SyncRecruitmentCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'interview_round_id' => ['required', 'integer', 'exists:interview_rounds,id'],
            'recruitment_provider_id' => ['required', 'integer', 'exists:recruitment_providers,id'],
        ];
    }
}
