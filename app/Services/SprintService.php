<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SprintService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $actor): Sprint
    {
        $payload = $this->normalize($organization, $data);

        return Sprint::query()->create([
            ...$payload,
            'organization_id' => $organization->id,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sprint $sprint, array $data, User $actor): Sprint
    {
        $payload = $this->normalize($sprint->organization, $data, $sprint);
        $sprint->update($payload);

        return $sprint->fresh(['project', 'tasks']);
    }

    public function delete(Sprint $sprint, User $actor): void
    {
        DB::transaction(function () use ($sprint): void {
            Task::query()
                ->where('organization_id', $sprint->organization_id)
                ->where('sprint_id', $sprint->id)
                ->update(['sprint_id' => null]);

            $sprint->delete();
        });
    }

    public function assignTask(Task $task, ?Sprint $sprint, User $actor): Task
    {
        if ($sprint && (int) $sprint->organization_id !== (int) $task->organization_id) {
            throw ValidationException::withMessages([
                'sprint_id' => __('Sprint must belong to the same organization.'),
            ]);
        }

        return app(TaskService::class)->update($task, [
            'sprint_id' => $sprint?->id,
        ], $actor);
    }

    /**
     * @return Collection<int, Sprint>
     */
    public function forOrganization(Organization $organization, ?int $projectId = null): Collection
    {
        return Sprint::query()
            ->where('organization_id', $organization->id)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('start_date')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalize(Organization $organization, array $data, ?Sprint $existing = null): array
    {
        $name = trim((string) ($data['name'] ?? $existing?->name ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => __('Sprint name is required.')]);
        }

        $status = (string) ($data['status'] ?? $existing?->status ?? 'planned');
        if (! array_key_exists($status, config('tasks.sprint_statuses', []))) {
            throw ValidationException::withMessages(['status' => __('Invalid sprint status.')]);
        }

        $projectId = array_key_exists('project_id', $data)
            ? ($data['project_id'] ? (int) $data['project_id'] : null)
            : $existing?->project_id;

        if ($projectId) {
            $exists = Project::query()
                ->where('organization_id', $organization->id)
                ->whereKey($projectId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages(['project_id' => __('Invalid project.')]);
            }
        }

        return [
            'name' => $name,
            'goal' => filled($data['goal'] ?? null) ? trim((string) $data['goal']) : ($existing?->goal),
            'start_date' => $data['start_date'] ?? $existing?->start_date?->toDateString(),
            'end_date' => $data['end_date'] ?? $existing?->end_date?->toDateString(),
            'status' => $status,
            'project_id' => $projectId,
            'sort_order' => (int) ($data['sort_order'] ?? $existing?->sort_order ?? 0),
        ];
    }
}
