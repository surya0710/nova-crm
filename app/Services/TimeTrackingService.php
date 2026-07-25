<?php

namespace App\Services;

use App\Events\TimeLogged;
use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TimeTrackingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function logManual(Task $task, array $data, User $actor): TaskTimeLog
    {
        $this->assertTrackable($task);

        $start = Carbon::parse($data['start_time'] ?? now());
        $end = isset($data['end_time']) ? Carbon::parse($data['end_time']) : null;

        if ($end && $end->lt($start)) {
            throw ValidationException::withMessages([
                'end_time' => __('End time must be after start time.'),
            ]);
        }

        $duration = isset($data['duration_minutes'])
            ? (int) $data['duration_minutes']
            : ($end ? $this->calculateDurationMinutes($start, $end) : 0);

        if ($duration < 0) {
            throw ValidationException::withMessages([
                'duration_minutes' => __('Duration cannot be negative.'),
            ]);
        }

        $log = TaskTimeLog::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : $actor->id,
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => $duration,
            'description' => $data['description'] ?? null,
            'source' => 'manual',
        ]);

        $this->refreshActualHours($task);
        $this->dispatchLogged($log, $actor);

        return $log->fresh();
    }

    public function startTimer(Task $task, User $actor, ?string $description = null): TaskTimeLog
    {
        $this->assertTrackable($task);

        $running = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('user_id', $actor->id)
            ->where('source', 'timer')
            ->whereNull('end_time')
            ->first();

        if ($running) {
            throw ValidationException::withMessages([
                'timer' => __('A timer is already running for this task.'),
            ]);
        }

        return TaskTimeLog::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'start_time' => now(),
            'end_time' => null,
            'duration_minutes' => 0,
            'description' => $description,
            'source' => 'timer',
        ]);
    }

    public function stopTimer(Task $task, User $actor): TaskTimeLog
    {
        $this->assertTrackable($task);

        $running = TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->where('user_id', $actor->id)
            ->where('source', 'timer')
            ->whereNull('end_time')
            ->first();

        if (! $running) {
            throw ValidationException::withMessages([
                'timer' => __('No running timer found for this task.'),
            ]);
        }

        $end = now();
        $duration = $this->calculateDurationMinutes($running->start_time, $end);

        $running->update([
            'end_time' => $end,
            'duration_minutes' => $duration,
        ]);

        $running = $running->fresh();
        $this->refreshActualHours($task);
        $this->dispatchLogged($running, $actor);

        return $running;
    }

    public function calculateDurationMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, (int) $start->diffInMinutes($end));
    }

    /**
     * @return array{total_minutes: int, by_user: list<array{user_id: int, minutes: int}>}
     */
    public function userSummaries(Organization|int $organization, ?int $userId = null, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = TaskTimeLog::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('end_time');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($from) {
            $query->where('start_time', '>=', $from);
        }

        if ($to) {
            $query->where('start_time', '<=', $to);
        }

        $rows = $query
            ->selectRaw('user_id, SUM(duration_minutes) as minutes')
            ->groupBy('user_id')
            ->get();

        $byUser = $rows->map(fn ($row) => [
            'user_id' => (int) $row->user_id,
            'minutes' => (int) $row->minutes,
        ])->values()->all();

        return [
            'total_minutes' => (int) collect($byUser)->sum('minutes'),
            'by_user' => $byUser,
        ];
    }

    protected function refreshActualHours(Task $task): void
    {
        $minutes = (int) TaskTimeLog::query()
            ->where('task_id', $task->id)
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $task->update([
            'actual_hours' => round($minutes / 60, 2),
        ]);
    }

    protected function dispatchLogged(TaskTimeLog $log, User $actor): void
    {
        $runtime = app(WorkflowRuntimeContext::class);
        event(TimeLogged::forModel(
            $log,
            [
                'actor_id' => $actor->id,
                'task_id' => $log->task_id,
                'duration_minutes' => $log->duration_minutes,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));
    }

    protected function assertTrackable(Task $task): void
    {
        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Cannot log time on an archived task.'),
            ]);
        }

        if ($task->isClosed()) {
            throw ValidationException::withMessages([
                'task' => __('Cannot log time on a closed task.'),
            ]);
        }
    }
}
