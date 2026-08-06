<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCalendarLink;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarSyncService
{
    public function syncProject(Project $project): Collection
    {
        return DB::transaction(function () use ($project) {
            $links = collect();

            if ($project->planned_end_date) {
                $links->push($this->upsertLink([
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'task_id' => null,
                    'milestone_id' => null,
                    'user_id' => $project->manager_id ?? $project->owner_id,
                    'provider' => 'internal',
                    'event_type' => 'project_deadline',
                    'title' => __('Deadline: :project', ['project' => $project->name]),
                    'starts_at' => Carbon::parse($project->planned_end_date)->startOfDay(),
                    'ends_at' => Carbon::parse($project->planned_end_date)->endOfDay(),
                    'due_date' => Carbon::parse($project->planned_end_date)->toDateString(),
                ]));
            }

            $milestones = ProjectMilestone::query()
                ->where('project_id', $project->id)
                ->whereNotNull('due_date')
                ->get();

            foreach ($milestones as $milestone) {
                $links->push($this->upsertLink([
                    'organization_id' => $project->organization_id,
                    'project_id' => $project->id,
                    'task_id' => null,
                    'milestone_id' => $milestone->id,
                    'user_id' => $project->manager_id ?? $project->owner_id,
                    'provider' => 'internal',
                    'event_type' => 'milestone_due',
                    'title' => __('Milestone: :name', ['name' => $milestone->name]),
                    'starts_at' => Carbon::parse($milestone->due_date)->startOfDay(),
                    'ends_at' => Carbon::parse($milestone->due_date)->endOfDay(),
                    'due_date' => Carbon::parse($milestone->due_date)->toDateString(),
                ]));
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
                ->where(function (Builder $query) {
                    $query->whereNotNull('due_date')->orWhereNotNull('due_at');
                })
                ->get();

            foreach ($tasks as $task) {
                $links->push($this->syncTask($task));
            }

            return new Collection($links->filter()->values()->all());
        });
    }

    public function syncTask(Task $task): ?ProjectCalendarLink
    {
        $due = $task->due_at
            ? Carbon::parse($task->due_at)
            : ($task->due_date ? Carbon::parse($task->due_date)->setTime(17, 0) : null);

        if (! $due) {
            ProjectCalendarLink::query()
                ->where('task_id', $task->id)
                ->where('provider', 'internal')
                ->where('event_type', 'task_due')
                ->delete();

            return null;
        }

        return $this->upsertLink([
            'organization_id' => $task->organization_id,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'milestone_id' => $task->milestone_id,
            'user_id' => $task->assigned_to,
            'provider' => 'internal',
            'event_type' => 'task_due',
            'title' => __('Task due: :task', ['task' => $task->title]),
            'starts_at' => $due->copy()->subHour(),
            'ends_at' => $due->copy(),
            'due_date' => $due->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, ProjectCalendarLink>
     */
    public function detectConflicts(User $user, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $organizationId = null): Collection
    {
        $organizationId ??= app(TenantContext::class)->id();

        $query = ProjectCalendarLink::query()
            ->where('user_id', $user->id)
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->orderBy('starts_at')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectCalendarLink>
     */
    public function listCalendarEvents(Organization|int $organization, array $filters = []): Collection
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = ProjectCalendarLink::query()
            ->where('organization_id', $organizationId)
            ->orderBy('starts_at')
            ->orderBy('due_date');

        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['from'])) {
            $query->where(function (Builder $builder) use ($filters) {
                $from = Carbon::parse($filters['from']);
                $builder->where('starts_at', '>=', $from)
                    ->orWhere('due_date', '>=', $from->toDateString());
            });
        }

        if (! empty($filters['to'])) {
            $query->where(function (Builder $builder) use ($filters) {
                $to = Carbon::parse($filters['to']);
                $builder->where('ends_at', '<=', $to)
                    ->orWhere('due_date', '<=', $to->toDateString());
            });
        }

        return $query->get();
    }

    /**
     * Stub: Google Calendar OAuth sync will be implemented later.
     *
     * @param  array<string, mixed>  $options
     */
    public function syncToGoogle(Project|Task $subject, array $options = []): never
    {
        throw ValidationException::withMessages([
            'provider' => __('Google Calendar sync is not configured yet.'),
        ]);
    }

    /**
     * Stub: Outlook Calendar OAuth sync will be implemented later.
     *
     * @param  array<string, mixed>  $options
     */
    public function syncToOutlook(Project|Task $subject, array $options = []): never
    {
        throw ValidationException::withMessages([
            'provider' => __('Outlook Calendar sync is not configured yet.'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertLink(array $attributes): ProjectCalendarLink
    {
        $match = [
            'organization_id' => $attributes['organization_id'],
            'provider' => $attributes['provider'] ?? 'internal',
            'event_type' => $attributes['event_type'],
            'project_id' => $attributes['project_id'] ?? null,
            'task_id' => $attributes['task_id'] ?? null,
            'milestone_id' => $attributes['milestone_id'] ?? null,
        ];

        $payload = [
            ...$attributes,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ];

        $link = ProjectCalendarLink::query()->where($match)->first();

        if ($link) {
            $link->update($payload);

            return $link->fresh();
        }

        return ProjectCalendarLink::query()->create($payload);
    }
}
