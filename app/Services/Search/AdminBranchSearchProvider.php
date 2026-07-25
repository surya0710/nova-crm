<?php

namespace App\Services\Search;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AdminBranchSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'branches';
    }

    public function label(): string
    {
        return __('Branches');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['hrms.view', 'organization.branches.view'])) {
            return collect();
        }

        if (! Schema::hasTable('hrms_branches')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $indexUrl = Route::has('hrms.branches.index')
            ? route('hrms.branches.index')
            : (Route::has('organization.settings.branches.index')
                ? route('organization.settings.branches.index')
                : null);

        if (! $indexUrl) {
            return collect();
        }

        return Branch::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('city', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (Branch $branch) => [
                'type' => __('Branch'),
                'label' => $this->label(),
                'title' => $branch->name,
                'subtitle' => $branch->code ?: $branch->city,
                'url' => Route::has('hrms.branches.show')
                    ? route('hrms.branches.show', $branch)
                    : $indexUrl,
                'workspace' => 'administration',
            ]);
    }
}
