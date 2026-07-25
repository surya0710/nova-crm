<?php

namespace App\Services;

use App\Events\ProjectIssueCreated;
use App\Events\ProjectIssueResolved;
use App\Models\Organization;
use App\Models\ProjectIssue;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ProjectIssue
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $title = trim((string) ($data['title'] ?? ''));

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => __('An issue title is required.'),
                ]);
            }

            $issue = ProjectIssue::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $data['project_id'] ?? null,
                'portfolio_id' => $data['portfolio_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'title' => $title,
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'severity' => $data['severity'] ?? 'medium',
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'status' => $data['status'] ?? 'open',
                'resolution' => $data['resolution'] ?? null,
                'root_cause' => $data['root_cause'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $issue = $issue->fresh(['owner', 'project']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectIssueCreated::forModel(
                $issue,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $issue;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectIssue $issue, array $data, User $actor): ProjectIssue
    {
        return DB::transaction(function () use ($issue, $data, $actor) {
            $payload = [];

            foreach ([
                'title', 'description', 'priority', 'severity', 'owner_id', 'status',
                'resolution', 'root_cause', 'due_date', 'project_id', 'portfolio_id',
                'program_id', 'metadata',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (($payload['status'] ?? null) === 'resolved' && $issue->resolved_at === null) {
                $payload['resolved_at'] = now();
                $payload['resolution'] = $payload['resolution'] ?? $issue->resolution;
                $issue->update($payload);
                $issue = $issue->fresh(['owner', 'project']);
                $this->fireResolved($issue, $actor);

                return $issue;
            }

            if ($payload !== []) {
                $issue->update($payload);
            }

            return $issue->fresh(['owner', 'project']);
        });
    }

    public function resolve(ProjectIssue $issue, User $actor, ?string $resolution = null): ProjectIssue
    {
        return DB::transaction(function () use ($issue, $actor, $resolution) {
            $issue->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution' => $resolution ?? $issue->resolution,
            ]);

            $issue = $issue->fresh(['owner', 'project']);
            $this->fireResolved($issue, $actor);

            return $issue;
        });
    }

    protected function fireResolved(ProjectIssue $issue, User $actor): void
    {
        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectIssueResolved::forModel(
            $issue,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));
    }

    public function delete(ProjectIssue $issue, User $actor): void
    {
        DB::transaction(function () use ($issue) {
            $issue->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectIssue>
     */
    public function list(Organization|int $organization, array $filters = []): Collection
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = ProjectIssue::query()
            ->where('organization_id', $organizationId)
            ->with(['owner', 'project'])
            ->orderByDesc('id');

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', (int) $filters['portfolio_id']);
        }

        if (! empty($filters['program_id'])) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->get();
    }
}
