<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\Department;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class DepartmentImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'department';
    }

    public function entityLabel(): string
    {
        return 'Department';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'name',
                label: 'Name',
                required: true,
                aliases: ['Department Name', 'Department'],
            ),
            new ImportFieldDefinition(
                key: 'code',
                label: 'Code',
                required: false,
                aliases: ['Department Code'],
            ),
            new ImportFieldDefinition(
                key: 'description',
                label: 'Description',
                required: false,
            ),
            new ImportFieldDefinition(
                key: 'branch_code',
                label: 'Branch Code',
                required: false,
                aliases: ['Branch'],
            ),
            new ImportFieldDefinition(
                key: 'is_active',
                label: 'Is Active',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Active'],
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
                    $errors[] = $this->error($rowNumber, $code !== null ? 'code' : 'name', 'Duplicate department within import file.', $code ?? $name);
                } else {
                    $seenKeys[$duplicateKey] = $rowNumber;
                }
            }

            $branchCode = $this->stringOrNull($values['branch_code'] ?? null);
            if ($branchCode !== null && $this->resolveBranchByCode($organization, $branchCode) === null) {
                $errors[] = $this->error($rowNumber, 'branch_code', 'Unknown branch code.', $branchCode);
            }

            if ($this->shouldReportDatabaseDuplicates($session) && $duplicateKey !== null) {
                $existing = $this->findExisting($organization, $code, $name);
                if ($existing) {
                    $field = $code !== null ? 'code' : 'name';
                    $errors[] = $this->error(
                        $rowNumber,
                        $field,
                        'Duplicate department already exists in this organization.',
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
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $code = $this->stringOrNull($mappedRow['code'] ?? null);

        if ($name === null) {
            throw new \InvalidArgumentException('Department name is required.');
        }

        $payload = $this->buildPayload($organization, $mappedRow);
        $existing = $this->findExisting($organization, $code, $name);
        $strategy = $this->duplicateStrategy($session);

        if ($existing !== null) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }

            if ($strategy === 'update') {
                $department = DB::transaction(function () use ($existing, $payload): Department {
                    $existing->update($payload);

                    return $existing->fresh();
                });

                return ['action' => 'updated', 'id' => $department->id];
            }
        }

        $department = DB::transaction(function () use ($payload): Department {
            return Department::query()->create($payload);
        });

        return ['action' => 'created', 'id' => $department->id];
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

    protected function findExisting(Organization $organization, ?string $code, ?string $name): ?Department
    {
        $query = Department::query()->where('organization_id', $organization->id);

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
        $branchCode = $this->stringOrNull($mappedRow['branch_code'] ?? null);
        $branch = $branchCode !== null ? $this->resolveBranchByCode($organization, $branchCode) : null;
        $isActive = $this->parseBoolean($mappedRow['is_active'] ?? null, true);

        return array_filter([
            'organization_id' => $organization->id,
            'name' => $this->stringOrNull($mappedRow['name'] ?? null),
            'code' => $this->stringOrNull($mappedRow['code'] ?? null),
            'description' => $this->stringOrNull($mappedRow['description'] ?? null),
            'branch_id' => $branch?->id,
            'is_active' => $isActive,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
