<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\ProjectIssue;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectsIssueSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'issues';
    }

    public function label(): string
    {
        return __('Issues');
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

        return ProjectIssue::query()
            ->with(['project:id,name', 'portfolio:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ProjectIssue $issue) => [
                'type' => __('Issue'),
                'label' => $this->label(),
                'title' => $issue->title,
                'subtitle' => collect([
                    $issue->project?->name ?? $issue->portfolio?->name,
                    $issue->status,
                ])->filter()->implode(' · ') ?: null,
                'url' => $issue->project_id
                    ? route('projects.issues.index', $issue->project_id)
                    : route('issues.index'),
                'workspace' => 'projects',
            ]);
    }
}
