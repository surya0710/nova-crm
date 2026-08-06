<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;

class RecentPagesService
{
    public function __construct(protected ThemeService $theme) {}

    /**
     * @return Collection<int, array{label: string, href: string, type?: string|null}>
     */
    public function list(User $user, Organization $organization): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);
        $max = (int) config('navigation.recents.max', 10);

        return collect($prefs->recent_pages ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['href']) && ! empty($item['label']))
            ->take($max)
            ->values();
    }

    /**
     * @param  array{label: string, href: string, type?: string|null}  $page
     */
    public function record(User $user, Organization $organization, array $page): void
    {
        if (empty($page['href']) || empty($page['label'])) {
            return;
        }

        $prefs = $this->theme->preferencesFor($user, $organization);
        $max = (int) config('navigation.recents.max', 10);

        $recents = collect($prefs->recent_pages ?? [])
            ->reject(fn ($item) => ($item['href'] ?? null) === $page['href'])
            ->prepend([
                'label' => $page['label'],
                'href' => $page['href'],
                'type' => $page['type'] ?? null,
                'visited_at' => now()->toIso8601String(),
            ])
            ->take($max)
            ->values();

        $this->theme->updatePreferences($user, $organization, [
            'recent_pages' => $recents->all(),
        ]);
    }

    public function clear(User $user, Organization $organization): void
    {
        $this->theme->updatePreferences($user, $organization, [
            'recent_pages' => [],
        ]);
    }
}
