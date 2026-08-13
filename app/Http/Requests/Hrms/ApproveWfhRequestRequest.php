<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class ApproveWfhRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wfhRequest = $this->route('wfh_request');

        return $this->user()?->can('approve', $wfhRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
