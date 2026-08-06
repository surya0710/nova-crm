<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;

class FavoriteWorkspacesService
{
    public function __construct(protected ThemeService $theme) {}

    /**
     * @return Collection<int, string>
     */
    public function list(User $user, Organization $organization): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];

        return collect($meta['favorite_workspaces'] ?? [])
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->values();
    }

    public function toggle(User $user, Organization $organization, string $workspaceId): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];
        $favorites = collect($meta['favorite_workspaces'] ?? []);

        if ($favorites->contains($workspaceId)) {
            $favorites = $favorites->reject(fn ($id) => $id === $workspaceId)->values();
        } else {
            $favorites = $favorites->push($workspaceId)->take(8)->values();
        }

        $meta['favorite_workspaces'] = $favorites->all();
        $this->theme->updatePreferences($user, $organization, ['meta' => $meta]);

        return $favorites;
    }

    /**
     * @return Collection<int, string>
     */
    public function recent(User $user, Organization $organization): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];

        return collect($meta['recent_workspaces'] ?? [])
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->take(5)
            ->values();
    }

    public function rememberRecent(User $user, Organization $organization, string $workspaceId): void
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];

        $recent = collect($meta['recent_workspaces'] ?? [])
            ->reject(fn ($id) => $id === $workspaceId)
            ->prepend($workspaceId)
            ->take(5)
            ->values()
            ->all();

        $meta['recent_workspaces'] = $recent;
        $this->theme->updatePreferences($user, $organization, ['meta' => $meta]);
    }
}
