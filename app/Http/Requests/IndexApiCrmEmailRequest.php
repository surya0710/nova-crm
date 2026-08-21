<?php

namespace App\Http\Requests;

use App\Models\CrmEmailMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexApiCrmEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CrmEmailMessage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(CrmEmailMessage::STATUSES)],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'contact_id' => ['sometimes', 'nullable', 'integer'],
            'template_id' => ['sometimes', 'nullable', 'integer'],
            'sent_by' => ['sometimes', 'nullable', 'integer'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return min(100, max(1, (int) $this->input('per_page', 20)));
    }
}
