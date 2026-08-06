<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ThemeCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! config('features.theme_switcher')) {
            return collect();
        }

        return collect([
            [
                'id' => 'theme.light',
                'label' => __('Switch to light theme'),
                'group' => __('Preferences'),
                'action' => 'theme:light',
                'keywords' => ['theme', 'light', 'appearance'],
            ],
            [
                'id' => 'theme.dark',
                'label' => __('Switch to dark theme'),
                'group' => __('Preferences'),
                'action' => 'theme:dark',
                'keywords' => ['theme', 'dark', 'appearance'],
            ],
            [
                'id' => 'theme.system',
                'label' => __('Use system theme'),
                'group' => __('Preferences'),
                'action' => 'theme:system',
                'keywords' => ['theme', 'system', 'appearance'],
            ],
            [
                'id' => 'nav.search',
                'label' => __('Open search'),
                'group' => __('Navigation'),
                'action' => 'search:open',
                'keywords' => ['search', 'find'],
            ],
        ])->when(Route::has('notifications.index'), function (Collection $c) {
            return $c->push([
                'id' => 'nav.notifications',
                'label' => __('Notifications'),
                'group' => __('Navigation'),
                'href' => route('notifications.index'),
                'keywords' => ['alerts', 'inbox'],
            ]);
        });
    }
}
