<?php

namespace App\Http\Requests;

use App\Models\SavedFilter;
use App\Services\SavedFilterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('saved_filter')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SavedFilter $savedFilter */
        $savedFilter = $this->route('saved_filter');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('saved_filters', 'name')
                    ->ignore($savedFilter->id)
                    ->where(fn ($query) => $query
                        ->where('organization_id', $savedFilter->organization_id)
                        ->where('entity_type', $savedFilter->entity_type)
                        ->where('created_by', $savedFilter->created_by)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'string', Rule::in(['private', 'shared'])],
            'redirect_route' => ['required', 'string', 'max:255'],
            'update_filter_definition' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function filterDefinition(): ?array
    {
        if (! $this->boolean('update_filter_definition')) {
            return null;
        }

        /** @var SavedFilter $savedFilter */
        $savedFilter = $this->route('saved_filter');

        return app(SavedFilterService::class)->definitionFromIndexInput(
            $savedFilter->entity_type,
            $this->all(),
        );
    }
}
