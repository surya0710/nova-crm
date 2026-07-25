<?php

namespace App\Http\Requests;

use App\Models\SavedFilter;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SavedFilter::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'entity_type' => ['required', 'string', Rule::in(['lead', 'customer', 'opportunity'])],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('saved_filters', 'name')
                    ->where(fn ($query) => $query
                        ->where('organization_id', $organization?->id)
                        ->where('entity_type', $this->input('entity_type'))
                        ->where('created_by', $this->user()?->id)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'string', Rule::in(['private', 'shared'])],
            'redirect_route' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filterDefinition(): array
    {
        return app(SavedFilterService::class)->definitionFromIndexInput(
            $this->string('entity_type')->toString(),
            $this->all(),
        );
    }
}
