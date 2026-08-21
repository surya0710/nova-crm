<?php

namespace App\Http\Requests\Concerns;

use App\Services\TenantContext;
use Closure;
use Illuminate\Validation\Rule;

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
        $organizationId = app(TenantContext::class)->id();

        $rules = [
            'email' => ['required', $this->recipientsRule(required: true)],
            'cc' => ['nullable', $this->recipientsRule()],
            'bcc' => ['nullable', $this->recipientsRule()],
            'message' => ['nullable', 'string', 'max:20000'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('crm_email_templates', 'id')->where(function ($query) use ($organizationId) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                    if ($organizationId) {
                        $query->where('organization_id', $organizationId);
                    }
                }),
            ],
            'include_signature' => ['nullable', 'boolean'],
            'in_reply_to' => ['nullable', 'string', 'max:255'],
            'thread_id' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];

        if ($requireSubject) {
            $rules['subject'] = ['required', 'string', 'max:255'];
        } else {
            $rules['subject'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function recipientsRule(bool $required = false): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($required): void {
            if ($value === null || $value === '') {
                if ($required) {
                    $fail(__('Enter at least one valid email address.'));
                }

                return;
            }

            $raw = is_array($value) ? $value : (preg_split('/[,;]+/', (string) $value) ?: []);
            $valid = 0;

            foreach ($raw as $entry) {
                $email = trim((string) $entry);
                if ($email === '') {
                    continue;
                }

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail(__('Each recipient must be a valid email address.'));

                    return;
                }

                $valid++;
            }

            if ($required && $valid === 0) {
                $fail(__('Enter at least one valid email address.'));
            }
        };
    }

    protected function ccRecipientsRule(): Closure
    {
        return $this->recipientsRule();
    }
}
