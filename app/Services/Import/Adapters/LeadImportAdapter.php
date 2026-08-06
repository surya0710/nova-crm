<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\AssignmentHistory;
use App\Models\ImportSession;
use App\Models\LeadNote;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportFieldDefinition;
use App\Services\Import\ImportOwnerResolver;
use App\Services\LeadNormalizationService;
use App\Services\LeadService;
use App\Services\MetadataEntityFormService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lead module adapter for the Import Platform.
 *
 * Owns Lead field definitions, lookup/owner/duplicate validation, and persistence
 * through LeadService. Contains no spreadsheet parsing logic.
 */
class LeadImportAdapter implements ImportableEntityInterface
{
    public function __construct(
        protected LeadService $leads,
        protected LeadNormalizationService $normalizer,
        protected MetadataEntityFormService $metadataForms,
        protected TenantContext $tenant,
        protected ImportOwnerResolver $owners,
    ) {}

    public function entityType(): string
    {
        return 'lead';
    }

    public function entityLabel(): string
    {
        return 'Lead';
    }

    public function fieldDefinitions(): array
    {
        $fields = [
            new ImportFieldDefinition(
                key: 'first_name',
                label: 'First Name',
                required: false,
                aliases: ['Firstname', 'Given Name'],
            ),
            new ImportFieldDefinition(
                key: 'last_name',
                label: 'Last Name',
                required: false,
                aliases: ['Lastname', 'Surname', 'Family Name'],
            ),
            new ImportFieldDefinition(
                key: 'full_name',
                label: 'Full Name',
                required: false,
                aliases: ['Name', 'Lead Name', 'Contact Name'],
            ),
            new ImportFieldDefinition(
                key: 'email',
                label: 'Email',
                required: false,
                dataType: ImportFieldDefinition::TYPE_EMAIL,
                aliases: ['Email Address', 'E-mail'],
            ),
            new ImportFieldDefinition(
                key: 'phone',
                label: 'Phone',
                required: true,
                dataType: ImportFieldDefinition::TYPE_PHONE,
                aliases: ['Phone Number', 'Mobile', 'Mobile Number'],
            ),
            new ImportFieldDefinition(
                key: 'company',
                label: 'Company',
                required: false,
                aliases: ['Organization', 'Company Name', 'Organisation'],
            ),
            new ImportFieldDefinition(
                key: 'address_line_1',
                label: 'Address',
                required: false,
                aliases: ['Street Address', 'Address Line 1'],
            ),
            new ImportFieldDefinition(
                key: 'city',
                label: 'City',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'state',
                label: 'State',
                required: false,
                aliases: ['State / Province', 'Province'],
            ),
            new ImportFieldDefinition(
                key: 'country',
                label: 'Country',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'postal_code',
                label: 'Postal Code',
                required: false,
                aliases: ['ZIP', 'ZIP Code', 'Pin Code'],
            ),
            new ImportFieldDefinition(
                key: 'source',
                label: 'Source',
                required: false,
                aliases: ['Lead Source'],
            ),
            new ImportFieldDefinition(
                key: 'status',
                label: 'Status',
                required: false,
                aliases: ['Lead Status', 'Pipeline', 'Stage'],
            ),
            new ImportFieldDefinition(
                key: 'owner',
                label: 'Owner',
                required: false,
                aliases: ['Assigned To', 'Assignee', 'Owner Email', 'Employee Code'],
            ),
            new ImportFieldDefinition(
                key: 'priority',
                label: 'Priority',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'industry',
                label: 'Industry',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'budget',
                label: 'Budget',
                required: false,
                dataType: ImportFieldDefinition::TYPE_NUMBER,
            ),
            new ImportFieldDefinition(
                key: 'notes',
                label: 'Notes',
                required: false,
                aliases: ['Note', 'Comments', 'Comment'],
            ),
        ];

        return array_merge($fields, $this->metadataFieldDefinitions());
    }

