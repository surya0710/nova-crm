<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminUserSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return __('Users');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('users.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $teamUrl = Route::has('team.index') ? route('team.index') : null;
        $profileUrl = Route::has('profile.edit') ? route('profile.edit') : null;

        return $organization->users()
            ->where(function ($q) use ($query) {
                $q->where('users.name', 'like', "%{$query}%")
                    ->orWhere('users.email', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function (User $member) use ($user, $teamUrl, $profileUrl) {
                $url = $teamUrl;
                if ($member->id === $user->id && $profileUrl) {
                    $url = $profileUrl;
                }

                return [
                    'type' => __('User'),
                    'label' => $this->label(),
                    'title' => $member->name,
                    'subtitle' => $member->email,
                    'url' => $url ?? '#',
                    'workspace' => 'administration',
                ];
            });
    }
}
