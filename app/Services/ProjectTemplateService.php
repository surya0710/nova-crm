<?php

namespace App\Services;

use App\Events\ProjectTemplateCreated;
use App\Events\ProjectTemplateDeleted;
use App\Events\ProjectTemplateUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectLabel;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskLabel;
use App\Models\TemplateChecklist;
use App\Models\TemplateLabel;
use App\Models\TemplateMilestone;
use App\Models\TemplateTask;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ProjectTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = array_key_exists('organization_id', $data)
                ? ($data['organization_id'] !== null ? (int) $data['organization_id'] : null)
                : app(TenantContext::class)->id();

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => __('A template name is required.'),
                ]);
            }

            $template = ProjectTemplate::query()->create([
                'organization_id' => $organizationId,
                'name' => $name,
                'slug' => $data['slug'] ?? $this->generateSlug($name, $organizationId),
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'industry' => $data['industry'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'source_project_id' => $data['source_project_id'] ?? null,
                'created_by' => $actor->id,
                'is_system' => (bool) ($data['is_system'] ?? false),
                'is_favorite' => (bool) ($data['is_favorite'] ?? false),
                'version' => (int) ($data['version'] ?? 1),
                'usage_count' => 0,
                'defaults' => $data['defaults'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectTemplateCreated::forModel(
                $template,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $template->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectTemplate $template, array $data, User $actor): ProjectTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            if ($template->is_system && ! ($data['allow_system_edit'] ?? false)) {
                throw ValidationException::withMessages([
                    'template' => __('System templates cannot be modified.'),
                ]);
            }

            $payload = collect($data)->only([
                'name',
                'description',
                'category',
                'industry',
                'department_id',
                'defaults',
                'metadata',
                'is_favorite',
            ])->all();

            if (array_key_exists('name', $payload)) {
                $name = trim((string) $payload['name']);
                if ($name === '') {
                    throw ValidationException::withMessages([
                        'name' => __('A template name is required.'),
                    ]);
                }
                $payload['name'] = $name;
                $payload['slug'] = $data['slug'] ?? $this->generateSlug($name, $template->organization_id, $template->id);
            } elseif (array_key_exists('slug', $data)) {
                $payload['slug'] = Str::slug((string) $data['slug']);
            }

            if ($payload !== []) {
                $template->update($payload);
            }

            $template = $template->fresh();

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectTemplateUpdated::forModel(
                $template,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $template;
        });
    }

    public function delete(ProjectTemplate $template, User $actor): void
    {
        if ($template->is_system) {
            throw ValidationException::withMessages([
                'template' => __('System templates cannot be deleted.'),
            ]);
        }

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectTemplateDeleted::forModel(
            $template,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $template->delete();
    }

    public function duplicate(ProjectTemplate $template, User $actor, ?array $overrides = null): ProjectTemplate
    {
        return DB::transaction(function () use ($template, $actor, $overrides) {
            $template->loadMissing(['templateMilestones', 'templateTasks.checklists', 'templateLabels']);

            $copy = $this->create([
                'organization_id' => $overrides['organization_id'] ?? $template->organization_id,
                'name' => $overrides['name'] ?? ($template->name.' (Copy)'),
                'description' => $overrides['description'] ?? $template->description,
                'category' => $overrides['category'] ?? $template->category,
                'industry' => $overrides['industry'] ?? $template->industry,
                'department_id' => $overrides['department_id'] ?? $template->department_id,
                'defaults' => $overrides['defaults'] ?? $template->defaults,
                'metadata' => $overrides['metadata'] ?? $template->metadata,
                'is_system' => false,
                'is_favorite' => false,
            ], $actor);

            $milestoneMap = [];
            foreach ($template->templateMilestones as $milestone) {
                $cloned = TemplateMilestone::query()->create([
                    'project_template_id' => $copy->id,
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'sequence' => $milestone->sequence,
                    'offset_days' => $milestone->offset_days,
                    'duration_days' => $milestone->duration_days,
                    'metadata' => $milestone->metadata,
                ]);
                $milestoneMap[$milestone->id] = $cloned->id;
            }

            $taskMap = [];
            foreach ($template->templateTasks as $task) {
                $clonedTask = TemplateTask::query()->create([
                    'project_template_id' => $copy->id,
                    'template_milestone_id' => $task->template_milestone_id
                        ? ($milestoneMap[$task->template_milestone_id] ?? null)
                        : null,
                    'parent_template_task_id' => null,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'offset_days' => $task->offset_days,
                    'duration_days' => $task->duration_days,
                    'estimated_hours' => $task->estimated_hours,
                    'assignee_role' => $task->assignee_role,
                    'sort_order' => $task->sort_order,
                    'metadata' => $task->metadata,
                ]);
                $taskMap[$task->id] = $clonedTask->id;

                foreach ($task->checklists as $checklist) {
                    TemplateChecklist::query()->create([
                        'template_task_id' => $clonedTask->id,
                        'title' => $checklist->title,
                        'sort_order' => $checklist->sort_order,
                    ]);
                }
            }

            foreach ($template->templateTasks as $task) {
                if ($task->parent_template_task_id && isset($taskMap[$task->id], $taskMap[$task->parent_template_task_id])) {
                    TemplateTask::query()->whereKey($taskMap[$task->id])->update([
                        'parent_template_task_id' => $taskMap[$task->parent_template_task_id],
                    ]);
                }
            }

            foreach ($template->templateLabels as $label) {
                TemplateLabel::query()->create([
                    'project_template_id' => $copy->id,
                    'template_task_id' => $label->template_task_id
                        ? ($taskMap[$label->template_task_id] ?? null)
                        : null,
                    'name' => $label->name,
                    'color' => $label->color,
                ]);
            }

            return $copy->fresh(['templateMilestones', 'templateTasks', 'templateLabels']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveFromProject(Project $project, array $data, User $actor): ProjectTemplate
    {
        return DB::transaction(function () use ($project, $data, $actor) {
            $project->loadMissing(['milestones', 'tasks']);

            $template = $this->create([
                'organization_id' => $project->organization_id,
                'name' => $data['name'] ?? ($project->name.' Template'),
                'description' => $data['description'] ?? $project->description,
                'category' => $data['category'] ?? null,
                'industry' => $data['industry'] ?? null,
                'department_id' => $data['department_id'] ?? $project->department_id,
                'source_project_id' => $project->id,
                'defaults' => $data['defaults'] ?? [
                    'priority' => $project->priority,
                    'project_type_id' => $project->project_type_id,
                    'category_id' => $project->category_id,
                ],
                'metadata' => $data['metadata'] ?? null,
            ], $actor);

            $start = $project->start_date ? \Carbon\Carbon::parse($project->start_date) : null;
            $milestoneMap = [];

            foreach ($project->milestones as $milestone) {
                $offset = 0;
                if ($start && $milestone->due_date) {
                    $offset = max(0, $start->diffInDays(\Carbon\Carbon::parse($milestone->due_date), false));
                }

                $cloned = TemplateMilestone::query()->create([
                    'project_template_id' => $template->id,
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'sequence' => $milestone->sequence,
                    'offset_days' => $offset,
                    'duration_days' => null,
                    'metadata' => null,
                ]);
                $milestoneMap[$milestone->id] = $cloned->id;
            }

            $tasks = Task::query()
                ->where('organization_id', $project->organization_id)
                ->where(function (Builder $query) use ($project) {
                    $query->where('project_id', $project->id)
                        ->orWhere(function (Builder $morph) use ($project) {
                            $morph->where('taskable_type', $project->getMorphClass())
                                ->where('taskable_id', $project->id);
                        });
                })
                ->whereNull('parent_task_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $taskMap = [];

            foreach ($tasks as $index => $task) {
                $offset = 0;
                if ($start && $task->due_date) {
                    $offset = max(0, $start->diffInDays(\Carbon\Carbon::parse($task->due_date), false));
                } elseif ($start && $task->due_at) {
                    $offset = max(0, $start->diffInDays(\Carbon\Carbon::parse($task->due_at), false));
                }

                $templateTask = TemplateTask::query()->create([
                    'project_template_id' => $template->id,
                    'template_milestone_id' => $task->milestone_id
                        ? ($milestoneMap[$task->milestone_id] ?? null)
                        : null,
                    'parent_template_task_id' => null,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority ?? 'medium',
                    'offset_days' => $offset,
                    'duration_days' => null,
                    'estimated_hours' => $task->estimated_hours ? (int) $task->estimated_hours : null,
                    'assignee_role' => null,
                    'sort_order' => $task->sort_order ?? $index,
                    'metadata' => $task->metadata,
                ]);
                $taskMap[$task->id] = $templateTask->id;

                $checklists = TaskChecklist::query()
                    ->where('task_id', $task->id)
                    ->orderBy('sequence')
                    ->get();

                foreach ($checklists as $checklist) {
                    TemplateChecklist::query()->create([
                        'template_task_id' => $templateTask->id,
                        'title' => $checklist->title,
                        'sort_order' => $checklist->sequence,
                    ]);
                }

                $labels = TaskLabel::query()
                    ->where('task_id', $task->id)
                    ->get();

                foreach ($labels as $pivot) {
                    $label = ProjectLabel::query()->find($pivot->label_id);
                    if (! $label) {
                        continue;
                    }

                    TemplateLabel::query()->create([
                        'project_template_id' => $template->id,
                        'template_task_id' => $templateTask->id,
                        'name' => $label->name,
                        'color' => $label->color ?? '#64748b',
                    ]);
                }
            }

            return $template->fresh(['templateMilestones', 'templateTasks.checklists', 'templateLabels']);
        });
    }

    public function toggleFavorite(ProjectTemplate $template, User $actor): ProjectTemplate
    {
        $template->update(['is_favorite' => ! $template->is_favorite]);
        $template = $template->fresh();

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectTemplateUpdated::forModel(
            $template,
            ['actor_id' => $actor->id, 'changes' => ['is_favorite']],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $template;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectTemplate>
     */
    public function list(Organization|int|null $organization, array $filters = []): Collection
    {
        $organizationId = $organization instanceof Organization
            ? $organization->id
            : ($organization ?? app(TenantContext::class)->id());

        $query = ProjectTemplate::query()
            ->withoutGlobalScopes()
            ->orderBy('name');

        if ($organizationId) {
            $query->where(function (Builder $builder) use ($organizationId) {
                $builder->where('organization_id', $organizationId)
                    ->orWhere(function (Builder $system) {
                        $system->whereNull('organization_id')->where('is_system', true);
                    });
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (! empty($filters['favorites'])) {
            $query->where('is_favorite', true);
        }

        if (array_key_exists('system', $filters) && $filters['system'] !== null && $filters['system'] !== '') {
            $query->where('is_system', (bool) $filters['system']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.Str::lower(trim((string) $filters['search'])).'%';
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereRaw('LOWER(name) like ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) like ?', [$search]);
            });
        }

        return $query->get();
    }

    public function generateSlug(string $name, ?int $organizationId, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug !== '' ? $slug : 'template';
        $candidate = $original;
        $count = 1;

        while ($this->slugExists($organizationId, $candidate, $ignoreId)) {
            $candidate = $original.'-'.$count;
            $count++;
        }

        return $candidate;
    }

    protected function slugExists(?int $organizationId, string $slug, ?int $ignoreId): bool
    {
        $query = ProjectTemplate::query()->where('slug', $slug);

        if ($organizationId === null) {
            $query->whereNull('organization_id');
        } else {
            $query->where('organization_id', $organizationId);
        }

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
