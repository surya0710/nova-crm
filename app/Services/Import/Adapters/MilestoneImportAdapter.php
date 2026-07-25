<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class MilestoneImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'milestone';
    }

    public function entityLabel(): string
    {
        return 'Milestone';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'name', label: 'Name', required: true, aliases: ['Milestone', 'Milestone Name']),
            new ImportFieldDefinition(key: 'project_number', label: 'Project Code', required: true, aliases: ['Project', 'Project Number']),
            new ImportFieldDefinition(key: 'description', label: 'Description', required: false),
            new ImportFieldDefinition(key: 'sequence', label: 'Sequence', required: false, dataType: ImportFieldDefinition::TYPE_INTEGER, aliases: ['Order']),
            new ImportFieldDefinition(key: 'due_date', label: 'Due Date', required: false, dataType: ImportFieldDefinition::TYPE_DATE),
            new ImportFieldDefinition(key: 'status', label: 'Status', required: false),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }
            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $projectNumber = $this->stringOrNull($values['project_number'] ?? null);

            $project = Project::query()
                ->where('organization_id', $organization->id)
                ->where('project_number', $projectNumber)
                ->first();

            if (! $project) {
                $errors[] = $this->error($rowNumber, 'project_number', 'Project code was not found.', $projectNumber);
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $strategy = $this->duplicateStrategy($session);
        $name = $this->stringOrNull($mappedRow['name'] ?? null);
        $projectNumber = $this->stringOrNull($mappedRow['project_number'] ?? null);

        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->where('project_number', $projectNumber)
            ->firstOrFail();

        $existing = ProjectMilestone::query()
            ->where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->first();

        if ($existing && $strategy === 'skip') {
            return ['action' => 'skipped', 'id' => $existing->id];
        }

        $payload = [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => $name,
            'description' => $this->stringOrNull($mappedRow['description'] ?? null),
            'sequence' => $this->parseInteger($mappedRow['sequence'] ?? null) ?? 0,
            'due_date' => $this->parseDate($mappedRow['due_date'] ?? null)?->toDateString(),
            'status' => $this->stringOrNull($mappedRow['status'] ?? null) ?? 'pending',
        ];

        return DB::transaction(function () use ($existing, $strategy, $payload) {
            if ($existing && $strategy === 'update') {
                $existing->update($payload);

                return ['action' => 'updated', 'id' => $existing->id];
            }

            $milestone = ProjectMilestone::query()->create($payload);

            return ['action' => 'created', 'id' => $milestone->id];
        });
    }
}
