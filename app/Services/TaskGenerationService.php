<?php

namespace App\Services;

use App\Events\TaskRecurrenceGenerated;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklist;
use App\Models\TaskLabel;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskGenerationService
{
    public function __construct(
        protected ?TaskService $tasks = null,
        protected ?TaskRecurrenceService $recurrences = null,
    ) {}

    /**
     * Process all due recurrence schedules.
     *
     * @return list<Task>
     */
    public function generateDue(?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $generated = [];

        $due = TaskRecurrence::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $asOf)
            ->orderBy('next_run_at')
            ->limit(500)
            ->get();

        foreach ($due as $recurrence) {
            $task = $this->generateFrom($recurrence);
            if ($task) {
                $generated[] = $task;
            }
        }

        return $generated;
    }

    public function generateFrom(TaskRecurrence $recurrence, ?User $actor = null): ?Task
    {
        if (! $recurrence->is_active) {
            return null;
        }

        if ($recurrence->end_type === 'occurrences') {
            $limit = (int) ($recurrence->occurrences ?? 0);
            if ($limit > 0 && (int) $recurrence->generated_count >= $limit) {
                $recurrence->update(['is_active' => false, 'next_run_at' => null]);

                return null;
            }
        }

        if ($recurrence->end_type === 'date' && $recurrence->end_date) {
            if (Carbon::parse($recurrence->next_run_at ?? now())->gt(Carbon::parse($recurrence->end_date)->endOfDay())) {
                $recurrence->update(['is_active' => false, 'next_run_at' => null]);

                return null;
            }
        }

        $template = $recurrence->task ?? Task::query()->find($recurrence->task_id);

        if (! $template) {
            $recurrence->update(['is_active' => false]);

            return null;
        }

        $actor ??= $template->creator
            ?? ($template->assigned_to ? User::query()->find($template->assigned_to) : null)
            ?? User::query()->find($template->created_by);

        if (! $actor) {
            return null;
        }

        return DB::transaction(function () use ($recurrence, $template, $actor) {
            $runAt = $recurrence->next_run_at
                ? Carbon::parse($recurrence->next_run_at)
                : now();

            if ($recurrence->skip_holidays && $this->isHoliday((int) $recurrence->organization_id, $runAt)) {
                $next = $this->recurrences()->calculateNextRunAt($recurrence, $runAt);
                $recurrence->update(['next_run_at' => $next, 'is_active' => $next !== null]);

                return null;
            }

            $dueDate = $runAt->toDateString();
            $dueAt = $runAt->copy();

            $clone = $this->tasks()->createWorkManagement([
                'organization_id' => $template->organization_id,
                'project_id' => $template->project_id,
                'parent_task_id' => $template->id,
                'milestone_id' => $template->milestone_id,
                'title' => $template->title,
                'description' => $template->description,
                'priority' => $template->priority,
                'priority_id' => $template->priority_id,
                'status' => 'pending',
                'assigned_to' => $template->assigned_to,
                'estimated_hours' => $template->estimated_hours,
                'start_date' => $dueDate,
                'due_date' => $dueDate,
                'due_at' => $dueAt,
                'metadata' => $template->metadata,
                'settings' => $template->settings,
                'taskable_type' => $template->taskable_type,
                'taskable_id' => $template->taskable_id,
            ], $actor);

            $this->cloneChecklists($template, $clone);
            $this->cloneLabels($template, $clone);

            if ($recurrence->copy_attachments) {
                $this->cloneAttachments($template, $clone, $actor);
            }

            $generatedCount = (int) $recurrence->generated_count + 1;
            $next = $this->recurrences()->calculateNextRunAtFromValues(
                array_merge($recurrence->attributesToArray(), ['generated_count' => $generatedCount]),
                $runAt,
            );

            $stillActive = true;
            if ($recurrence->end_type === 'occurrences' && (int) $recurrence->occurrences > 0 && $generatedCount >= (int) $recurrence->occurrences) {
                $stillActive = false;
                $next = null;
            }
            if ($next === null) {
                $stillActive = false;
            }

            $recurrence->update([
                'last_generated_at' => now(),
                'next_run_at' => $next,
                'generated_count' => $generatedCount,
                'is_active' => $stillActive,
            ]);

            $runtime = app(WorkflowRuntimeContext::class);
            event(TaskRecurrenceGenerated::forModel(
                $recurrence->fresh(),
                [
                    'actor_id' => $actor->id,
                    'template_task_id' => $template->id,
                    'generated_task_id' => $clone->id,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $clone->fresh(['assignee', 'checklists']);
        });
    }

    protected function cloneChecklists(Task $template, Task $clone): void
    {
        $items = TaskChecklist::query()
            ->where('task_id', $template->id)
            ->orderBy('sequence')
            ->get();

        foreach ($items as $item) {
            TaskChecklist::query()->create([
                'organization_id' => $clone->organization_id,
                'task_id' => $clone->id,
                'title' => $item->title,
                'sequence' => $item->sequence,
                'is_completed' => false,
                'completed_by' => null,
                'completed_at' => null,
            ]);
        }
    }

    protected function cloneLabels(Task $template, Task $clone): void
    {
        $labelIds = TaskLabel::query()->where('task_id', $template->id)->pluck('label_id');

        foreach ($labelIds as $labelId) {
            TaskLabel::query()->firstOrCreate([
                'task_id' => $clone->id,
                'label_id' => $labelId,
            ]);
        }
    }

    protected function cloneAttachments(Task $template, Task $clone, User $actor): void
    {
        $attachments = TaskAttachment::query()->where('task_id', $template->id)->get();

        foreach ($attachments as $attachment) {
            $newPath = $attachment->file_path;

            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                $extension = pathinfo($attachment->file_path, PATHINFO_EXTENSION);
                $newPath = 'task-attachments/'.$clone->id.'/'.uniqid('att_', true).($extension ? '.'.$extension : '');
                Storage::disk('public')->copy($attachment->file_path, $newPath);
            }

            TaskAttachment::query()->create([
                'organization_id' => $clone->organization_id,
                'task_id' => $clone->id,
                'file_name' => $attachment->file_name,
                'file_path' => $newPath,
                'mime_type' => $attachment->mime_type,
                'file_size' => $attachment->file_size,
                'uploaded_by' => $actor->id,
            ]);
        }
    }

    protected function isHoliday(int $organizationId, Carbon $date): bool
    {
        if (! class_exists(\App\Models\Holiday::class)) {
            return false;
        }

        return \App\Models\Holiday::query()
            ->where('organization_id', $organizationId)
            ->where(function ($builder) use ($date) {
                $builder->whereDate('holiday_date', $date->toDateString())
                    ->orWhere(function ($recurring) use ($date) {
                        $recurring->where('is_recurring', true)
                            ->whereMonth('holiday_date', $date->month)
                            ->whereDay('holiday_date', $date->day);
                    });
            })
            ->exists();
    }

    protected function tasks(): TaskService
    {
        return $this->tasks ??= app(TaskService::class);
    }

    protected function recurrences(): TaskRecurrenceService
    {
        return $this->recurrences ??= app(TaskRecurrenceService::class);
    }
}
