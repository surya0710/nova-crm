<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ProjectsPortfolioSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'portfolios';
    }

    public function label(): string
    {
        return __('Portfolios');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('projects.portfolios.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Portfolio::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'type' => __('Portfolio'),
                'label' => $this->label(),
                'title' => $portfolio->name,
                'subtitle' => collect([$portfolio->code, $portfolio->status])->filter()->implode(' · ') ?: null,
                'url' => Route::has('portfolios.show')
                    ? route('portfolios.show', $portfolio)
                    : route('portfolios.index'),
                'workspace' => 'projects',
            ]);
    }
}
