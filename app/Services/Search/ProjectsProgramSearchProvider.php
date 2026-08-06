<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ProjectsProgramSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'programs';
    }

    public function label(): string
    {
        return __('Programs');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('projects.programs.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Program::query()
            ->with('portfolio:id,name,code')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Program $program) => [
                'type' => __('Program'),
                'label' => $this->label(),
                'title' => $program->name,
                'subtitle' => collect([
                    $program->code,
                    $program->portfolio?->name,
                    $program->status,
                ])->filter()->implode(' · ') ?: null,
                'url' => Route::has('programs.show')
                    ? route('programs.show', $program)
                    : route('programs.index'),
                'workspace' => 'projects',
            ]);
    }
}
