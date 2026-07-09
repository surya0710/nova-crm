<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldLayout;
use App\Models\MetadataFieldLayoutField;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MetadataFormResolver
{
    /**
     * Resolve active fields for an entity form/detail context.
     *
     * @return Collection<int, array{
     *     field: MetadataFieldDefinition,
     *     group: mixed,
     *     placement: ?MetadataFieldLayoutField,
     *     tab_key: ?string,
     *     section_key: ?string,
     *     group_key: ?string,
     *     width: string
     * }>
     */
    public function fieldsFor(Organization $organization, string $entityType, string $context = 'edit'): Collection
    {
        $layout = $this->defaultLayoutFor($organization, $entityType, $context);

        if ($layout) {
            $fields = $this->fieldsFromLayout($organization, $entityType, $layout);

            if ($fields->isNotEmpty()) {
                return $fields;
            }
        }

        return $this->fallbackFields($organization, $entityType);
    }

    protected function defaultLayoutFor(Organization $organization, string $entityType, string $context): ?MetadataFieldLayout
    {
        return MetadataFieldLayout::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('entity_type', $entityType)
            ->where('context', $context)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function fieldsFromLayout(Organization $organization, string $entityType, MetadataFieldLayout $layout): Collection
    {
        $placements = MetadataFieldLayoutField::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('metadata_field_layout_id', $layout->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('metadata_field_definition_id');

        if ($placements->isEmpty()) {
            return collect();
        }

        $definitions = $this->activeDefinitionQuery($organization, $entityType)
            ->whereIn('id', $placements->keys())
            ->get()
            ->keyBy('id');

        return $placements
            ->map(fn (MetadataFieldLayoutField $placement) => $definitions->get($placement->metadata_field_definition_id)
                ? $this->fieldItem($definitions->get($placement->metadata_field_definition_id), $placement)
                : null)
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function fallbackFields(Organization $organization, string $entityType): Collection
    {
        return $this->activeDefinitionQuery($organization, $entityType)
            ->get()
            ->sortBy([
                fn (MetadataFieldDefinition $field) => $field->group?->sort_order ?? PHP_INT_MAX,
                fn (MetadataFieldDefinition $field) => $field->sort_order,
                fn (MetadataFieldDefinition $field) => $field->label,
            ])
            ->map(fn (MetadataFieldDefinition $field) => $this->fieldItem($field))
            ->values();
    }

    protected function activeDefinitionQuery(Organization $organization, string $entityType): Builder
    {
        return MetadataFieldDefinition::withoutGlobalScopes()
            ->with([
                'group' => fn ($query) => $query->withoutGlobalScopes(),
                'options' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('label'),
            ])
            ->where('organization_id', $organization->id)
            ->where('entity_type', $entityType)
            ->where('status', 'active');
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldItem(MetadataFieldDefinition $field, ?MetadataFieldLayoutField $placement = null): array
    {
        return [
            'field' => $field,
            'group' => $field->group,
            'placement' => $placement,
            'tab_key' => $placement?->tab_key,
            'section_key' => $placement?->section_key ?? $field->group?->key,
            'group_key' => $placement?->group_key ?? $field->group?->key,
            'width' => $placement?->width ?? 'full',
        ];
    }
}
