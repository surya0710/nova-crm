<?php

namespace App\Http\Requests;

use App\Services\MetadataValidationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMetadataFieldDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = app(TenantContext::class)->get();

        return $organization && ($this->user()?->hasPermission('metadata.create', $organization) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => str($this->input('key') ?: $this->input('label'))->slug('_')->toString(),
            'options' => $this->parseOptions($this->input('options_text')),
            'validation_rules' => $this->decodeJsonObject('validation_rules_json'),
            'visibility_rules' => $this->decodeJsonObject('visibility_rules_json'),
            'display_rules' => $this->decodeJsonObject('display_rules_json'),
            'permission_rules' => $this->decodeJsonObject('permission_rules_json'),
        ]);
    }

    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        $entityTypes = array_keys(config('metadata.entities'));
        $fieldTypes = array_keys(config('metadata.field_types'));
        $optionTypes = config('metadata.option_field_types');

        return [
            'entity_type' => ['required', 'string', Rule::in($entityTypes)],
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('metadata_field_definitions', 'key')
                    ->where('organization_id', $organizationId)
                    ->where('entity_type', $this->input('entity_type')),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($fieldTypes)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_sortable' => ['nullable', 'boolean'],
            'is_reportable' => ['nullable', 'boolean'],
            'is_exportable' => ['nullable', 'boolean'],
            'is_api_visible' => ['nullable', 'boolean'],
            'is_sensitive' => ['nullable', 'boolean'],
            'options_text' => [Rule::requiredIf(fn () => in_array($this->input('type'), $optionTypes, true)), 'nullable', 'string'],
            'options' => ['array'],
            'options.*.value' => ['required_with:options', 'string', 'max:150', 'distinct'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'validation_rules_json' => ['nullable', 'json'],
            'visibility_rules_json' => ['nullable', 'json'],
            'display_rules_json' => ['nullable', 'json'],
            'permission_rules_json' => ['nullable', 'json'],
            'validation_rules' => ['array'],
            'visibility_rules' => ['array'],
            'display_rules' => ['array'],
            'permission_rules' => ['array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $errors = app(MetadataValidationService::class)->validationRuleSchemaErrors(
                $this->input('validation_rules', []),
                $this->input('type'),
            );

            foreach ($errors as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                    $validator->errors()->add('validation_rules_json', $message);
                }
            }
        });
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function parseOptions(mixed $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                [$value, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

                return [
                    'value' => str($value)->slug('_')->toString(),
                    'label' => $label ?: $value,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonObject(string $key): array
    {
        $value = $this->input($key);

        if (! filled($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
