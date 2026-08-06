<?php

namespace App\Services;

use App\Events\ProjectLabelCreated;
use App\Events\ProjectLabelDeleted;
use App\Events\ProjectLabelUpdated;
use App\Events\TaskLabelAttached;
use App\Events\TaskLabelDetached;
use App\Models\Organization;
use App\Models\ProjectLabel;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectLabelService
{
    /**
     * @var list<array{name: string, color: string, description: string}>
     */
    public const DEFAULT_LABELS = [
        ['name' => 'Urgent', 'color' => '#dc2626', 'description' => 'Needs immediate attention'],
        ['name' => 'Backend', 'color' => '#2563eb', 'description' => 'Backend / API work'],
        ['name' => 'Frontend', 'color' => '#7c3aed', 'description' => 'UI / frontend work'],
        ['name' => 'Bug', 'color' => '#ea580c', 'description' => 'Defect or regression'],
        ['name' => 'UI', 'color' => '#db2777', 'description' => 'Visual or UX change'],
        ['name' => 'Enhancement', 'color' => '#059669', 'description' => 'Improvement to existing behavior'],
        ['name' => 'Research', 'color' => '#0891b2', 'description' => 'Spike or investigation'],
        ['name' => 'Blocked', 'color' => '#57534e', 'description' => 'Waiting on a dependency'],
        ['name' => 'QA', 'color' => '#ca8a04', 'description' => 'Testing / quality assurance'],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ProjectLabel
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => __('A label name is required.'),
                ]);
            }

            $this->assertUniqueName($organizationId, $name);

            $label = ProjectLabel::query()->create([
                'organization_id' => $organizationId,
                'name' => $name,
                'color' => $data['color'] ?? '#64748b',
                'description' => $data['description'] ?? null,
                'is_system' => (bool) ($data['is_system'] ?? false),
            ]);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectLabelCreated::forModel(
                $label,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $label->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectLabel $label, array $data, User $actor): ProjectLabel
    {
        return DB::transaction(function () use ($label, $data, $actor) {
            if ($label->is_system && array_key_exists('name', $data)) {
                throw ValidationException::withMessages([
                    'name' => __('System labels cannot be renamed.'),
                ]);
            }

            $payload = [];

            if (array_key_exists('name', $data)) {
                $name = trim((string) $data['name']);

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'name' => __('A label name is required.'),
                    ]);
                }

                $this->assertUniqueName((int) $label->organization_id, $name, $label->id);
                $payload['name'] = $name;
            }

            if (array_key_exists('color', $data)) {
                $payload['color'] = $data['color'];
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }

            if ($payload !== []) {
                $label->update($payload);
            }

            $label = $label->fresh();

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectLabelUpdated::forModel(
                $label,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $label;
        });
    }

    public function delete(ProjectLabel $label, User $actor): void
    {
        if ($label->is_system) {
            throw ValidationException::withMessages([
                'label' => __('System labels cannot be deleted.'),
            ]);
        }

        DB::transaction(function () use ($label, $actor) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectLabelDeleted::forModel(
                $label,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            TaskLabel::query()->where('label_id', $label->id)->delete();
            $label->delete();
        });
    }

    public function attach(Task $task, ProjectLabel $label, User $actor): TaskLabel
    {
        if ((int) $label->organization_id !== (int) $task->organization_id) {
            throw ValidationException::withMessages([
                'label_id' => __('The label does not belong to this organization.'),
            ]);
        }

        $existing = TaskLabel::query()
            ->where('task_id', $task->id)
            ->where('label_id', $label->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $pivot = TaskLabel::query()->create([
            'task_id' => $task->id,
            'label_id' => $label->id,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        // Subject is the task (task_labels has no organization_id).
        event(TaskLabelAttached::forModel(
            $task,
            [
                'actor_id' => $actor->id,
                'task_id' => $task->id,
                'label_id' => $label->id,
                'task_label_id' => $pivot->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $pivot->fresh();
    }

    public function detach(Task $task, ProjectLabel $label, User $actor): void
    {
        $pivot = TaskLabel::query()
            ->where('task_id', $task->id)
            ->where('label_id', $label->id)
            ->first();

        if (! $pivot) {
            return;
        }

        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskLabelDetached::forModel(
            $task,
            [
                'actor_id' => $actor->id,
                'task_id' => $task->id,
                'label_id' => $label->id,
                'task_label_id' => $pivot->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $pivot->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectLabel>
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
        $query = ProjectLabel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $search = '%'.Str::lower(trim((string) $filters['search'])).'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereRaw('LOWER(name) like ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) like ?', [$search]);
            });
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== null && $filters['is_system'] !== '') {
            $query->where('is_system', (bool) $filters['is_system']);
        }

        if (! empty($filters['task_id'])) {
            $labelIds = TaskLabel::query()
                ->where('task_id', (int) $filters['task_id'])
                ->pluck('label_id');
            $query->whereKey($labelIds);
        }

        return $query;
    }

    public function seedDefaults(Organization|int $organization): void
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        foreach (self::DEFAULT_LABELS as $default) {
            ProjectLabel::query()->firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'name' => $default['name'],
                ],
                [
                    'color' => $default['color'],
                    'description' => $default['description'],
                    'is_system' => true,
                ],
            );
        }
    }

    protected function assertUniqueName(int $organizationId, string $name, ?int $ignoreId = null): void
    {
        $query = ProjectLabel::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => __('A label with this name already exists.'),
            ]);
        }
    }
}
