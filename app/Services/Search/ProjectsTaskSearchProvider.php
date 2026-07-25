<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\MetadataSearchService;
use Illuminate\Support\Collection;

class ProjectsTaskSearchProvider implements SearchProviderInterface
{
    public function __construct(protected MetadataSearchService $metadataSearch) {}

    public function key(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return __('Tasks');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Task::query()
            ->with(['assignee', 'project'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('task_number', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('assignee', fn ($a) => $a->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'task', $query);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Task $task) => [
                'type' => __('Task'),
                'label' => $this->label(),
                'title' => $task->title,
                'subtitle' => collect([$task->task_number, $task->project?->name])->filter()->implode(' · ') ?: null,
                'url' => route('tasks.show', $task),
                'workspace' => 'projects',
            ]);
    }
}
