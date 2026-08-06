<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\Project;
use App\Models\Task;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'task';
    }

    public function entityLabel(): string
    {
        return 'Task';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'title', label: 'Title', required: true, aliases: ['Task', 'Task Name', 'Name']),
            new ImportFieldDefinition(key: 'project_number', label: 'Project Code', required: false, aliases: ['Project', 'Project Number']),
            new ImportFieldDefinition(key: 'description', label: 'Description', required: false),
            new ImportFieldDefinition(key: 'status', label: 'Status', required: false),
            new ImportFieldDefinition(key: 'priority', label: 'Priority', required: false),
            new ImportFieldDefinition(key: 'due_at', label: 'Due Date', required: false, dataType: ImportFieldDefinition::TYPE_DATE, aliases: ['Due']),
            new ImportFieldDefinition(key: 'estimated_hours', label: 'Estimated Hours', required: false, dataType: ImportFieldDefinition::TYPE_NUMBER),
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

            if ($projectNumber) {
                $exists = Project::query()
                    ->where('organization_id', $organization->id)
                    ->where('project_number', $projectNumber)
                    ->exists();
                if (! $exists) {
                    $errors[] = $this->error($rowNumber, 'project_number', 'Project code was not found.', $projectNumber);
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
        $title = $this->stringOrNull($mappedRow['title'] ?? null);
        $projectNumber = $this->stringOrNull($mappedRow['project_number'] ?? null);

        $projectId = null;
        if ($projectNumber) {
            $projectId = Project::query()
                ->where('organization_id', $organization->id)
                ->where('project_number', $projectNumber)
                ->value('id');
        }

        $existing = Task::query()
            ->where('organization_id', $organization->id)
            ->where('title', $title)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->first();

        if ($existing && $strategy === 'skip') {
            return ['action' => 'skipped', 'id' => $existing->id];
        }

        $payload = [
            'organization_id' => $organization->id,
            'project_id' => $projectId,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'description' => $this->stringOrNull($mappedRow['description'] ?? null),
            'status' => $this->stringOrNull($mappedRow['status'] ?? null) ?? 'open',
            'priority' => $this->stringOrNull($mappedRow['priority'] ?? null) ?? 'medium',
            'due_at' => $this->parseDate($mappedRow['due_at'] ?? null),
            'estimated_hours' => $mappedRow['estimated_hours'] ?? null,
            'assigned_to' => $actor->id,
            'assigned_by' => $actor->id,
        ];

        return DB::transaction(function () use ($existing, $strategy, $payload) {
            if ($existing && $strategy === 'update') {
                $existing->update($payload);

                return ['action' => 'updated', 'id' => $existing->id];
            }

            $task = Task::query()->create($payload);

            return ['action' => 'created', 'id' => $task->id];
        });
    }
}
