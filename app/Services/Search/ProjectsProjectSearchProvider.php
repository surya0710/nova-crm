<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\MetadataSearchService;
use Illuminate\Support\Collection;

class ProjectsProjectSearchProvider implements SearchProviderInterface
{
    public function __construct(protected MetadataSearchService $metadataSearch) {}

    public function key(): string
    {
        return 'projects';
    }

    public function label(): string
    {
        return __('Projects');
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

        return Project::query()
            ->with(['client', 'owner', 'manager'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('project_number', 'like', "%{$query}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('manager', fn ($m) => $m->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'project', $query);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Project $project) => [
                'type' => __('Project'),
                'label' => $this->label(),
                'title' => $project->name,
                'subtitle' => $project->project_number,
                'url' => route('projects.show', $project),
                'workspace' => 'projects',
            ]);
    }
}
