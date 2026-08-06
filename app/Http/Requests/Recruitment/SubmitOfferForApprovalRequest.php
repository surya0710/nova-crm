<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOfferForApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('offer_letter')) ?? false;
    }

    public function rules(): array
    {
        return [
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
