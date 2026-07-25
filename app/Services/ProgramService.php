<?php

namespace App\Services;

use App\Events\ProgramCreated;
use App\Events\ProgramUpdated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgramService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Program
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => __('A program name is required.'),
                ]);
            }

            $portfolioId = isset($data['portfolio_id']) ? (int) $data['portfolio_id'] : null;
            if ($portfolioId) {
                $this->assertPortfolioBelongs($organizationId, $portfolioId);
            }

            $code = $this->normalizeCode((string) ($data['code'] ?? $name), $organizationId);

            $program = Program::query()->create([
                'organization_id' => $organizationId,
                'portfolio_id' => $portfolioId,
                'name' => $name,
                'code' => $code,
                'description' => $data['description'] ?? null,
                'manager_id' => $data['manager_id'] ?? $actor->id,
                'status' => $data['status'] ?? 'active',
                'color' => $data['color'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'target_end_date' => $data['target_end_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if (! empty($data['project_ids']) && is_array($data['project_ids'])) {
                $this->syncProjects($program, array_map('intval', $data['project_ids']));
            }

            $program = $program->fresh(['manager', 'portfolio', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProgramCreated::forModel(
                $program,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $program;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Program $program, array $data, User $actor): Program
    {
        return DB::transaction(function () use ($program, $data, $actor) {
            $payload = [];

            foreach (['name', 'description', 'manager_id', 'status', 'color', 'start_date', 'target_end_date', 'metadata'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (array_key_exists('name', $payload)) {
                $payload['name'] = trim((string) $payload['name']);
                if ($payload['name'] === '') {
                    throw ValidationException::withMessages([
                        'name' => __('A program name is required.'),
                    ]);
                }
            }

            if (array_key_exists('code', $data)) {
                $payload['code'] = $this->normalizeCode(
                    (string) $data['code'],
                    (int) $program->organization_id,
                    $program->id,
                );
            }

            if (array_key_exists('portfolio_id', $data)) {
                $portfolioId = $data['portfolio_id'] !== null ? (int) $data['portfolio_id'] : null;
                if ($portfolioId) {
                    $this->assertPortfolioBelongs((int) $program->organization_id, $portfolioId);
                }
                $payload['portfolio_id'] = $portfolioId;
            }

            if ($payload !== []) {
                $program->update($payload);
            }

            if (array_key_exists('project_ids', $data) && is_array($data['project_ids'])) {
                $this->syncProjects($program, array_map('intval', $data['project_ids']));
            }

            $program = $program->fresh(['manager', 'portfolio', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProgramUpdated::forModel(
                $program,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $program;
        });
    }

    public function delete(Program $program, User $actor): void
    {
        DB::transaction(function () use ($program, $actor) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(ProgramUpdated::forModel(
                $program,
                ['actor_id' => $actor->id, 'deleted' => true],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $program->projects()->detach();
            $program->delete();
        });
    }

    public function attachProject(Program $program, Project $project, User $actor): Program
    {
        if ((int) $project->organization_id !== (int) $program->organization_id) {
            throw ValidationException::withMessages([
                'project_id' => __('The project does not belong to this organization.'),
            ]);
        }

        return DB::transaction(function () use ($program, $project, $actor) {
            $program->projects()->syncWithoutDetaching([$project->id]);
            $program = $program->fresh(['manager', 'portfolio', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProgramUpdated::forModel(
                $program,
                ['actor_id' => $actor->id, 'attached_project_id' => $project->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $program;
        });
    }

    public function detachProject(Program $program, Project $project, User $actor): Program
    {
        return DB::transaction(function () use ($program, $project, $actor) {
            $program->projects()->detach($project->id);
            $program = $program->fresh(['manager', 'portfolio', 'projects']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProgramUpdated::forModel(
                $program,
                ['actor_id' => $actor->id, 'detached_project_id' => $project->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $program;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Program>
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
        $query = Program::query()
            ->where('organization_id', $organizationId)
            ->with(['manager', 'portfolio'])
            ->withCount('projects')
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = '%'.Str::lower(trim((string) $filters['search'])).'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereRaw('LOWER(name) like ?', [$search])
                    ->orWhereRaw('LOWER(code) like ?', [$search]);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', (int) $filters['portfolio_id']);
        }

        if (! empty($filters['manager_id'])) {
            $query->where('manager_id', (int) $filters['manager_id']);
        }

        return $query;
    }

    /**
     * @param  list<int>  $projectIds
     */
    protected function syncProjects(Program $program, array $projectIds): void
    {
        $validIds = Project::query()
            ->where('organization_id', $program->organization_id)
            ->whereIn('id', $projectIds)
            ->pluck('id')
            ->all();

        $program->projects()->sync($validIds);
    }

    protected function assertPortfolioBelongs(int $organizationId, int $portfolioId): void
    {
        $exists = Portfolio::query()
            ->where('organization_id', $organizationId)
            ->whereKey($portfolioId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'portfolio_id' => __('The selected portfolio is invalid.'),
            ]);
        }
    }

    protected function normalizeCode(string $code, int $organizationId, ?int $ignoreId = null): string
    {
        $normalized = Str::upper(Str::slug($code, '_'));
        $normalized = $normalized !== '' ? $normalized : 'PROGRAM';
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
        $query = Program::query()
            ->where('organization_id', $organizationId)
            ->where('code', $code);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
