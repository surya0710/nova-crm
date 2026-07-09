<?php

namespace App\Http\Requests;

use App\Models\MetadataFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMetadataFieldDefinitionRequest extends StoreMetadataFieldDefinitionRequest
{
    public function authorize(): bool
    {
        $organization = app(TenantContext::class)->get();

        return $organization && ($this->user()?->hasPermission('metadata.update', $organization) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $field = $this->route('metadata_field');

        if ($field instanceof MetadataFieldDefinition) {
            $rules['key'] = [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('metadata_field_definitions', 'key')
                    ->where('organization_id', $field->organization_id)
                    ->where('entity_type', $this->input('entity_type'))
                    ->ignore($field->id),
            ];

            if (! $field->isDraft()) {
                $rules['entity_type'] = ['required', 'string', Rule::in([$field->entity_type])];
                $rules['type'] = ['required', 'string', Rule::in([$field->type])];
                $rules['key'] = ['required', 'string', Rule::in([$field->key])];
            }
        }

        return $rules;
    }
}
