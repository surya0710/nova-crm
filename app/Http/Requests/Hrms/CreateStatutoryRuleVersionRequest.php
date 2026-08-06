<?php

namespace App\Http\Requests\Hrms;

use App\Models\StatutoryRuleSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateStatutoryRuleVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var StatutoryRuleSet $ruleSet */
        $ruleSet = $this->route('ruleSet');

        return $this->user()?->can('update', $ruleSet) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:40'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'jurisdiction' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
            'configuration_json' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $json = trim((string) $this->input('configuration_json', ''));
            if ($json === '') {
                return;
            }

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                $validator->errors()->add('configuration_json', 'Configuration must be valid JSON.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /** @return array<string, mixed> */
    public function statutoryPayload(): array
    {
        $data = $this->validated();
        $json = trim((string) ($data['configuration_json'] ?? ''));
        unset($data['configuration_json']);

        if ($json !== '') {
            $data['configuration'] = json_decode($json, true);
        }

        return $data;
    }
}
