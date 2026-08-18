<?php

namespace App\Http\Requests\Concerns;

use Closure;

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
            'cc' => ['nullable', $this->ccRecipientsRule()],
            'message' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];

        if ($requireSubject) {
            $rules['subject'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function ccRecipientsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $raw = is_array($value) ? $value : (preg_split('/[,;]+/', (string) $value) ?: []);

            foreach ($raw as $entry) {
                $email = trim((string) $entry);
                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail(__('Each CC recipient must be a valid email address.'));

                    return;
                }
            }
        };
    }
}
