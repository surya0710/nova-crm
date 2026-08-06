<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackPageViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Anonymous visitors are allowed; abuse is handled by throttling.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::in(['page_view'])],
            'url' => ['required', 'string', 'url', 'max:2048'],
            'landing_page' => ['nullable', 'string', 'url', 'max:2048'],
            'referrer' => ['nullable', 'string', 'url', 'max:2048'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
