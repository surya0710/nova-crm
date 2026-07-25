<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\ProjectRisk;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectsRiskSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'risks';
    }

    public function label(): string
    {
        return __('Risks');
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

        return ProjectRisk::query()
            ->with(['project:id,name', 'portfolio:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->orderByDesc('severity')
            ->limit($limit)
            ->get()
            ->map(fn (ProjectRisk $risk) => [
                'type' => __('Risk'),
                'label' => $this->label(),
                'title' => $risk->title,
                'subtitle' => collect([
                    $risk->project?->name ?? $risk->portfolio?->name,
                    $risk->status,
                ])->filter()->implode(' · ') ?: null,
                'url' => $risk->project_id
                    ? route('projects.risks.index', $risk->project_id)
                    : route('risks.index'),
                'workspace' => 'projects',
            ]);
    }
}
