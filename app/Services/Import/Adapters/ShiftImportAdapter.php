<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\HrmsShift;
use App\Models\ImportSession;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class ShiftImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'shift';
    }

    public function entityLabel(): string
    {
        return 'Shift';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'name', label: 'Name', required: true, aliases: ['Shift Name', 'Shift']),
            new ImportFieldDefinition(key: 'code', label: 'Code', required: false, aliases: ['Shift Code']),
            new ImportFieldDefinition(key: 'start_time', label: 'Start Time', required: false, aliases: ['Start']),
            new ImportFieldDefinition(key: 'end_time', label: 'End Time', required: false, aliases: ['End']),
            new ImportFieldDefinition(key: 'break_minutes', label: 'Break Minutes', required: false, dataType: ImportFieldDefinition::TYPE_INTEGER),
            new ImportFieldDefinition(key: 'is_overnight', label: 'Is Overnight', required: false, dataType: ImportFieldDefinition::TYPE_BOOLEAN),
            new ImportFieldDefinition(key: 'is_active', label: 'Is Active', required: false, dataType: ImportFieldDefinition::TYPE_BOOLEAN, aliases: ['Active']),
            new ImportFieldDefinition(key: 'is_default', label: 'Is Default', required: false, dataType: ImportFieldDefinition::TYPE_BOOLEAN),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }

            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $name = $this->stringOrNull($values['name'] ?? null);
            $code = $this->stringOrNull($values['code'] ?? null);
            $key = strtolower($code ?: (string) $name);

            if ($key !== '' && isset($seen[$key])) {
                $errors[] = $this->error($rowNumber, $code ? 'code' : 'name', 'Duplicate shift in file.', $code ?? $name);
            }
            $seen[$key] = true;

            if ($this->shouldReportDatabaseDuplicates($session) && $key !== '') {
                $exists = HrmsShift::query()
                    ->where('organization_id', $organization->id)
                    ->when($code, fn ($q) => $q->where('code', $code), fn ($q) => $q->where('name', $name))
                    ->exists();
                if ($exists) {
                    $errors[] = $this->error($rowNumber, $code ? 'code' : 'name', 'Duplicate shift already exists.', $code ?? $name);
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $strategy = $this->duplicateStrategy($session);
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $code = $this->stringOrNull($mappedRow['code'] ?? null);

        $existing = HrmsShift::query()
            ->where('organization_id', $organization->id)
            ->when($code, fn ($q) => $q->where('code', $code), fn ($q) => $q->where('name', $name))
            ->first();

        if ($existing && $strategy === 'skip') {
            return ['action' => 'skipped', 'id' => $existing->id];
        }

        $payload = [
            'organization_id' => $organization->id,
            'name' => $name,
            'code' => $code,
            'start_time' => $this->stringOrNull($mappedRow['start_time'] ?? null),
            'end_time' => $this->stringOrNull($mappedRow['end_time'] ?? null),
            'break_minutes' => $this->parseInteger($mappedRow['break_minutes'] ?? null),
            'is_overnight' => $this->parseBoolean($mappedRow['is_overnight'] ?? null, false),
            'is_active' => $this->parseBoolean($mappedRow['is_active'] ?? null, true),
            'is_default' => $this->parseBoolean($mappedRow['is_default'] ?? null, false),
        ];

        return DB::transaction(function () use ($existing, $strategy, $payload) {
            if ($existing && $strategy === 'update') {
                $existing->update($payload);

                return ['action' => 'updated', 'id' => $existing->id];
            }

            $shift = HrmsShift::query()->create($payload);

            return ['action' => 'created', 'id' => $shift->id];
        });
    }
}
