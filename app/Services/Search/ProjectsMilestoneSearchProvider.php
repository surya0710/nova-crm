<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\ProjectMilestone;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectsMilestoneSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'milestones';
    }

    public function label(): string
    {
        return __('Milestones');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return ProjectMilestone::query()
            ->with('project:id,name')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));
            })
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (ProjectMilestone $milestone) => [
                'type' => __('Milestone'),
                'label' => $this->label(),
                'title' => $milestone->name,
                'subtitle' => collect([
                    $milestone->project?->name,
                    $milestone->due_date?->format('M j, Y'),
                    $milestone->status_label,
                ])->filter()->implode(' · ') ?: null,
                'url' => $milestone->project_id
                    ? route('projects.milestones.index', $milestone->project_id)
                    : route('projects.milestones.hub'),
                'workspace' => 'projects',
            ]);
    }
}
