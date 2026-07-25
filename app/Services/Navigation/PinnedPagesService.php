<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;

class PinnedPagesService
{
    public function __construct(protected ThemeService $theme) {}

    /**
     * Role/org defaults merged with user pins.
     *
     * @return Collection<int, array{label: string, href: string, icon?: string|null, source: string}>
     */
    public function list(User $user, Organization $organization): Collection
    {
        $prefs = $this->theme->preferencesFor($user, $organization);

        $defaults = collect($this->roleDefaults($user, $organization));
        $userPins = collect($prefs->pinned_pages ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['href']))
            ->map(fn ($item) => array_merge($item, ['source' => 'user']));

        return $defaults->merge($userPins)->unique('href')->values();
    }

    /**
     * @return array<int, array{label: string, href: string, icon?: string|null, source: string}>
     */
    protected function roleDefaults(User $user, Organization $organization): array
    {
        $pins = [];

        if ($user->hasPermission('recruitment.view', $organization) && \Illuminate\Support\Facades\Route::has('hrms.recruitment.dashboard')) {
            $pins[] = [
                'label' => __('Recruitment'),
                'href' => route('hrms.recruitment.dashboard'),
                'icon' => 'hr',
                'source' => 'role',
            ];
        }

        return $pins;
    }
}
