<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;

class FavoritePagesService
{
    public function __construct(protected ThemeService $theme) {}

    /**
     * @return Collection<int, array{label: string, href: string, icon?: string|null}>
     */
    public function list(User $user, Organization $organization): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $max = (int) config('navigation.favorites.max_display', 5);

        return collect($prefs->favorites ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['href']) && ! empty($item['label']))
            ->take($max)
            ->values();
    }

    /**
     * @param  array{label: string, href: string, icon?: string|null}  $page
     */
    public function toggle(User $user, Organization $organization, array $page): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $favorites = collect($prefs->favorites ?? []);

        $exists = $favorites->first(fn ($f) => ($f['href'] ?? null) === $page['href']);

        if ($exists) {
            $favorites = $favorites->reject(fn ($f) => ($f['href'] ?? null) === $page['href'])->values();
        } else {
            $favorites = $favorites->prepend([
                'label' => $page['label'],
                'href' => $page['href'],
                'icon' => $page['icon'] ?? null,
            ])->unique('href')->values();
        }

        $this->theme->updatePreferences($user, $organization, [
            'favorites' => $favorites->all(),
        ]);

        return $favorites;
    }
}
