<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', EmployeeDocument::class) ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('hrms.documents.max_size_kb', 10240);
        $mimes = implode(',', config('hrms.documents.allowed_mimes', ['pdf']));

        return [
            'category' => ['required', 'string', Rule::in(array_keys(config('hrms.document_categories', [])))],
            'title' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];
    }
}
