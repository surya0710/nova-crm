<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof EmployeeDocument
            && ($this->user()?->can('manage', $document) ?? false);
    }

    public function rules(): array
    {
        return [
            'verification_status' => [
                'required',
                'string',
                Rule::in(array_keys(config('hrms.document_verification_statuses', []))),
                Rule::notIn(['pending']),
            ],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
