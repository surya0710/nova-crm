<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldGroup;
use App\Models\MetadataFieldOption;
use App\Models\MetadataFieldVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MetadataFieldDefinitionService
{
    public function __construct(
        protected MetadataProjectionService $projection,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $actor): MetadataFieldDefinition
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $field = MetadataFieldDefinition::query()->create([
                ...$this->definitionPayload($organization, $data),
                'status' => 'draft',
                'source' => $data['source'] ?? 'manual',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncOptions($field, $data['options'] ?? []);
            $this->snapshot($field->fresh(['options']), 'created', $actor);

            return $field->fresh(['group', 'options', 'versions']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MetadataFieldDefinition $field, array $data, User $actor): MetadataFieldDefinition
    {
        return DB::transaction(function () use ($field, $data, $actor) {
            $payload = $this->definitionPayload($field->organization, $data, $field);
            $shouldRebuildProjections = $this->shouldRebuildProjectionsAfterUpdate($field, $payload);

            if (! $field->isDraft()) {
                unset($payload['key'], $payload['type'], $payload['entity_type']);
            }

            $field->update([
                ...$payload,
                'updated_by' => $actor->id,
            ]);

            if ($field->isOptionBacked()) {
                $this->syncOptions($field, $data['options'] ?? []);
            } else {
                $field->options()->delete();
            }

            $this->snapshot($field->fresh(['options']), 'updated', $actor);

            $field = $field->fresh(['group', 'options', 'versions']);

            if ($shouldRebuildProjections) {
                $this->projection->rebuildForField($field);
            }

            return $field;
        });
    }

    public function publish(MetadataFieldDefinition $field, User $actor): MetadataFieldDefinition
    {
        if ($field->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => __('Only draft fields can be published.'),
            ]);
        }

        return $this->transition($field, 'published', 'published', $actor, [
            'published_at' => now(),
        ]);
    }

    public function activate(MetadataFieldDefinition $field, User $actor): MetadataFieldDefinition
    {
        if (! in_array($field->status, ['published', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'status' => __('Only published or inactive fields can be activated.'),
            ]);
        }

        return $this->transition($field, 'active', 'activated', $actor, [
            'activated_at' => now(),
        ]);
    }

    public function deactivate(MetadataFieldDefinition $field, User $actor): MetadataFieldDefinition
    {
        if ($field->status !== 'active') {
            throw ValidationException::withMessages([
                'status' => __('Only active fields can be deactivated.'),
            ]);
        }

        return $this->transition($field, 'inactive', 'deactivated', $actor);
    }

    public function archive(MetadataFieldDefinition $field, User $actor): MetadataFieldDefinition
    {
        if ($field->status === 'archived') {
            return $field;
        }

        return $this->transition($field, 'archived', 'archived', $actor, [
            'archived_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function transition(MetadataFieldDefinition $field, string $status, string $event, User $actor, array $extra = []): MetadataFieldDefinition
    {
        return DB::transaction(function () use ($field, $status, $event, $actor, $extra) {
            $field->update([
                ...$extra,
                'status' => $status,
                'updated_by' => $actor->id,
            ]);

            $this->snapshot($field->fresh(['options']), $event, $actor);

            $field = $field->fresh(['group', 'options', 'versions']);

            if ($status === 'active') {
                $this->projection->rebuildForField($field);
            }

            return $field;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function shouldRebuildProjectionsAfterUpdate(MetadataFieldDefinition $field, array $payload): bool
    {
        if ($field->status !== 'active' && ($payload['status'] ?? $field->status) !== 'active') {
            return false;
        }

        foreach (['is_filterable', 'is_sortable', 'is_searchable'] as $capability) {
            if (array_key_exists($capability, $payload) && ! $field->{$capability} && (bool) $payload[$capability]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function definitionPayload(Organization $organization, array $data, ?MetadataFieldDefinition $field = null): array
    {
        $entityType = $data['entity_type'] ?? $field?->entity_type;
        $group = $this->resolveGroup($organization, $entityType, $data);

        return [
            'organization_id' => $organization->id,
            'metadata_field_group_id' => $group?->id,
            'entity_type' => $entityType,
            'key' => Str::slug($data['key'] ?? $data['label'] ?? $field?->key, '_'),
            'label' => $data['label'] ?? $field?->label,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? $field?->type,
            'default_value' => $data['default_value'] ?? null,
            'validation_rules' => $data['validation_rules'] ?? [],
            'visibility_rules' => $data['visibility_rules'] ?? [],
            'display_rules' => $data['display_rules'] ?? [],
            'permission_rules' => $data['permission_rules'] ?? [],
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_unique' => (bool) ($data['is_unique'] ?? false),
            'is_searchable' => (bool) ($data['is_searchable'] ?? false),
            'is_filterable' => (bool) ($data['is_filterable'] ?? false),
            'is_sortable' => (bool) ($data['is_sortable'] ?? false),
            'is_reportable' => (bool) ($data['is_reportable'] ?? false),
            'is_exportable' => (bool) ($data['is_exportable'] ?? true),
            'is_api_visible' => (bool) ($data['is_api_visible'] ?? true),
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveGroup(Organization $organization, ?string $entityType, array $data): ?MetadataFieldGroup
    {
        $label = trim((string) ($data['group_label'] ?? ''));

        if ($label === '' || $entityType === null) {
            return null;
        }

        return MetadataFieldGroup::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'entity_type' => $entityType,
                'key' => Str::slug($label, '_'),
            ],
            [
                'label' => $label,
                'sort_order' => 0,
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function syncOptions(MetadataFieldDefinition $field, array $options): void
    {
        $keptIds = [];

        foreach (array_values($options) as $index => $option) {
            $value = trim((string) ($option['value'] ?? ''));
            $label = trim((string) ($option['label'] ?? ''));

            if ($value === '' || $label === '') {
                continue;
            }

            $record = MetadataFieldOption::query()->updateOrCreate(
                [
                    'metadata_field_definition_id' => $field->id,
                    'value' => $value,
                ],
                [
                    'organization_id' => $field->organization_id,
                    'label' => $label,
                    'sort_order' => (int) ($option['sort_order'] ?? $index),
                    'is_default' => (bool) ($option['is_default'] ?? false),
                    'is_active' => (bool) ($option['is_active'] ?? true),
                    'color' => $option['color'] ?? null,
                ],
            );

            $keptIds[] = $record->id;
        }

        $staleOptions = $field->options()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds));

        if ($field->isDraft()) {
            $staleOptions->delete();

            return;
        }

        $staleOptions->update(['is_active' => false]);
    }

    protected function snapshot(MetadataFieldDefinition $field, string $event, User $actor): MetadataFieldVersion
    {
        $version = ((int) $field->versions()->max('version')) + 1;

        return MetadataFieldVersion::query()->create([
            'organization_id' => $field->organization_id,
            'metadata_field_definition_id' => $field->id,
            'version' => $version,
            'event' => $event,
            'snapshot' => Arr::except($field->toArray(), ['versions']),
            'created_by' => $actor->id,
        ]);
    }
}
