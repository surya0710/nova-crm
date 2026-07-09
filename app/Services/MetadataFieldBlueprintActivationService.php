<?php

namespace App\Services;

use App\Models\IndustryTemplateVersion;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldGroup;
use App\Models\MetadataFieldOption;
use App\Models\MetadataFieldVersion;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetadataFieldBlueprintActivationService
{
    /**
     * @return array<string, mixed>
     */
    public function activateCopiedBlueprints(Organization $organization, ?IndustryTemplateVersion $version = null): array
    {
        $blueprints = $organization->settings['field_blueprints'] ?? [];

        return $this->activate($organization, is_array($blueprints) ? $blueprints : [], $version);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blueprints
     * @return array<string, mixed>
     */
    public function activate(Organization $organization, array $blueprints, ?IndustryTemplateVersion $version = null): array
    {
        $summary = [
            'total' => count($blueprints),
            'activated' => 0,
            'skipped' => 0,
            'conflicts' => [],
            'unsupported' => [],
            'field_ids' => [],
        ];

        if ($blueprints === []) {
            return $summary;
        }

        return DB::transaction(function () use ($organization, $blueprints, $version, $summary) {
            foreach ($blueprints as $index => $blueprint) {
                $normalized = $this->normalizeBlueprint($blueprint);

                if (! $this->isSupported($normalized)) {
                    $summary['skipped']++;
                    $summary['unsupported'][] = [
                        'index' => $index,
                        'entity' => $normalized['entity_type'] ?? null,
                        'key' => $normalized['key'] ?? null,
                        'type' => $normalized['type'] ?? null,
                    ];

                    continue;
                }

                $existing = MetadataFieldDefinition::withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('entity_type', $normalized['entity_type'])
                    ->where('key', $normalized['key'])
                    ->first();

                if ($existing) {
                    $summary['skipped']++;
                    $summary['conflicts'][] = [
                        'index' => $index,
                        'entity' => $normalized['entity_type'],
                        'key' => $normalized['key'],
                        'field_id' => $existing->id,
                    ];

                    continue;
                }

                $group = $this->resolveGroup($organization, $normalized);
                $now = now();

                $field = MetadataFieldDefinition::withoutEvents(fn () => MetadataFieldDefinition::withoutGlobalScopes()->create([
                    'organization_id' => $organization->id,
                    'metadata_field_group_id' => $group?->id,
                    'entity_type' => $normalized['entity_type'],
                    'key' => $normalized['key'],
                    'label' => $normalized['label'],
                    'description' => $normalized['description'],
                    'type' => $normalized['type'],
                    'status' => $normalized['is_active_by_default'] ? 'active' : 'published',
                    'default_value' => $normalized['default_value'],
                    'validation_rules' => $normalized['validation_rules'],
                    'visibility_rules' => $normalized['visibility_rules'],
                    'display_rules' => $normalized['display_rules'],
                    'permission_rules' => $normalized['permission_rules'],
                    'is_required' => $normalized['is_required'],
                    'is_unique' => $normalized['is_unique'],
                    'is_searchable' => $normalized['is_searchable'],
                    'is_filterable' => $normalized['is_filterable'],
                    'is_sortable' => $normalized['is_sortable'],
                    'is_reportable' => $normalized['is_reportable'],
                    'is_exportable' => $normalized['is_exportable'],
                    'is_api_visible' => $normalized['is_api_visible'],
                    'is_sensitive' => $normalized['is_sensitive'],
                    'sort_order' => $normalized['sort_order'],
                    'source' => 'industry_template',
                    'source_type' => $version ? IndustryTemplateVersion::class : null,
                    'source_identifier' => $version?->id,
                    'published_at' => $now,
                    'activated_at' => $normalized['is_active_by_default'] ? $now : null,
                ]));

                $this->createOptions($field, $normalized['options']);
                $this->snapshot($field->fresh(['options']), 'blueprint_activated');

                $summary['activated']++;
                $summary['field_ids'][] = $field->id;
            }

            return $summary;
        });
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @return array<string, mixed>
     */
    protected function normalizeBlueprint(array $blueprint): array
    {
        $key = Str::slug((string) ($blueprint['key'] ?? $blueprint['label'] ?? ''), '_');
        $entity = (string) ($blueprint['entity'] ?? $blueprint['entity_type'] ?? '');

        return [
            'entity_type' => $entity,
            'key' => $key,
            'label' => (string) ($blueprint['label'] ?? Str::headline($key)),
            'description' => $blueprint['description'] ?? $blueprint['help_text'] ?? null,
            'type' => (string) ($blueprint['type'] ?? 'text'),
            'group_label' => $blueprint['group'] ?? $blueprint['group_label'] ?? null,
            'default_value' => $blueprint['default'] ?? $blueprint['default_value'] ?? null,
            'validation_rules' => $blueprint['validation'] ?? $blueprint['validation_rules'] ?? [],
            'visibility_rules' => $blueprint['visibility'] ?? $blueprint['visibility_rules'] ?? [],
            'display_rules' => $blueprint['display'] ?? $blueprint['display_rules'] ?? [],
            'permission_rules' => $blueprint['permissions'] ?? $blueprint['permission_rules'] ?? [],
            'is_required' => (bool) ($blueprint['required'] ?? $blueprint['is_required'] ?? false),
            'is_unique' => (bool) ($blueprint['unique'] ?? $blueprint['is_unique'] ?? false),
            'is_searchable' => (bool) ($blueprint['searchable'] ?? $blueprint['is_searchable'] ?? false),
            'is_filterable' => (bool) ($blueprint['filterable'] ?? $blueprint['is_filterable'] ?? false),
            'is_sortable' => (bool) ($blueprint['sortable'] ?? $blueprint['is_sortable'] ?? false),
            'is_reportable' => (bool) ($blueprint['reportable'] ?? $blueprint['is_reportable'] ?? false),
            'is_exportable' => (bool) ($blueprint['exportable'] ?? $blueprint['is_exportable'] ?? true),
            'is_api_visible' => (bool) ($blueprint['api_visible'] ?? $blueprint['is_api_visible'] ?? true),
            'is_sensitive' => (bool) ($blueprint['sensitive'] ?? $blueprint['is_sensitive'] ?? false),
            'is_active_by_default' => (bool) ($blueprint['is_active_by_default'] ?? true),
            'sort_order' => (int) ($blueprint['order'] ?? $blueprint['sort_order'] ?? 0),
            'options' => $this->normalizeOptions($blueprint['options'] ?? []),
        ];
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeOptions(array $options): array
    {
        return collect($options)
            ->map(function ($option, int $index) {
                if (is_string($option)) {
                    return [
                        'value' => Str::slug($option, '_'),
                        'label' => $option,
                        'sort_order' => $index,
                    ];
                }

                if (! is_array($option)) {
                    return null;
                }

                $label = (string) ($option['label'] ?? $option['value'] ?? '');
                $value = Str::slug((string) ($option['value'] ?? $label), '_');

                if ($label === '' || $value === '') {
                    return null;
                }

                return [
                    'value' => $value,
                    'label' => $label,
                    'sort_order' => (int) ($option['sort_order'] ?? $option['order'] ?? $index),
                    'is_default' => (bool) ($option['is_default'] ?? false),
                    'is_active' => (bool) ($option['is_active'] ?? true),
                    'color' => $option['color'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    protected function isSupported(array $blueprint): bool
    {
        return in_array($blueprint['entity_type'] ?? null, array_keys(config('metadata.entities')), true)
            && in_array($blueprint['type'] ?? null, array_keys(config('metadata.field_types')), true)
            && filled($blueprint['key'] ?? null)
            && filled($blueprint['label'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    protected function resolveGroup(Organization $organization, array $blueprint): ?MetadataFieldGroup
    {
        $label = trim((string) ($blueprint['group_label'] ?? ''));

        if ($label === '') {
            return null;
        }

        return MetadataFieldGroup::withoutEvents(fn () => MetadataFieldGroup::withoutGlobalScopes()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'entity_type' => $blueprint['entity_type'],
                'key' => Str::slug($label, '_'),
            ],
            [
                'label' => $label,
                'sort_order' => 0,
            ],
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function createOptions(MetadataFieldDefinition $field, array $options): void
    {
        foreach ($options as $option) {
            MetadataFieldOption::withoutGlobalScopes()->create([
                'organization_id' => $field->organization_id,
                'metadata_field_definition_id' => $field->id,
                'value' => $option['value'],
                'label' => $option['label'],
                'sort_order' => $option['sort_order'] ?? 0,
                'is_default' => $option['is_default'] ?? false,
                'is_active' => $option['is_active'] ?? true,
                'color' => $option['color'] ?? null,
            ]);
        }
    }

    protected function snapshot(MetadataFieldDefinition $field, string $event): MetadataFieldVersion
    {
        return MetadataFieldVersion::withoutGlobalScopes()->create([
            'organization_id' => $field->organization_id,
            'metadata_field_definition_id' => $field->id,
            'version' => 1,
            'event' => $event,
            'snapshot' => $field->toArray(),
            'created_by' => null,
        ]);
    }
}
