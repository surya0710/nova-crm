<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\Holiday;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class HolidayImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'holiday';
    }

    public function entityLabel(): string
    {
        return 'Holiday';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'name',
                label: 'Name',
                required: true,
                aliases: ['Holiday Name', 'Holiday'],
            ),
            new ImportFieldDefinition(
                key: 'holiday_date',
                label: 'Holiday Date',
                required: true,
                dataType: ImportFieldDefinition::TYPE_DATE,
                aliases: ['Date'],
            ),
            new ImportFieldDefinition(
                key: 'branch_code',
                label: 'Branch Code',
                required: false,
                aliases: ['Branch'],
            ),
            new ImportFieldDefinition(
                key: 'is_optional',
                label: 'Is Optional',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Optional'],
            ),
            new ImportFieldDefinition(
                key: 'is_recurring',
                label: 'Is Recurring',
                required: false,
                dataType: ImportFieldDefinition::TYPE_BOOLEAN,
                aliases: ['Recurring'],
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
            $holidayDate = $this->parseDate($values['holiday_date'] ?? null);
            $branchCode = $this->stringOrNull($values['branch_code'] ?? null);
            $branch = $branchCode !== null ? $this->resolveBranchByCode($organization, $branchCode) : null;

            if ($branchCode !== null && $branch === null) {
                $errors[] = $this->error($rowNumber, 'branch_code', 'Unknown branch code.', $branchCode);
            }

            if ($name !== null && $holidayDate !== null) {
                $duplicateKey = $this->duplicateKey($name, $holidayDate->toDateString(), $branch?->id);

                if (isset($seenKeys[$duplicateKey])) {
                    $errors[] = $this->error($rowNumber, 'name', 'Duplicate holiday within import file.', $name);
                } else {
                    $seenKeys[$duplicateKey] = $rowNumber;
                }

                if ($this->shouldReportDatabaseDuplicates($session)) {
                    $existing = $this->findExisting($organization, $name, $holidayDate->toDateString(), $branch?->id);
                    if ($existing) {
                        $errors[] = $this->error(
                            $rowNumber,
                            'name',
                            'Duplicate holiday already exists in this organization.',
                            $name
                        );
                    }
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $holidayDate = $this->parseDate($mappedRow['holiday_date'] ?? null);

        if ($name === null) {
            throw new \InvalidArgumentException('Holiday name is required.');
        }

        if ($holidayDate === null) {
            throw new \InvalidArgumentException('Holiday date is required.');
        }

        $payload = $this->buildPayload($organization, $mappedRow, $holidayDate);
        $branchId = $payload['branch_id'] ?? null;
        $existing = $this->findExisting($organization, $name, $holidayDate->toDateString(), $branchId);
        $strategy = $this->duplicateStrategy($session);

        if ($existing !== null) {
            if ($strategy === 'skip') {
                return ['action' => 'skipped', 'id' => $existing->id];
            }

            if ($strategy === 'update') {
                $holiday = DB::transaction(function () use ($existing, $payload): Holiday {
                    $existing->update($payload);

                    return $existing->fresh();
                });

                return ['action' => 'updated', 'id' => $holiday->id];
            }
        }

        $holiday = DB::transaction(function () use ($payload): Holiday {
            return Holiday::query()->create($payload);
        });

        return ['action' => 'created', 'id' => $holiday->id];
    }

    protected function duplicateKey(string $name, string $date, ?int $branchId): string
    {
        return strtolower($name).'|'.$date.'|'.($branchId ?? 'org');
    }

    protected function findExisting(Organization $organization, string $name, string $date, ?int $branchId): ?Holiday
    {
        $query = Holiday::query()
            ->where('organization_id', $organization->id)
            ->where('name', $name)
            ->whereDate('holiday_date', $date);

        if ($branchId === null) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $branchId);
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $mappedRow
     * @return array<string, mixed>
     */
    protected function buildPayload(Organization $organization, array $mappedRow, \Illuminate\Support\Carbon $holidayDate): array
    {
        $branchCode = $this->stringOrNull($mappedRow['branch_code'] ?? null);
        $branch = $branchCode !== null ? $this->resolveBranchByCode($organization, $branchCode) : null;

        return array_filter([
            'organization_id' => $organization->id,
            'name' => $this->stringOrNull($mappedRow['name'] ?? null),
            'holiday_date' => $holidayDate->toDateString(),
            'branch_id' => $branch?->id,
            'is_optional' => $this->parseBoolean($mappedRow['is_optional'] ?? null, false),
            'is_recurring' => $this->parseBoolean($mappedRow['is_recurring'] ?? null, false),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
