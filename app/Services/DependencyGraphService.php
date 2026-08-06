<?php

namespace App\Services;

use App\Events\ProjectDependencyCreated;
use App\Events\ProjectDependencyUpdated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectDependency;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DependencyGraphService
{
    public const DEPENDENCY_TYPES = [
        'finish_to_start',
        'start_to_start',
        'finish_to_finish',
        'start_to_finish',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ProjectDependency
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $predecessorId = (int) ($data['predecessor_project_id'] ?? 0);
            $successorId = (int) ($data['successor_project_id'] ?? 0);
            $type = (string) ($data['dependency_type'] ?? 'finish_to_start');

            $this->assertValidType($type);
            $this->assertProjects($organizationId, $predecessorId, $successorId);
            $this->assertNoSelfLink($predecessorId, $successorId);
            $this->assertNoCycle($organizationId, $predecessorId, $successorId);

            $dependency = ProjectDependency::query()->create([
                'organization_id' => $organizationId,
                'predecessor_project_id' => $predecessorId,
                'successor_project_id' => $successorId,
                'dependency_type' => $type,
                'lag_days' => (int) ($data['lag_days'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ]);

            $dependency = $dependency->fresh(['predecessor', 'successor']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectDependencyCreated::forModel(
                $dependency,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $dependency;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectDependency $dependency, array $data, User $actor): ProjectDependency
    {
        return DB::transaction(function () use ($dependency, $data, $actor) {
            $predecessorId = array_key_exists('predecessor_project_id', $data)
                ? (int) $data['predecessor_project_id']
                : (int) $dependency->predecessor_project_id;
            $successorId = array_key_exists('successor_project_id', $data)
                ? (int) $data['successor_project_id']
                : (int) $dependency->successor_project_id;
            $type = array_key_exists('dependency_type', $data)
                ? (string) $data['dependency_type']
                : (string) $dependency->dependency_type;

            $this->assertValidType($type);
            $this->assertProjects((int) $dependency->organization_id, $predecessorId, $successorId);
            $this->assertNoSelfLink($predecessorId, $successorId);
            $this->assertNoCycle(
                (int) $dependency->organization_id,
                $predecessorId,
                $successorId,
                $dependency->id,
            );

            $payload = [
                'predecessor_project_id' => $predecessorId,
                'successor_project_id' => $successorId,
                'dependency_type' => $type,
            ];

            if (array_key_exists('lag_days', $data)) {
                $payload['lag_days'] = (int) $data['lag_days'];
            }

            if (array_key_exists('notes', $data)) {
                $payload['notes'] = $data['notes'];
            }

            $dependency->update($payload);
            $dependency = $dependency->fresh(['predecessor', 'successor']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectDependencyUpdated::forModel(
                $dependency,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $dependency;
        });
    }

    public function delete(ProjectDependency $dependency, User $actor): void
    {
        DB::transaction(function () use ($dependency, $actor) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectDependencyUpdated::forModel(
                $dependency,
                ['actor_id' => $actor->id, 'deleted' => true],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $dependency->delete();
        });
    }

    /**
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    public function graph(Organization|int $organization, ?Portfolio $portfolio = null): array
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $projectsQuery = Project::query()
            ->where('organization_id', $organizationId)
            ->where('is_archived', false);

        if ($portfolio) {
            $projectIds = $portfolio->projects()->pluck('projects.id');
            $projectsQuery->whereIn('id', $projectIds);
        }

        $projects = $projectsQuery->with('status')->get();
        $ids = $projects->pluck('id');

        $edges = ProjectDependency::query()
            ->where('organization_id', $organizationId)
            ->whereIn('predecessor_project_id', $ids)
            ->whereIn('successor_project_id', $ids)
            ->get();

        return [
            'nodes' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status?->slug,
                'completion_percentage' => $project->completion_percentage,
                'planned_end_date' => $project->planned_end_date?->toDateString(),
            ])->values()->all(),
            'edges' => $edges->map(fn (ProjectDependency $dep) => [
                'id' => $dep->id,
                'from' => $dep->predecessor_project_id,
                'to' => $dep->successor_project_id,
                'type' => $dep->dependency_type,
                'lag_days' => $dep->lag_days,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function impactAnalysis(Project $project): array
    {
        $predecessors = ProjectDependency::query()
            ->where('organization_id', $project->organization_id)
            ->where('successor_project_id', $project->id)
            ->with('predecessor.status')
            ->get();

        $successors = ProjectDependency::query()
            ->where('organization_id', $project->organization_id)
            ->where('predecessor_project_id', $project->id)
            ->with('successor.status')
            ->get();

        $downstream = $this->collectDownstream((int) $project->organization_id, (int) $project->id);
        $upstream = $this->collectUpstream((int) $project->organization_id, (int) $project->id);

        return [
            'project_id' => $project->id,
            'direct_predecessors' => $predecessors->map(fn (ProjectDependency $d) => [
                'dependency_id' => $d->id,
                'project_id' => $d->predecessor_project_id,
                'name' => $d->predecessor?->name,
                'type' => $d->dependency_type,
                'lag_days' => $d->lag_days,
            ])->values()->all(),
            'direct_successors' => $successors->map(fn (ProjectDependency $d) => [
                'dependency_id' => $d->id,
                'project_id' => $d->successor_project_id,
                'name' => $d->successor?->name,
                'type' => $d->dependency_type,
                'lag_days' => $d->lag_days,
            ])->values()->all(),
            'upstream_project_ids' => $upstream,
            'downstream_project_ids' => $downstream,
            'blocking' => $this->blockingIndicators($project),
        ];
    }

    /**
     * @return array{is_blocked: bool, blocked_by: list<array<string, mixed>>, blocks: list<array<string, mixed>>}
     */
    public function blockingIndicators(Project $project): array
    {
        $blockedBy = ProjectDependency::query()
            ->where('organization_id', $project->organization_id)
            ->where('successor_project_id', $project->id)
            ->with(['predecessor.status'])
            ->get()
            ->filter(function (ProjectDependency $dep) {
                $pred = $dep->predecessor;
                if (! $pred) {
                    return false;
                }

                if ($pred->isArchived()) {
                    return false;
                }

                if ($pred->status?->is_closed) {
                    return false;
                }

                return (int) ($pred->completion_percentage ?? 0) < 100;
            })
            ->map(fn (ProjectDependency $dep) => [
                'project_id' => $dep->predecessor_project_id,
                'name' => $dep->predecessor?->name,
                'completion_percentage' => $dep->predecessor?->completion_percentage,
                'dependency_type' => $dep->dependency_type,
            ])
            ->values()
            ->all();

        $blocks = ProjectDependency::query()
            ->where('organization_id', $project->organization_id)
            ->where('predecessor_project_id', $project->id)
            ->with('successor')
            ->get()
            ->map(fn (ProjectDependency $dep) => [
                'project_id' => $dep->successor_project_id,
                'name' => $dep->successor?->name,
                'dependency_type' => $dep->dependency_type,
            ])
            ->values()
            ->all();

        return [
            'is_blocked' => $blockedBy !== [],
            'blocked_by' => $blockedBy,
            'blocks' => $blocks,
        ];
    }

    protected function assertValidType(string $type): void
    {
        $allowed = array_keys(config('projects.dependency_types', []));
        if ($allowed === []) {
            $allowed = array_keys(config('tasks.dependency_types', []));
        }
        if ($allowed === []) {
            $allowed = self::DEPENDENCY_TYPES;
        }

        if (! in_array($type, $allowed, true)) {
            throw ValidationException::withMessages([
                'dependency_type' => __('Invalid dependency type.'),
            ]);
        }
    }

    protected function assertProjects(int $organizationId, int $predecessorId, int $successorId): void
    {
        $count = Project::query()
            ->where('organization_id', $organizationId)
            ->whereIn('id', [$predecessorId, $successorId])
            ->count();

        if ($count < 2) {
            throw ValidationException::withMessages([
                'predecessor_project_id' => __('Both projects must belong to the organization.'),
            ]);
        }
    }

    protected function assertNoSelfLink(int $predecessorId, int $successorId): void
    {
        if ($predecessorId === $successorId) {
            throw ValidationException::withMessages([
                'successor_project_id' => __('A project cannot depend on itself.'),
            ]);
        }
    }

    protected function assertNoCycle(int $organizationId, int $predecessorId, int $successorId, ?int $ignoreId = null): void
    {
        // Adding edge predecessor -> successor creates a cycle if predecessor
        // is already reachable from successor.
        $reachable = $this->collectDownstream($organizationId, $successorId, $ignoreId);

        if (in_array($predecessorId, $reachable, true)) {
            throw ValidationException::withMessages([
                'successor_project_id' => __('This dependency would create a circular chain.'),
            ]);
        }
    }

    /**
     * @return list<int>
     */
    protected function collectDownstream(int $organizationId, int $projectId, ?int $ignoreDependencyId = null): array
    {
        $visited = [];
        $queue = [$projectId];

        while ($queue !== []) {
            $current = array_shift($queue);
            $edges = ProjectDependency::query()
                ->where('organization_id', $organizationId)
                ->where('predecessor_project_id', $current)
                ->when($ignoreDependencyId, fn ($q) => $q->whereKeyNot($ignoreDependencyId))
                ->pluck('successor_project_id');

            foreach ($edges as $next) {
                $next = (int) $next;
                if (isset($visited[$next])) {
                    continue;
                }
                $visited[$next] = true;
                $queue[] = $next;
            }
        }

        return array_map('intval', array_keys($visited));
    }

    /**
     * @return list<int>
     */
    protected function collectUpstream(int $organizationId, int $projectId): array
    {
        $visited = [];
        $queue = [$projectId];

        while ($queue !== []) {
            $current = array_shift($queue);
            $edges = ProjectDependency::query()
                ->where('organization_id', $organizationId)
                ->where('successor_project_id', $current)
                ->pluck('predecessor_project_id');

            foreach ($edges as $prev) {
                $prev = (int) $prev;
                if (isset($visited[$prev])) {
                    continue;
                }
                $visited[$prev] = true;
                $queue[] = $prev;
            }
        }

        return array_map('intval', array_keys($visited));
    }
}
