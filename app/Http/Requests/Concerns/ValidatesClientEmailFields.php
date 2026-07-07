<?php

namespace App\Http\Requests\Concerns;

trait ValidatesClientEmailFields
{
    /**
     * @return array<string, mixed>
     */
    protected function clientEmailRules(bool $requireSubject = false): array
    {
        $maxFiles = (int) config('crm.email_attachments.max_files', 5);
        $maxKb = (int) config('crm.email_attachments.max_size_kb', 10240);
        $mimes = implode(',', config('crm.email_attachments.allowed_mimes', ['pdf']));

        $rules = [
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];

        if ($requireSubject) {
            $rules['subject'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
