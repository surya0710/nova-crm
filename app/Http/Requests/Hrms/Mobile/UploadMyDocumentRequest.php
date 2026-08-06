<?php

namespace App\Http\Requests\Hrms\Mobile;

use App\Models\EmployeeDocument;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(EssContext::class)->requireEmployee($this->user());

        $user = $this->user();

        return $user !== null && (
            $user->can('manage', EmployeeDocument::class)
            || $user->hasPermission('ess.access')
        );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = (int) config('hrms.mobile.uploads.document.max_kb', config('hrms.documents.max_size_kb', 10240));
        $mimes = implode(',', config('hrms.mobile.uploads.document.mimes', ['pdf', 'jpeg', 'jpg', 'png']));

        return [
            'category' => ['required', 'string', Rule::in(array_keys(config('hrms.document_categories', [])))],
            'title' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];
    }
}
