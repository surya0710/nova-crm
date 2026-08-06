<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class LeaveTypeImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'leave_type';
    }

    public function entityLabel(): string
    {
        return 'Leave Type';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'name',
                label: 'Name',
                required: true,
                aliases: ['Leave Type', 'Leave Name'],
            ),
            new ImportFieldDefinition(
                key: 'code',
                label: 'Code',
                required: false,
                aliases: ['Leave Code'],
            ),
            new ImportFieldDefinition(
                key: 'is_paid',
                label: 'Is Paid',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Paid'],
            ),
            new ImportFieldDefinition(
                key: 'requires_approval',
                label: 'Requires Approval',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
            ),
            new ImportFieldDefinition(
                key: 'allow_half_day',
                label: 'Allow Half Day',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
            ),
            new ImportFieldDefinition(
                key: 'max_days_per_year',
                label: 'Max Days Per Year',
                required: false,
                dataType: ImportFieldDefinition::TYPE_INTEGER,
                aliases: ['Max Days'],
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
                    $errors[] = $this->error($rowNumber, $code !== null ? 'code' : 'name', 'Duplicate leave type within import file.', $code ?? $name);
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
                        'Duplicate leave type already exists in this organization.',
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
            throw new \InvalidArgumentException('Leave type name is required.');
        }

        $payload = $this->buildPayload($organization, $mappedRow);
        $existing = $this->findExisting($organization, $code, $name);
        $strategy = $this->duplicateStrategy($session);

        if ($existing !== null) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }

            if ($strategy === 'update') {
                $leaveType = DB::transaction(function () use ($existing, $payload): LeaveType {
                    $existing->update($payload);

                    return $existing->fresh();
                });

                return ['action' => 'updated', 'id' => $leaveType->id];
            }
        }

        $leaveType = DB::transaction(function () use ($payload): LeaveType {
            return LeaveType::query()->create($payload);
        });

        return ['action' => 'created', 'id' => $leaveType->id];
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

    protected function findExisting(Organization $organization, ?string $code, ?string $name): ?LeaveType
    {
        $query = LeaveType::query()->where('organization_id', $organization->id);

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
        return array_filter([
            'organization_id' => $organization->id,
            'name' => $this->stringOrNull($mappedRow['name'] ?? null),
            'code' => $this->stringOrNull($mappedRow['code'] ?? null),
            'is_paid' => $this->parseBoolean($mappedRow['is_paid'] ?? null, true),
            'requires_approval' => $this->parseBoolean($mappedRow['requires_approval'] ?? null, true),
            'allow_half_day' => $this->parseBoolean($mappedRow['allow_half_day'] ?? null, false),
            'max_days_per_year' => $this->parseInteger($mappedRow['max_days_per_year'] ?? null),
            'is_active' => $this->parseBoolean($mappedRow['is_active'] ?? null, true),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
