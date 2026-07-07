<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead && ($this->user()?->can('update', $lead) ?? false);
    }

    public function rules(): array
    {
        return [
            'next_follow_up_at' => ['nullable', 'date'],
            'next_follow_up_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('next_follow_up_at') && $this->input('next_follow_up_at') === '') {
            $this->merge([
                'next_follow_up_at' => null,
                'next_follow_up_note' => null,
            ]);
        }
    }
}
