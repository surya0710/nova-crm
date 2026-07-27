<?php

namespace App\View\Composers;

use App\Services\Navigation\NavigationContextManager;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShellComposer
{
    public function __construct(
        protected NavigationContextManager $navigation,
        protected TenantContext $tenant,
    ) {}

    public function compose(View $view): void
    {
        $user = Auth::user();
        $organization = $this->tenant->get();

        if (! $user || ! $organization) {
            $view->with('shellNav', [
                'workspaces' => collect(),
                'currentWorkspace' => 'home',
                'currentWorkspaceMeta' => null,
                'menu' => collect(),
                'favorites' => collect(),
                'favoriteWorkspaces' => collect(),
                'recentWorkspaces' => collect(),
                'recents' => collect(),
                'pinned' => collect(),
                'quickActions' => [],
                'searchDefaultScope' => 'all',
                'preferences' => null,
                'sidebarCollapsed' => false,
                'theme' => 'light',
                'density' => 'comfortable',
                'branding' => [],
            ]);

            return;
        }

        $view->with('shellNav', $this->navigation->forRequest($user, $organization));
    }
}
