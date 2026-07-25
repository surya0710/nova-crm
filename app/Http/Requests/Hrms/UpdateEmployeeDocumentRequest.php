<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof EmployeeDocument
            && ($this->user()?->can('manage', $document) ?? false);
    }

    public function rules(): array
    {
        $maxKb = (int) config('hrms.documents.max_size_kb', 10240);
        $mimes = implode(',', config('hrms.documents.allowed_mimes', ['pdf']));

        return [
            'category' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('hrms.document_categories', [])))],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];
    }
}
