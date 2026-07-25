<?php

namespace App\Services;

use App\Events\ChecklistCompleted;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChecklistService
{
    public function __construct(protected ?TaskService $tasks = null) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Task $task, array $data, User $actor): TaskChecklist
    {
        $this->assertTaskWritable($task);

        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => __('A checklist item title is required.'),
            ]);
        }

        $sequence = isset($data['sequence'])
            ? (int) $data['sequence']
            : ((int) $task->checklists()->max('sequence')) + 1;

        $item = TaskChecklist::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'title' => $title,
            'sequence' => $sequence,
            'is_completed' => (bool) ($data['is_completed'] ?? false),
            'completed_by' => ($data['is_completed'] ?? false) ? $actor->id : null,
            'completed_at' => ($data['is_completed'] ?? false) ? now() : null,
        ]);

        $this->tasks()->calculateProgress($task->fresh());

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaskChecklist $item, array $data, User $actor): TaskChecklist
    {
        $task = $item->task;
        $this->assertTaskWritable($task);

        $payload = [];

        if (array_key_exists('title', $data)) {
            $title = trim((string) $data['title']);

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => __('A checklist item title is required.'),
                ]);
            }

            $payload['title'] = $title;
        }

        if (array_key_exists('sequence', $data)) {
            $payload['sequence'] = (int) $data['sequence'];
        }

        if ($payload !== []) {
            $item->update($payload);
        }

        return $item->fresh();
    }

    public function delete(TaskChecklist $item, User $actor): void
    {
        $task = $item->task;
        $this->assertTaskWritable($task);

        $item->delete();
        $this->tasks()->calculateProgress($task->fresh());
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Task $task, array $orderedIds, User $actor): void
    {
        $this->assertTaskWritable($task);

        DB::transaction(function () use ($task, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                TaskChecklist::query()
                    ->where('task_id', $task->id)
                    ->whereKey((int) $id)
                    ->update(['sequence' => $index + 1]);
            }
        });
    }

    public function complete(TaskChecklist $item, User $actor, bool $completed = true): TaskChecklist
    {
        $task = $item->task;
        $this->assertTaskWritable($task);

        if ($item->is_completed === $completed) {
            return $item;
        }

        $item->update([
            'is_completed' => $completed,
            'completed_by' => $completed ? $actor->id : null,
            'completed_at' => $completed ? now() : null,
        ]);

        $item = $item->fresh();

        if ($completed) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(ChecklistCompleted::forModel(
                $item,
                [
                    'actor_id' => $actor->id,
                    'task_id' => $task->id,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        $this->tasks()->calculateProgress($task->fresh());

        return $item;
    }

    protected function assertTaskWritable(Task $task): void
    {
        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }

        if ($task->isClosed()) {
            throw ValidationException::withMessages([
                'task' => __('Closed tasks cannot be modified.'),
            ]);
        }
    }

    protected function tasks(): TaskService
    {
        return $this->tasks ??= app(TaskService::class);
    }
}