    /**
     * @param  list<array{row_number: int, values: array<string, mixed>, valid: bool, errors: list<string>}>  $rows
     * @return list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>
     */
    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seenEmails = [];
        $seenPhones = [];
        $duplicateStrategy = $this->duplicateStrategy($session);

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }

            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];

            $name = $this->composeName($values);
            if ($name === null) {
                $errors[] = $this->error($rowNumber, 'full_name', 'Full Name (or First Name / Last Name) is required.', $values['full_name'] ?? null);
            }

            $firstNameMapped = ! empty($session->column_mapping['first_name']);
            if ($firstNameMapped
                && $this->stringOrNull($values['first_name'] ?? null) === null
                && $this->stringOrNull($values['full_name'] ?? null) === null) {
                $errors[] = $this->error($rowNumber, 'first_name', 'First Name is required when the First Name column is mapped.', null);
            }

            foreach (['state' => 'State', 'country' => 'Country'] as $field => $label) {
                $value = $this->stringOrNull($values[$field] ?? null);
                if ($value !== null && mb_strlen($value) > 255) {
                    $errors[] = $this->error($rowNumber, $field, "{$label} may not exceed 255 characters.", $value);
                }
            }

            $email = $this->normalizeEmail($values['email'] ?? null);
            $phone = $this->normalizer->normalizePhone(
                isset($values['phone']) ? (string) $values['phone'] : null
            );

            if ($email !== null) {
                if (isset($seenEmails[$email]) && $duplicateStrategy === 'skip') {
                    $errors[] = $this->error($rowNumber, 'email', 'Duplicate email within import file.', $email);
                } else {
                    $seenEmails[$email] = $rowNumber;
                }
            }

            if ($phone !== null) {
                if (isset($seenPhones[$phone]) && $duplicateStrategy === 'skip') {
                    $errors[] = $this->error($rowNumber, 'phone', 'Duplicate phone within import file.', $phone);
                } else {
                    $seenPhones[$phone] = $rowNumber;
                }
            }

            if ($email !== null || $phone !== null) {
                $duplicate = $this->leads->findDuplicate($organization, $email, $phone);
                if ($duplicate && $duplicateStrategy === 'skip') {
                    $field = ($email !== null && strcasecmp((string) $duplicate->email, $email) === 0) ? 'email' : 'phone';
                    $errors[] = $this->error(
                        $rowNumber,
                        $field,
                        'Duplicate lead already exists in this organization.',
                        $values[$field] ?? null
                    );
                }
            }

            $ownerValue = $this->stringOrNull($values['owner'] ?? null);
            if ($ownerValue !== null) {
                $ownerResolution = $this->owners->resolveWithDiagnostics($organization, $ownerValue);
                if ($ownerResolution['user'] === null) {
                    $errors[] = $this->error(
                        $rowNumber,
                        'owner',
                        $ownerResolution['error'] ?? 'Owner could not be resolved.',
                        $ownerValue,
                    );
                }
            }

            $sourceValue = $this->stringOrNull($values['source'] ?? null);
            if ($sourceValue !== null && $this->resolveConfigKey(config('leads.sources'), $sourceValue) === null) {
                $errors[] = $this->error($rowNumber, 'source', 'Unknown lead source.', $sourceValue);
            }

            $statusValue = $this->stringOrNull($values['status'] ?? null);
            if ($statusValue !== null && $this->resolveConfigKey(config('leads.statuses'), $statusValue) === null) {
                $errors[] = $this->error($rowNumber, 'status', 'Unknown lead status (pipeline/stage).', $statusValue);
            }

            $priorityValue = $this->stringOrNull($values['priority'] ?? null);
            if ($priorityValue !== null && $this->resolveConfigKey(config('leads.priorities'), $priorityValue) === null) {
                $errors[] = $this->error($rowNumber, 'priority', 'Unknown lead priority.', $priorityValue);
            }

            foreach ($this->metadataErrors($organization, $values) as $message) {
                $errors[] = $this->error($rowNumber, null, $message, null);
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $user = $this->actorFor($session);

        $name = $this->composeName($mappedRow);
        if ($name === null) {
            throw new \InvalidArgumentException('Lead name is required.');
        }

        $email = $this->normalizeEmail($mappedRow['email'] ?? null);
        $phone = $this->normalizer->normalizePhone(
            isset($mappedRow['phone']) ? (string) $mappedRow['phone'] : null
        );

        $duplicate = $this->leads->findDuplicate($organization, $email, $phone);
        $duplicateStrategy = $this->duplicateStrategy($session);

        if ($duplicate && $duplicateStrategy === 'skip') {
            return ['action' => 'skipped', 'id' => null];
        }

        $ownerValue = $this->stringOrNull($mappedRow['owner'] ?? null);
        $ownerResolution = $ownerValue !== null
            ? $this->owners->resolveWithDiagnostics($organization, $ownerValue)
            : ['user' => null, 'matched_by' => null, 'error' => null];
        $owner = $ownerResolution['user'];

        if ($ownerValue !== null && $owner === null) {
            throw new \RuntimeException($ownerResolution['error'] ?? 'Owner could not be resolved.');
        }

        $source = $this->resolveConfigKey(config('leads.sources'), $this->stringOrNull($mappedRow['source'] ?? null))
            ?? 'import';
        $status = $this->resolveConfigKey(config('leads.statuses'), $this->stringOrNull($mappedRow['status'] ?? null))
            ?? 'new';
        $priority = $this->resolveConfigKey(config('leads.priorities'), $this->stringOrNull($mappedRow['priority'] ?? null))
            ?? 'medium';

        $metadataPayload = $this->extractMetadataPayload($mappedRow);
        $metadataValues = $metadataPayload === []
            ? []
            : $this->metadataForms->validatedValues(
                null,
                $organization,
                'lead',
                $metadataPayload,
                allowUnknown: false,
                enforceRequired: false,
                context: 'create',
            );

        $notes = $this->stringOrNull($mappedRow['notes'] ?? null);

        $lead = DB::transaction(function () use (
            $organization,
            $user,
            $name,
            $email,
            $phone,
            $mappedRow,
            $source,
            $status,
            $priority,
            $owner,
            $metadataValues,
            $notes,
            $duplicate,
            $duplicateStrategy,
        ) {
            $leadPayload = [
                'organization_id' => $organization->id,
                'name' => $name,
                'company' => $this->stringOrNull($mappedRow['company'] ?? null),
                'email' => $email,
                'phone' => $phone,
                'address_line_1' => $this->stringOrNull($mappedRow['address_line_1'] ?? null),
                'city' => $this->stringOrNull($mappedRow['city'] ?? null),
                'state' => $this->stringOrNull($mappedRow['state'] ?? null),
                'country' => $this->stringOrNull($mappedRow['country'] ?? null),
                'postal_code' => $this->stringOrNull($mappedRow['postal_code'] ?? null),
                'source' => $source,
                'status' => $status,
                'priority' => $priority,
                'industry' => $this->stringOrNull($mappedRow['industry'] ?? null),
                'budget' => $this->nullableNumber($mappedRow['budget'] ?? null),
            ];

            // Explicit owner overrides Assignment Platform; blank owner triggers it.
            if ($owner !== null) {
                $leadPayload['assigned_to'] = $owner->id;
            }

            if ($duplicate && $duplicateStrategy === 'update') {
                $updatePayload = $this->updatePayloadForMappedRow($leadPayload, $mappedRow);
                $lead = $this->leads->update($duplicate, $updatePayload, $user, $metadataValues);
            } else {
                $lead = $this->leads->create(
                    $leadPayload,
                    $user,
                    AssignmentHistory::REASON_IMPORTED,
                );
            }

            if ($metadataValues !== [] && ! ($duplicate && $duplicateStrategy === 'update')) {
                $this->metadataForms->persistValidatedValues($lead, $metadataValues);
            }

            if ($notes !== null) {
                LeadNote::query()->create([
                    'organization_id' => $organization->id,
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'body' => $notes,
                ]);
            }

            return $lead->fresh();
        });

        return [
            'action' => $duplicate && $duplicateStrategy === 'update' ? 'updated' : 'created',
            'id' => $lead->id,
            'organization_id' => $lead->organization_id,
            'assigned_to' => $lead->assigned_to,
            'created_by' => $lead->created_by,
            'owner_resolution' => $ownerResolution['matched_by'],
        ];
    }

    protected function duplicateStrategy(ImportSession $session): string
    {
        $strategy = strtolower(trim((string) ($session->metadata['duplicate_strategy'] ?? 'skip')));

        return in_array($strategy, ['skip', 'update', 'create'], true) ? $strategy : 'skip';
    }

    /**
     * Only update fields that were populated by the import row.
     *
     * @param  array<string, mixed>  $leadPayload
     * @param  array<string, mixed>  $mappedRow
     * @return array<string, mixed>
     */
    protected function updatePayloadForMappedRow(array $leadPayload, array $mappedRow): array
    {
        unset($leadPayload['organization_id']);

        $fieldMap = [
            'company' => 'company',
            'email' => 'email',
            'phone' => 'phone',
            'address_line_1' => 'address_line_1',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'postal_code' => 'postal_code',
            'source' => 'source',
            'status' => 'status',
            'priority' => 'priority',
            'industry' => 'industry',
            'budget' => 'budget',
            'assigned_to' => 'owner',
        ];

        foreach ($fieldMap as $payloadKey => $rowKey) {
            if ($this->stringOrNull($mappedRow[$rowKey] ?? null) === null) {
                unset($leadPayload[$payloadKey]);
            }
        }

        return $leadPayload;
    }

    /**
     * @return list<ImportFieldDefinition>
     */
    protected function metadataFieldDefinitions(): array
    {
        $organization = $this->tenant->get();

        if (! $organization) {
            return [];
        }

        $fields = [];
        $reservedKeys = [
            'first_name', 'last_name', 'full_name', 'email', 'phone', 'company',
            'address_line_1', 'city', 'state', 'country', 'postal_code',
            'source', 'status', 'owner', 'priority', 'industry', 'budget', 'notes',
        ];

        foreach ($this->metadataForms->fieldsFor($organization, 'lead', 'create') as $item) {
            /** @var MetadataFieldDefinition $definition */
            $definition = $item['field'];

            if (in_array($definition->key, $reservedKeys, true)) {
                continue;
            }

            $fields[] = new ImportFieldDefinition(
                key: $definition->key,
                label: $definition->label,
                required: (bool) $definition->is_required,
                dataType: $this->mapMetadataType((string) $definition->type),
                supportsMetadata: true,
                aliases: [$definition->label],
            );
        }

        return $fields;
    }

    protected function mapMetadataType(string $type): string
    {
        return match ($type) {
            'email' => ImportFieldDefinition::TYPE_EMAIL,
            'phone' => ImportFieldDefinition::TYPE_PHONE,
            'date', 'datetime' => ImportFieldDefinition::TYPE_DATE,
            'number', 'decimal', 'currency', 'percentage' => ImportFieldDefinition::TYPE_NUMBER,
            'boolean' => ImportFieldDefinition::TYPE_BOOLEAN,
            default => ImportFieldDefinition::TYPE_STRING,
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    protected function metadataErrors(Organization $organization, array $values): array
    {
        $payload = $this->extractMetadataPayload($values);

        if ($payload === []) {
            return [];
        }

        try {
            $this->metadataForms->validatedValues(
                null,
                $organization,
                'lead',
                $payload,
                allowUnknown: false,
                enforceRequired: false,
                context: 'create',
            );
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $fieldErrors) {
                foreach ($fieldErrors as $message) {
                    $messages[] = $message;
                }
            }

            return $messages;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function extractMetadataPayload(array $values): array
    {
        $payload = [];

        foreach ($this->fieldDefinitions() as $field) {
            if (! $field->supportsMetadata) {
                continue;
            }

            if (! array_key_exists($field->key, $values)) {
                continue;
            }

            $value = $values[$field->key];
            if ($value === null || $value === '') {
                continue;
            }

            $payload[$field->key] = $value;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function composeName(array $values): ?string
    {
        $full = $this->stringOrNull($values['full_name'] ?? null);
        if ($full !== null) {
            return $full;
        }

        $parts = array_filter([
            $this->stringOrNull($values['first_name'] ?? null),
            $this->stringOrNull($values['last_name'] ?? null),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function resolveConfigKey(array $map, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $needle = strtolower(trim($value));

        foreach ($map as $key => $label) {
            if (strtolower((string) $key) === $needle || strtolower((string) $label) === $needle) {
                return (string) $key;
            }
        }

        return null;
    }

    protected function organizationFor(ImportSession $session): Organization
    {
        $organization = Organization::query()->find($session->organization_id);

        if (! $organization) {
            throw new \RuntimeException('Import session organization was not found.');
        }

        if ($this->tenant->id() !== $organization->id) {
            $this->tenant->set($organization);
        }

        return $organization;
    }

    protected function actorFor(ImportSession $session): User
    {
        if ($session->uploaded_by) {
            $user = User::query()->find($session->uploaded_by);
            if ($user && $user->belongsToActiveOrganization((int) $session->organization_id)) {
                return $user;
            }
        }

        $authUser = auth()->user();
        if ($authUser instanceof User
            && $authUser->belongsToActiveOrganization((int) $session->organization_id)) {
            return $authUser;
        }

        throw new \RuntimeException('Import session has no active uploading user in this organization.');
    }

    protected function normalizeEmail(mixed $email): ?string
    {
        $email = $this->stringOrNull($email);

        return $email !== null ? strtolower($email) : null;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function nullableNumber(mixed $value): ?float
    {
        $string = $this->stringOrNull($value);

        if ($string === null || ! is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }

    /**
     * @return array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}
     */
    protected function error(int $rowNumber, ?string $field, string $message, mixed $value): array
    {
        return [
            'row_number' => $rowNumber,
            'column' => null,
            'field' => $field,
            'error' => $message,
            'value' => $value !== null ? (string) $value : null,
        ];
    }
}
