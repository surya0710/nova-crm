<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MetadataEntityFormService
{
    public function __construct(
        protected MetadataFormResolver $resolver,
        protected MetadataFormValuePresenter $presenter,
        protected MetadataValueStorageService $storage,
        protected MetadataValidationService $validator,
        protected MetadataProjectionService $projection,
    ) {}

    public function presenter(): MetadataFormValuePresenter
    {
        return $this->presenter;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fieldsFor(?Organization $organization, string $entityType, string $context): Collection
    {
        if (! $organization) {
            return collect();
        }

        return $this->resolver->fieldsFor($organization, $entityType, $context);
    }

    /**
     * Persist rendered metadata fields from a web request.
     *
     * @return array{
     *     changed: bool,
     *     values: array<string, mixed>,
     *     changes: array<string, array{field_id: ?int, old: mixed, new: mixed}>,
     *     ignored: array<int, string>
     * }|null
     */
    public function persistFromRequest(
        Model $record,
        ?Organization $organization,
        string $entityType,
        string $context,
        Request $request,
        bool $allowUnknown = false
    ): ?array {
        $values = $this->validatedValuesFromRequest($record, $organization, $entityType, $context, $request, $allowUnknown);

        return $this->persistValidatedValues($record, $values, $allowUnknown);
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedValuesFromRequest(
        ?Model $record,
        ?Organization $organization,
        string $entityType,
        string $context,
        Request $request,
        bool $allowUnknown = false,
        bool $enforceRequired = true
    ): array {
        if (! $organization) {
            return [];
        }

        $payload = $request->input('custom_fields', []);
        $payload = is_array($payload) ? $payload : [];
        $fields = $this->fieldsFor($organization, $entityType, $context);

        $values = $this->presenter->extractSubmittedValues(
            $fields,
            $payload,
        );

        return $this->validator->validate(
            $record,
            $organization,
            $entityType,
            $fields,
            $values,
            $allowUnknown,
            $enforceRequired,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function validatedValues(
        ?Model $record,
        Organization $organization,
        string $entityType,
        array $values,
        bool $allowUnknown = false,
        bool $enforceRequired = true,
        string $context = 'edit'
    ): array {
        return $this->validator->validate(
            $record,
            $organization,
            $entityType,
            $this->fieldsFor($organization, $entityType, $context),
            $values,
            $allowUnknown,
            $enforceRequired,
        );
    }

    /**
     * Persist a raw metadata payload, primarily for non-form channels.
     *
     * @param  array<string, mixed>  $values
     * @return array{
     *     changed: bool,
     *     values: array<string, mixed>,
     *     changes: array<string, array{field_id: ?int, old: mixed, new: mixed}>,
     *     ignored: array<int, string>
     * }
     */
    public function persistValues(Model $record, array $values, bool $allowUnknown = false, bool $enforceRequired = true): array
    {
        $organization = $record instanceof Organization
            ? $record
            : Organization::query()->findOrFail($record->organization_id);
        $entityType = $this->storage->entityTypeFor($record);
        $validated = $this->validatedValues($record, $organization, $entityType, $values, $allowUnknown, $enforceRequired);

        $result = $this->storage->mergeValues($record, $validated, $allowUnknown);

        if ($result['changed']) {
            $this->projection->sync($record->refresh());
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{
     *     changed: bool,
     *     values: array<string, mixed>,
     *     changes: array<string, array{field_id: ?int, old: mixed, new: mixed}>,
     *     ignored: array<int, string>
     * }|null
     */
    public function persistValidatedValues(Model $record, array $values, bool $allowUnknown = false): ?array
    {
        if ($values === []) {
            return null;
        }

        $result = $this->storage->mergeValues($record, $values, $allowUnknown);

        if ($result['changed']) {
            $this->projection->sync($record->refresh());
        }

        return $result;
    }
}
