<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\Project;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'project';
    }

    public function entityLabel(): string
    {
        return 'Project';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'name', label: 'Name', required: true, aliases: ['Project Name', 'Project']),
            new ImportFieldDefinition(key: 'project_number', label: 'Project Code', required: false, aliases: ['Code', 'Project Number']),
            new ImportFieldDefinition(key: 'description', label: 'Description', required: false),
            new ImportFieldDefinition(key: 'priority', label: 'Priority', required: false),
            new ImportFieldDefinition(key: 'start_date', label: 'Start Date', required: false, dataType: ImportFieldDefinition::TYPE_DATE),
            new ImportFieldDefinition(key: 'planned_end_date', label: 'Planned End Date', required: false, dataType: ImportFieldDefinition::TYPE_DATE, aliases: ['End Date']),
            new ImportFieldDefinition(key: 'estimated_budget', label: 'Estimated Budget', required: false, dataType: ImportFieldDefinition::TYPE_NUMBER, aliases: ['Budget']),
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
            $code = $this->stringOrNull($values['project_number'] ?? null);
            $name = $this->stringOrNull($values['name'] ?? null);
            $key = strtolower($code ?: (string) $name);

            if ($key !== '' && isset($seen[$key])) {
                $errors[] = $this->error($rowNumber, $code ? 'project_number' : 'name', 'Duplicate project in file.', $code ?? $name);
            }
            $seen[$key] = true;

            if ($this->shouldReportDatabaseDuplicates($session) && $key !== '') {
                $exists = Project::query()
                    ->where('organization_id', $organization->id)
                    ->when($code, fn ($q) => $q->where('project_number', $code), fn ($q) => $q->where('name', $name))
                    ->exists();
                if ($exists) {
                    $errors[] = $this->error($rowNumber, $code ? 'project_number' : 'name', 'Duplicate project already exists.', $code ?? $name);
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $actor = $this->actorFor($session);
        $strategy = $this->duplicateStrategy($session);
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $code = $this->stringOrNull($mappedRow['project_number'] ?? null);

        $existing = Project::query()
            ->where('organization_id', $organization->id)
            ->when($code, fn ($q) => $q->where('project_number', $code), fn ($q) => $q->where('name', $name))
            ->first();

        if ($existing && $strategy === 'skip') {
            return ['action' => 'skipped', 'id' => $existing->id];
        }

        $payload = [
            'organization_id' => $organization->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'project_number' => $code,
            'description' => $this->stringOrNull($mappedRow['description'] ?? null),
            'priority' => $this->stringOrNull($mappedRow['priority'] ?? null) ?? 'medium',
            'start_date' => $this->parseDate($mappedRow['start_date'] ?? null)?->toDateString(),
            'planned_end_date' => $this->parseDate($mappedRow['planned_end_date'] ?? null)?->toDateString(),
            'estimated_budget' => $mappedRow['estimated_budget'] ?? null,
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
        ];

        return DB::transaction(function () use ($existing, $strategy, $payload) {
            if ($existing && $strategy === 'update') {
                $existing->update($payload);

                return ['action' => 'updated', 'id' => $existing->id];
            }

            $project = Project::query()->create($payload);

            return ['action' => 'created', 'id' => $project->id];
        });
    }
}
