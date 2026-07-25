<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use App\Services\Navigation\MenuBuilder;
use App\Services\Navigation\WorkspaceResolver;
use Illuminate\Support\Collection;

class NavigationCommandProvider implements CommandProviderInterface
{
    public function __construct(
        protected WorkspaceResolver $workspaces,
        protected MenuBuilder $menus,
    ) {}

    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $commands = collect();

        foreach ($this->workspaces->availableWorkspaces($user, $organization) as $workspace) {
            $commands->push([
                'id' => 'workspace.'.$workspace['id'],
                'label' => __('Go to :name', ['name' => $workspace['label']]),
                'group' => __('Workspaces'),
                'href' => $workspace['href'],
                'keywords' => [$workspace['id'], 'workspace'],
            ]);

            foreach ($this->menus->buildForWorkspace($workspace['id'], $user, $organization) as $item) {
                $this->collectItem($commands, $item, $workspace['label']);
            }
        }

        $commands->push([
            'id' => 'nav.profile',
            'label' => __('Profile'),
            'group' => __('Account'),
            'href' => route('profile.edit'),
            'keywords' => ['account', 'settings'],
        ]);

        if (\Illuminate\Support\Facades\Route::has('knowledge.index')) {
            $commands->push([
                'id' => 'nav.knowledge',
                'label' => __('Knowledge Center'),
                'group' => __('Help'),
                'href' => route('knowledge.index'),
                'keywords' => ['help', 'docs'],
            ]);
        }

        return $commands;
    }

    /**
     * @param  Collection<int, array>  $commands
     * @param  array<string, mixed>  $item
     */
    protected function collectItem(Collection $commands, array $item, string $group): void
    {
        if (! empty($item['href'])) {
            $commands->push([
                'id' => 'nav.'.md5($item['href']),
                'label' => $item['label'],
                'group' => $group,
                'href' => $item['href'],
                'keywords' => [$group],
            ]);
        }

        foreach ($item['children'] ?? [] as $child) {
            $this->collectItem($commands, $child, $group);
        }
    }
}
