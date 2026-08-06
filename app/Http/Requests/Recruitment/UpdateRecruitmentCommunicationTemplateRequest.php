<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruitmentCommunicationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', Rule::in(config('recruitment.communication.template_keys', []))],
            'name' => ['sometimes', 'string', 'max:255'],
            'channel' => ['sometimes', 'string', Rule::in(config('recruitment.communication.channels', []))],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
        ];
    }
}
