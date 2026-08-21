<?php

namespace App\Services;

use App\Data\MetadataQueryRequest;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\SavedFilter;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Theme\ThemeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SavedFilterService
{
    protected array $staticFilterKeys = [
        'lead' => ['search', 'status', 'source', 'priority', 'assigned_to', 'state', 'country'],
        'customer' => [
            'search', 'status', 'type', 'lifecycle_stage', 'segment', 'source',
            'industry', 'assigned_to', 'state', 'country', 'tags',
            'created_from', 'created_to', 'last_activity_from', 'last_activity_to',
            'value_min', 'value_max', 'sort', 'sort_direction',
        ],
        'opportunity' => ['search', 'stage', 'customer_id', 'assigned_to', 'source'],
        'ticket' => [
            'search', 'status', 'priority', 'customer_id', 'contact_id', 'assigned_to',
            'overdue', 'unassigned', 'sort', 'sort_direction',
        ],
    ];

    public function __construct(
        protected MetadataQueryDefinitionService $definitions,
        protected MetadataQueryService $queries,
    ) {}

    public function create(int $organizationId, User $user, string $entityType, array $data): SavedFilter
    {
        $this->assertSupportedEntityType($entityType);

        $definition = $this->normalizeDefinition($entityType, $data['filter_definition'] ?? $data);
        $validation = $this->validateDefinition($organizationId, $entityType, $definition);

        return SavedFilter::query()->create([
            'organization_id' => $organizationId,
            'entity_type' => $entityType,
            'name' => trim((string) $data['name']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'filter_definition' => $definition,
            'visibility' => $this->normalizeVisibility($data['visibility'] ?? 'private'),
            'validation_status' => $validation['status'],
            'validation_errors' => $validation['errors'] === [] ? null : $validation['errors'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function update(SavedFilter $filter, User $user, array $data): SavedFilter
    {
        $definition = array_key_exists('filter_definition', $data)
            ? $this->normalizeDefinition($filter->entity_type, $data['filter_definition'])
            : $filter->filter_definition;

        $validation = $this->validateDefinition($filter->organization_id, $filter->entity_type, $definition);

        $filter->fill([
            'name' => array_key_exists('name', $data) ? trim((string) $data['name']) : $filter->name,
            'description' => array_key_exists('description', $data)
                ? (filled($data['description']) ? trim((string) $data['description']) : null)
                : $filter->description,
            'filter_definition' => $definition,
            'visibility' => array_key_exists('visibility', $data)
                ? $this->normalizeVisibility($data['visibility'])
                : $filter->visibility,
            'validation_status' => $validation['status'],
            'validation_errors' => $validation['errors'] === [] ? null : $validation['errors'],
            'updated_by' => $user->id,
        ])->save();

        return $filter->fresh();
    }

    public function delete(SavedFilter $filter, User $user): void
    {
        $filter->delete();
    }

    public function duplicate(SavedFilter $filter, User $user, ?string $name = null): SavedFilter
    {
        $copyName = $name ?: $this->duplicateName($filter, $user);

        return $this->create($filter->organization_id, $user, $filter->entity_type, [
            'name' => $copyName,
            'description' => $filter->description,
            'visibility' => 'private',
            'filter_definition' => $filter->filter_definition,
        ]);
    }

    /**
     * @return array{status: string, errors: array<string, mixed>}
     */
    public function validateDefinition(int $organizationId, string $entityType, array $definition): array
    {
        $input = $this->inputFromDefinition($definition);
        $errors = [];
        $request = null;

        try {
            $request = $this->definitions->requestForWebIndex($organizationId, $entityType, $input);
            $this->queries->applyForWebIndex($this->emptyBuilder($entityType), $request, $organizationId);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        } catch (InvalidArgumentException $exception) {
            $errors = ['filter_definition' => [$exception->getMessage()]];
        }

        $storedFilterCount = count($definition['metadata_filters'] ?? []);
        $compiledFilterCount = $request ? count($request->filters) : 0;
        $storedSortKey = $definition['metadata_sort']['key'] ?? null;
        $compiledSortKey = $request?->sort?->key;

        if ($storedFilterCount > $compiledFilterCount) {
            $errors['metadata_filters'] = array_merge(
                $errors['metadata_filters'] ?? [],
                [__('One or more saved metadata filters are no longer available.')],
            );
        }

        if ($storedSortKey && $compiledSortKey !== $storedSortKey) {
            $errors['metadata_sort.key'] = array_merge(
                $errors['metadata_sort.key'] ?? [],
                [__('The saved metadata sort field is no longer available.')],
            );
        }

        $hasMetadataIntent = $this->hasMetadataIntent($definition);
        $hasStaticIntent = $this->hasStaticIntent($entityType, $definition);

        if ($errors !== []) {
            return [
                'status' => ($hasMetadataIntent || $hasStaticIntent) ? 'partial' : 'invalid',
                'errors' => $errors,
            ];
        }

        if (! $hasMetadataIntent && ! $hasStaticIntent) {
            return [
                'status' => 'invalid',
                'errors' => ['filter_definition' => [__('Saved filters must include at least one filter criterion.')]],
            ];
        }

        return [
            'status' => 'valid',
            'errors' => [],
        ];
    }

    public function refreshValidation(SavedFilter $filter): SavedFilter
    {
        $validation = $this->validateDefinition(
            $filter->organization_id,
            $filter->entity_type,
            $filter->filter_definition ?? [],
        );

        $filter->update([
            'validation_status' => $validation['status'],
            'validation_errors' => $validation['errors'] === [] ? null : $validation['errors'],
        ]);

        return $filter->fresh();
    }

    public function metadataQueryRequest(SavedFilter $filter): MetadataQueryRequest
    {
        $this->assertExecutable($filter);

        return $this->definitions->requestForWebIndex(
            $filter->organization_id,
            $filter->entity_type,
            $this->inputFromDefinition($filter->filter_definition ?? []),
        );
    }

    public function applyToBuilder(Builder $builder, SavedFilter $filter): Builder
    {
        $metadataRequest = $this->metadataQueryRequest($filter);

        return $this->queries->applyForWebIndex(
            $builder,
            $metadataRequest,
            $filter->organization_id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParameters(SavedFilter $filter): array
    {
        $parameters = $this->inputFromDefinition($filter->filter_definition ?? []);
        $parameters['saved_filter'] = $filter->id;

        return $parameters;
    }

    /**
     * @return array<string, mixed>
     */
    public function definitionFromIndexInput(string $entityType, array $input): array
    {
        $this->assertSupportedEntityType($entityType);

        return $this->normalizeDefinition($entityType, [
            'version' => 1,
            'static_filters' => Arr::only($input, $this->staticFilterKeys[$entityType]),
            'metadata_filters' => $input['metadata_filters'] ?? [],
            'metadata_sort' => $this->normalizeSortInput($input),
        ]);
    }

    /**
     * @return Collection<int, SavedFilter>
     */
    public function availableFor(User $user, int $organizationId, string $entityType): Collection
    {
        return SavedFilter::query()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhere('visibility', 'shared');
            })
            ->orderBy('name')
            ->get();
    }

    public function findAccessible(User $user, int $organizationId, int $savedFilterId, string $entityType): ?SavedFilter
    {
        $filter = SavedFilter::query()
            ->whereKey($savedFilterId)
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->first();

        if (! $filter) {
            return null;
        }

        if ($filter->isPrivate() && ! $filter->isOwnedBy($user)) {
            return null;
        }

        return $this->refreshValidation($filter);
    }

    public function defaultFor(User $user, int $organizationId, string $entityType): ?SavedFilter
    {
        $prefs = UserUiPreference::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();

        $savedFilterId = (int) data_get($prefs?->meta, "default_saved_filters.{$entityType}", 0);

        if ($savedFilterId < 1) {
            return null;
        }

        return $this->findAccessible($user, $organizationId, $savedFilterId, $entityType);
    }

    public function isDefaultFor(User $user, int $organizationId, SavedFilter $filter): bool
    {
        $default = $this->defaultFor($user, $organizationId, $filter->entity_type);

        return $default?->is($filter) ?? false;
    }

    public function setDefault(User $user, Organization $organization, SavedFilter $filter): void
    {
        $prefs = app(ThemeService::class)->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];
        $defaults = is_array($meta['default_saved_filters'] ?? null) ? $meta['default_saved_filters'] : [];
        $defaults[$filter->entity_type] = $filter->id;
        $meta['default_saved_filters'] = $defaults;
        $prefs->meta = $meta;
        $prefs->save();
    }

    public function clearDefault(User $user, Organization $organization, string $entityType): void
    {
        $prefs = app(ThemeService::class)->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];
        $defaults = is_array($meta['default_saved_filters'] ?? null) ? $meta['default_saved_filters'] : [];
        unset($defaults[$entityType]);
        $meta['default_saved_filters'] = $defaults;
        $prefs->meta = $meta;
        $prefs->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveIndexInput(User $user, int $organizationId, string $entityType, array $input): array
    {
        if (! $savedFilterId = (int) ($input['saved_filter'] ?? 0)) {
            return $input;
        }

        $filter = $this->findAccessible($user, $organizationId, $savedFilterId, $entityType);

        if (! $filter) {
            throw ValidationException::withMessages([
                'saved_filter' => __('The selected saved filter is not available.'),
            ]);
        }

        if ($filter->validation_status === 'invalid') {
            throw ValidationException::withMessages([
                'saved_filter' => __('The selected saved filter is no longer valid.'),
            ]);
        }

        $resolved = $this->queryParameters($filter);

        if (isset($input['page'])) {
            $resolved['page'] = $input['page'];
        }

        return $resolved;
    }

    protected function assertExecutable(SavedFilter $filter): void
    {
        $validation = $this->validateDefinition(
            $filter->organization_id,
            $filter->entity_type,
            $filter->filter_definition ?? [],
        );

        if ($validation['status'] === 'invalid') {
            throw ValidationException::withMessages(
                $validation['errors'] !== []
                    ? $validation['errors']
                    : ['saved_filter' => [__('The saved filter cannot be executed.')]],
            );
        }

        if ($filter->validation_status !== $validation['status']) {
            $filter->update([
                'validation_status' => $validation['status'],
                'validation_errors' => $validation['errors'] === [] ? null : $validation['errors'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function inputFromDefinition(array $definition): array
    {
        $input = $definition['static_filters'] ?? [];

        if (! empty($definition['metadata_filters'])) {
            $input['metadata_filters'] = $definition['metadata_filters'];
        }

        if (! empty($definition['metadata_sort'])) {
            $input['metadata_sort'] = $definition['metadata_sort'];
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function normalizeDefinition(string $entityType, array $definition): array
    {
        $this->assertSupportedEntityType($entityType);

        return [
            'version' => (int) ($definition['version'] ?? 1),
            'static_filters' => Arr::only(
                $definition['static_filters'] ?? [],
                $this->staticFilterKeys[$entityType],
            ),
            'metadata_filters' => array_values(array_filter(
                (array) ($definition['metadata_filters'] ?? []),
                fn ($filter) => is_array($filter) && filled($filter['key'] ?? null) && filled($filter['operator'] ?? null),
            )),
            'metadata_sort' => $this->normalizeSortDefinition($definition['metadata_sort'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>|null
     */
    protected function normalizeSortInput(array $input): ?array
    {
        $sort = $input['metadata_sort'] ?? null;

        if (is_array($sort) && filled($sort['key'] ?? null)) {
            return [
                'key' => trim((string) $sort['key']),
                'direction' => trim((string) ($sort['direction'] ?? 'asc')) ?: 'asc',
            ];
        }

        $legacyKey = trim((string) ($input['metadata_sort_key'] ?? ''));

        if ($legacyKey === '') {
            return null;
        }

        return [
            'key' => $legacyKey,
            'direction' => trim((string) ($input['metadata_sort_direction'] ?? 'asc')) ?: 'asc',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function normalizeSortDefinition(mixed $sort): ?array
    {
        if (! is_array($sort) || ! filled($sort['key'] ?? null)) {
            return null;
        }

        return [
            'key' => trim((string) $sort['key']),
            'direction' => trim((string) ($sort['direction'] ?? 'asc')) ?: 'asc',
        ];
    }

    protected function normalizeVisibility(string $visibility): string
    {
        return in_array($visibility, ['private', 'shared'], true) ? $visibility : 'private';
    }

    protected function duplicateName(SavedFilter $filter, User $user): string
    {
        $base = $filter->name.' '.__('(copy)');
        $name = $base;
        $suffix = 2;

        while (SavedFilter::query()
            ->where('organization_id', $filter->organization_id)
            ->where('entity_type', $filter->entity_type)
            ->where('created_by', $user->id)
            ->where('name', $name)
            ->exists()) {
            $name = $base.' '.$suffix;
            $suffix++;
        }

        return $name;
    }

    protected function hasMetadataIntent(array $definition): bool
    {
        if (filled($definition['metadata_sort']['key'] ?? null)) {
            return true;
        }

        return collect($definition['metadata_filters'] ?? [])
            ->contains(fn ($filter) => is_array($filter) && filled($filter['key'] ?? null));
    }

    protected function hasStaticIntent(string $entityType, array $definition): bool
    {
        foreach ($this->staticFilterKeys[$entityType] as $key) {
            $value = $definition['static_filters'][$key] ?? null;

            if (is_array($value)) {
                if ($value !== []) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function assertSupportedEntityType(string $entityType): void
    {
        if (! array_key_exists($entityType, $this->staticFilterKeys)) {
            throw new InvalidArgumentException("Saved filters are not supported for entity type [{$entityType}].");
        }
    }

    protected function emptyBuilder(string $entityType): Builder
    {
        $builder = match ($entityType) {
            'lead' => Lead::query(),
            'customer' => Customer::query(),
            'opportunity' => Opportunity::query(),
            'ticket' => CustomerTicket::query(),
            default => throw new InvalidArgumentException("Saved filters are not supported for entity type [{$entityType}]."),
        };

        return $builder->whereRaw('1 = 0');
    }
}
