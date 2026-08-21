<?php

namespace App\Http\Requests;

use App\Models\CrmEmailMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiCrmEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CrmEmailMessage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'related_type' => ['required', 'string', Rule::in(array_keys(config('crm_email.related_types', [])))],
            'related_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'string', 'max:2000'],
            'cc' => ['nullable', 'string', 'max:2000'],
            'bcc' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:20000'],
            'template_id' => ['nullable', 'integer'],
            'include_signature' => ['nullable', 'boolean'],
        ];
    }
}
