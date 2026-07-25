<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\Branch;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Services\Hrms\BranchService;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;

class BranchImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(
        protected TenantContext $tenant,
        protected BranchService $branches,
    ) {}

    public function entityType(): string
    {
        return 'branch';
    }

    public function entityLabel(): string
    {
        return 'Branch';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'name',
                label: 'Name',
                required: true,
                aliases: ['Branch Name', 'Branch'],
            ),
            new ImportFieldDefinition(
                key: 'code',
                label: 'Code',
                required: false,
                aliases: ['Branch Code'],
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
            ),
            new ImportFieldDefinition(
                key: 'country',
                label: 'Country',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'contact_email',
                label: 'Contact Email',
                required: false,
                dataType: ImportFieldDefinition::TYPE_EMAIL,
                aliases: ['Email'],
            ),
            new ImportFieldDefinition(
                key: 'contact_phone',
                label: 'Contact Phone',
                required: false,
                dataType: ImportFieldDefinition::TYPE_PHONE,
                aliases: ['Phone'],
            ),
            new ImportFieldDefinition(
                key: 'is_active',
                label: 'Is Active',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Active'],
            ),
            new ImportFieldDefinition(
                key: 'is_default',
                label: 'Is Default',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Default'],
            ),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seenKeys = [];

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }

            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $name = $this->stringOrNull($values['name'] ?? null);
            $code = $this->stringOrNull($values['code'] ?? null);
            $duplicateKey = $this->duplicateKey($code, $name);

            if ($duplicateKey !== null) {
                if (isset($seenKeys[$duplicateKey])) {
                    $errors[] = $this->error($rowNumber, $code !== null ? 'code' : 'name', 'Duplicate branch within import file.', $code ?? $name);
                } else {
                    $seenKeys[$duplicateKey] = $rowNumber;
                }
            }

            if ($this->shouldReportDatabaseDuplicates($session) && $duplicateKey !== null) {
                $existing = $this->findExisting($organization, $code, $name);
                if ($existing) {
                    $field = $code !== null ? 'code' : 'name';
                    $errors[] = $this->error(
                        $rowNumber,
                        $field,
                        'Duplicate branch already exists in this organization.',
                        $values[$field] ?? null
                    );
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $actor = $this->actorFor($session);
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $code = $this->stringOrNull($mappedRow['code'] ?? null);

        if ($name === null) {
            throw new \InvalidArgumentException('Branch name is required.');
        }

        $payload = $this->buildPayload($organization, $mappedRow);
        $existing = $this->findExisting($organization, $code, $name);
        $strategy = $this->duplicateStrategy($session);

        if ($existing !== null) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }

            if ($strategy === 'update') {
                $branch = $this->branches->update($existing, $payload, $actor);

                return ['action' => 'updated', 'id' => $branch->id];
            }
        }

        $branch = $this->branches->create($payload, $actor);

        return ['action' => 'created', 'id' => $branch->id];
    }

    protected function duplicateKey(?string $code, ?string $name): ?string
    {
        if ($code !== null) {
            return 'code:'.strtolower($code);
        }

        if ($name !== null) {
            return 'name:'.strtolower($name);
        }

        return null;
    }

    protected function findExisting(Organization $organization, ?string $code, ?string $name): ?Branch
    {
        $query = Branch::query()->where('organization_id', $organization->id);

        if ($code !== null) {
            return (clone $query)->where('code', $code)->first();
        }

        if ($name !== null) {
            return (clone $query)->where('name', $name)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $mappedRow
     * @return array<string, mixed>
     */
    protected function buildPayload(Organization $organization, array $mappedRow): array
    {
        $isActive = $this->parseBoolean($mappedRow['is_active'] ?? null, true);
        $isDefault = $this->parseBoolean($mappedRow['is_default'] ?? null, false);

        return array_filter([
            'organization_id' => $organization->id,
            'name' => $this->stringOrNull($mappedRow['name'] ?? null),
            'code' => $this->stringOrNull($mappedRow['code'] ?? null),
            'city' => $this->stringOrNull($mappedRow['city'] ?? null),
            'state' => $this->stringOrNull($mappedRow['state'] ?? null),
            'country' => $this->stringOrNull($mappedRow['country'] ?? null),
            'contact_email' => $this->normalizeEmail($mappedRow['contact_email'] ?? null),
            'contact_phone' => $this->stringOrNull($mappedRow['contact_phone'] ?? null),
            'is_active' => $isActive,
            'is_default' => $isDefault,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
