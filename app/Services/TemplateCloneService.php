<?php

namespace App\Services;

use App\Events\ProjectCreatedFromTemplate;
use App\Models\Project;
use App\Models\ProjectLabel;
use App\Models\ProjectMilestone;
use App\Models\ProjectTemplate;
use App\Models\TaskChecklist;
use App\Models\TaskLabel;
use App\Models\TemplateTask;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TemplateCloneService
{
    public function __construct(
        protected ?ProjectService $projects = null,
        protected ?TaskService $tasks = null,
        protected ?ProjectLabelService $labels = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProjectFromTemplate(ProjectTemplate $template, array $data, User $actor): Project
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $template->loadMissing(['templateMilestones', 'templateTasks.checklists', 'templateLabels']);

            $organizationId = (int) ($data['organization_id'] ?? $template->organization_id ?? app(TenantContext::class)->id());

            if (! $organizationId) {
                throw ValidationException::withMessages([
                    'organization_id' => __('An organization is required.'),
                ]);
            }

            $defaults = is_array($template->defaults) ? $template->defaults : [];
            $startDate = isset($data['start_date'])
                ? Carbon::parse($data['start_date'])
                : now()->startOfDay();

            $projectPayload = [
                'organization_id' => $organizationId,
                'name' => $data['name'] ?? $template->name,
                'description' => $data['description'] ?? $template->description,
                'objective' => $data['objective'] ?? null,
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'manager_id' => $data['manager_id'] ?? $data['owner_id'] ?? $actor->id,
                'client_id' => $data['client_id'] ?? null,
                'department_id' => $data['department_id'] ?? $template->department_id,
                'category_id' => $data['category_id'] ?? ($defaults['category_id'] ?? null),
                'project_type_id' => $data['project_type_id'] ?? ($defaults['project_type_id'] ?? null),
                'priority' => $data['priority'] ?? ($defaults['priority'] ?? 'medium'),
                'start_date' => $startDate->toDateString(),
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'estimated_budget' => $data['estimated_budget'] ?? null,
                'metadata' => $data['metadata'] ?? $template->metadata,
                'settings' => $data['settings'] ?? null,
            ];

            $project = $this->projects()->create($projectPayload, $actor);

            $milestoneMap = [];
            foreach ($template->templateMilestones as $milestone) {
                $due = $startDate->copy()->addDays((int) $milestone->offset_days);
                $created = ProjectMilestone::query()->create([
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'sequence' => $milestone->sequence,
                    'due_date' => $due->toDateString(),
                    'status' => 'pending',
                ]);
                $milestoneMap[$milestone->id] = $created->id;
            }

            $this->labels()->seedDefaults($organizationId);

            $labelCache = [];
            foreach ($template->templateLabels as $templateLabel) {
                if ($templateLabel->template_task_id) {
                    continue;
                }

                $labelCache[$templateLabel->name] = ProjectLabel::query()->firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'name' => $templateLabel->name,
                    ],
                    [
                        'color' => $templateLabel->color ?? '#64748b',
                        'is_system' => false,
                    ],
                );
            }

            $taskMap = [];
            $orderedTasks = $template->templateTasks->sortBy('sort_order')->values();

            foreach ($orderedTasks as $templateTask) {
                /** @var TemplateTask $templateTask */
                $due = $startDate->copy()->addDays((int) $templateTask->offset_days);
                $task = $this->tasks()->createWorkManagement([
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'milestone_id' => $templateTask->template_milestone_id
                        ? ($milestoneMap[$templateTask->template_milestone_id] ?? null)
                        : null,
                    'parent_task_id' => $templateTask->parent_template_task_id
                        ? ($taskMap[$templateTask->parent_template_task_id] ?? null)
                        : null,
                    'title' => $templateTask->title,
                    'description' => $templateTask->description,
                    'priority' => $templateTask->priority ?? 'medium',
                    'estimated_hours' => $templateTask->estimated_hours,
                    'due_date' => $due->toDateString(),
                    'due_at' => $due->copy()->setTime(17, 0),
                    'sort_order' => $templateTask->sort_order,
                    'metadata' => $templateTask->metadata,
                    'taskable_type' => $project->getMorphClass(),
                    'taskable_id' => $project->id,
                ], $actor);

                $taskMap[$templateTask->id] = $task->id;

                foreach ($templateTask->checklists as $checklist) {
                    TaskChecklist::query()->create([
                        'organization_id' => $project->organization_id,
                        'task_id' => $task->id,
                        'title' => $checklist->title,
                        'sequence' => $checklist->sort_order,
                        'is_completed' => false,
                    ]);
                }

                $taskLabels = $template->templateLabels
                    ->where('template_task_id', $templateTask->id);

                foreach ($taskLabels as $templateLabel) {
                    $label = $labelCache[$templateLabel->name] ?? ProjectLabel::query()->firstOrCreate(
                        [
                            'organization_id' => $organizationId,
                            'name' => $templateLabel->name,
                        ],
                        [
                            'color' => $templateLabel->color ?? '#64748b',
                            'is_system' => false,
                        ],
                    );
                    $labelCache[$templateLabel->name] = $label;

                    TaskLabel::query()->firstOrCreate([
                        'task_id' => $task->id,
                        'label_id' => $label->id,
                    ]);
                }
            }

            $template->increment('usage_count');

            $project = $project->fresh(['milestones', 'owner', 'manager']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectCreatedFromTemplate::forModel(
                $project,
                [
                    'actor_id' => $actor->id,
                    'template_id' => $template->id,
                    'template_slug' => $template->slug,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $project;
        });
    }

    protected function projects(): ProjectService
    {
        return $this->projects ??= app(ProjectService::class);
    }

    protected function tasks(): TaskService
    {
        return $this->tasks ??= app(TaskService::class);
    }

    protected function labels(): ProjectLabelService
    {
        return $this->labels ??= app(ProjectLabelService::class);
    }
}
