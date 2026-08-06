<?php

namespace App\Services\Platform;

use App\Models\PlatformUser;
use Illuminate\Support\Facades\Route;

class PlatformCommandPaletteService
{
    /**
     * @return array<int, array{id: string, label: string, group: string, href?: string, action?: string, keywords?: array<int, string>}>
     */
    public function commands(PlatformUser $user, string $query = ''): array
    {
        $commands = collect();

        foreach (config('platform.navigation', []) as $item) {
            if (! $user->hasPermission($item['permission'] ?? '')) {
                continue;
            }

            if (! Route::has($item['route'])) {
                continue;
            }

            $commands->push([
                'id' => 'nav.'.$item['route'],
                'label' => __($item['label']),
                'group' => __('Navigation'),
                'href' => route($item['route']),
                'keywords' => [$item['label'], $item['route']],
            ]);
        }

        if ($user->hasPermission('platform.organizations.manage') && Route::has('platform.organizations.create')) {
            $commands->push([
                'id' => 'org.create',
                'label' => __('Create Organization'),
                'group' => __('Platform'),
                'href' => route('platform.organizations.create'),
                'keywords' => ['new', 'organization', 'tenant', 'create'],
            ]);
        }

        $platformShortcuts = [
            ['id' => 'org.open', 'label' => __('Open Organizations'), 'permission' => 'platform.organizations.view', 'route' => 'platform.organizations.index', 'keywords' => ['organizations', 'tenants']],
            ['id' => 'subs.open', 'label' => __('Open Subscriptions'), 'permission' => 'platform.subscriptions.view', 'route' => 'platform.subscriptions.index', 'keywords' => ['billing', 'plans', 'subscriptions']],
            ['id' => 'mon.open', 'label' => __('Open Monitoring'), 'permission' => 'platform.monitoring.view', 'route' => 'platform.monitoring.index', 'keywords' => ['queue', 'health', 'jobs']],
            ['id' => 'prov.open', 'label' => __('Open Providers'), 'permission' => 'platform.providers.view', 'route' => 'platform.providers.index', 'keywords' => ['smtp', 'google', 'ai']],
            ['id' => 'support.open', 'label' => __('Open Support'), 'permission' => 'platform.support.view', 'route' => 'platform.support.index', 'keywords' => ['tickets', 'help']],
            ['id' => 'org.search', 'label' => __('Search Organizations'), 'permission' => 'platform.organizations.view', 'route' => 'platform.organizations.index', 'keywords' => ['search', 'find', 'organization'], 'action' => 'search:open'],
            ['id' => 'users.search', 'label' => __('Search Users'), 'permission' => 'platform.global_users.view', 'route' => 'platform.global-users.index', 'keywords' => ['search', 'users'], 'action' => 'search:open'],
        ];

        foreach ($platformShortcuts as $shortcut) {
            if (! $user->hasPermission($shortcut['permission']) || ! Route::has($shortcut['route'])) {
                continue;
            }

            $commands->push([
                'id' => $shortcut['id'],
                'label' => $shortcut['label'],
                'group' => __('Platform'),
                'href' => route($shortcut['route']),
                'action' => $shortcut['action'] ?? null,
                'keywords' => $shortcut['keywords'],
            ]);
        }

        $commands->push([
            'id' => 'shell.search',
            'label' => __('Open Search'),
            'group' => __('Shell'),
            'action' => 'search:open',
            'keywords' => ['search', 'find'],
        ]);

        foreach (['light', 'dark'] as $theme) {
            $commands->push([
                'id' => 'theme.'.$theme,
                'label' => __('Switch to :theme theme', ['theme' => ucfirst($theme)]),
                'group' => __('Appearance'),
                'action' => 'theme:'.$theme,
                'keywords' => ['theme', 'appearance', $theme],
            ]);
        }

        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return $commands->values()->all();
        }

        return $commands
            ->filter(function (array $command) use ($needle) {
                $haystack = mb_strtolower(implode(' ', [
                    $command['label'] ?? '',
                    $command['group'] ?? '',
                    implode(' ', $command['keywords'] ?? []),
                ]));

                return str_contains($haystack, $needle);
            })
            ->values()
            ->all();
    }

    public function recent(PlatformUser $user): array
    {
        return collect($user->preferences['recent_commands'] ?? [])
            ->take(8)
            ->values()
            ->all();
    }
}
