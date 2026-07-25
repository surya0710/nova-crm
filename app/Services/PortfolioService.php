<?php

namespace App\Services;

use App\Events\PortfolioCreated;
use App\Events\PortfolioDeleted;
use App\Events\PortfolioUpdated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortfolioService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Portfolio
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => __('A portfolio name is required.'),
                ]);
            }

            $code = $this->normalizeCode((string) ($data['code'] ?? $name), $organizationId);

            $portfolio = Portfolio::query()->create([
                'organization_id' => $organizationId,
                'name' => $name,
                'code' => $code,
                'description' => $data['description'] ?? null,
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'status' => $data['status'] ?? 'active',
                'color' => $data['color'] ?? '#4f46e5',
                'start_date' => $data['start_date'] ?? null,
                'target_end_date' => $data['target_end_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'settings' => $data['settings'] ?? null,
            ]);

            if (! empty($data['project_ids']) && is_array($data['project_ids'])) {
                $this->syncProjects($portfolio, array_map('intval', $data['project_ids']));
            }

            $portfolio = $portfolio->fresh(['owner', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioCreated::forModel(
                $portfolio,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $portfolio;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Portfolio $portfolio, array $data, User $actor): Portfolio
    {
        if ($portfolio->isArchived()) {
            throw ValidationException::withMessages([
                'portfolio' => __('Archived portfolios are read-only.'),
            ]);
        }

        return DB::transaction(function () use ($portfolio, $data, $actor) {
            $payload = [];

            foreach (['name', 'description', 'owner_id', 'status', 'color', 'start_date', 'target_end_date', 'metadata', 'settings'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (array_key_exists('name', $payload)) {
                $payload['name'] = trim((string) $payload['name']);
                if ($payload['name'] === '') {
                    throw ValidationException::withMessages([
                        'name' => __('A portfolio name is required.'),
                    ]);
                }
            }

            if (array_key_exists('code', $data)) {
                $payload['code'] = $this->normalizeCode(
                    (string) $data['code'],
                    (int) $portfolio->organization_id,
                    $portfolio->id,
                );
            }

            if ($payload !== []) {
                $portfolio->update($payload);
            }

            if (array_key_exists('project_ids', $data) && is_array($data['project_ids'])) {
                $this->syncProjects($portfolio, array_map('intval', $data['project_ids']));
            }

            $portfolio = $portfolio->fresh(['owner', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioUpdated::forModel(
                $portfolio,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $portfolio;
        });
    }

    public function archive(Portfolio $portfolio, User $actor): Portfolio
    {
        if ($portfolio->isArchived()) {
            return $portfolio;
        }

        return DB::transaction(function () use ($portfolio, $actor) {
            $portfolio->update([
                'archived_at' => now(),
                'status' => 'archived',
            ]);

            $portfolio = $portfolio->fresh(['owner', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioUpdated::forModel(
                $portfolio,
                ['actor_id' => $actor->id, 'changes' => ['archived_at', 'status'], 'archived' => true],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $portfolio;
        });
    }

    public function delete(Portfolio $portfolio, User $actor): void
    {
        DB::transaction(function () use ($portfolio, $actor) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioDeleted::forModel(
                $portfolio,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $portfolio->projects()->detach();
            $portfolio->delete();
        });
    }

    public function attachProject(Portfolio $portfolio, Project $project, User $actor): Portfolio
    {
        if ((int) $project->organization_id !== (int) $portfolio->organization_id) {
            throw ValidationException::withMessages([
                'project_id' => __('The project does not belong to this organization.'),
            ]);
        }

        return DB::transaction(function () use ($portfolio, $project, $actor) {
            $portfolio->projects()->syncWithoutDetaching([$project->id]);
            $portfolio = $portfolio->fresh(['owner', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioUpdated::forModel(
                $portfolio,
                ['actor_id' => $actor->id, 'attached_project_id' => $project->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $portfolio;
        });
    }

    public function detachProject(Portfolio $portfolio, Project $project, User $actor): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $project, $actor) {
            $portfolio->projects()->detach($project->id);
            $portfolio = $portfolio->fresh(['owner', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioUpdated::forModel(
                $portfolio,
                ['actor_id' => $actor->id, 'detached_project_id' => $project->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $portfolio;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Portfolio>
     */
    public function list(Organization|int $organization, array $filters = []): Collection
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->query($organizationId, $filters)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(int $organizationId, array $filters = []): Builder
    {
        $query = Portfolio::query()
            ->where('organization_id', $organizationId)
            ->with(['owner'])
            ->withCount('projects')
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = '%'.Str::lower(trim((string) $filters['search'])).'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereRaw('LOWER(name) like ?', [$search])
                    ->orWhereRaw('LOWER(code) like ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) like ?', [$search]);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', (int) $filters['owner_id']);
        }

        if (array_key_exists('archived', $filters) && $filters['archived'] !== null && $filters['archived'] !== '') {
            if ((bool) $filters['archived']) {
                $query->whereNotNull('archived_at');
            } else {
                $query->whereNull('archived_at');
            }
        }

        if (! empty($filters['project_id'])) {
            $query->whereHas('projects', fn (Builder $q) => $q->where('projects.id', (int) $filters['project_id']));
        }

        return $query;
    }

    /**
     * @param  list<int>  $projectIds
     */
    protected function syncProjects(Portfolio $portfolio, array $projectIds): void
    {
        $validIds = Project::query()
            ->where('organization_id', $portfolio->organization_id)
            ->whereIn('id', $projectIds)
            ->pluck('id')
            ->all();

        $portfolio->projects()->sync($validIds);
    }

    protected function normalizeCode(string $code, int $organizationId, ?int $ignoreId = null): string
    {
        $normalized = Str::upper(Str::slug($code, '_'));
        $normalized = $normalized !== '' ? $normalized : 'PORTFOLIO';
        $candidate = $normalized;
        $count = 1;

        while ($this->codeExists($organizationId, $candidate, $ignoreId)) {
            $candidate = $normalized.'_'.$count;
            $count++;
        }

        return Str::limit($candidate, 50, '');
    }

    protected function codeExists(int $organizationId, string $code, ?int $ignoreId): bool
    {
        $query = Portfolio::query()
            ->where('organization_id', $organizationId)
            ->where('code', $code);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
