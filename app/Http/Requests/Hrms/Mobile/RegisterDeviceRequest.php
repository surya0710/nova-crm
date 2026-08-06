<?php

namespace App\Http\Requests\Hrms\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'string', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', Rule::in(['ios', 'android', 'web', 'other'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ];
    }
}
