<?php

namespace App\Services\Search;

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AdminDepartmentSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'departments';
    }

    public function label(): string
    {
        return __('Departments');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['hrms.view', 'organization.branches.view'])) {
            return collect();
        }

        if (! Schema::hasTable('hrms_departments')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $indexUrl = Route::has('hrms.departments.index')
            ? route('hrms.departments.index')
            : (Route::has('organization.settings.departments.index')
                ? route('organization.settings.departments.index')
                : null);

        if (! $indexUrl) {
            return collect();
        }

        return Department::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (Department $department) => [
                'type' => __('Department'),
                'label' => $this->label(),
                'title' => $department->name,
                'subtitle' => $department->code,
                'url' => Route::has('hrms.departments.show')
                    ? route('hrms.departments.show', $department)
                    : $indexUrl,
                'workspace' => 'administration',
            ]);
    }
}
