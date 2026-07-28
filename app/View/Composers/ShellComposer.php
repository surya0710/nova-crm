<?php

namespace App\View\Composers;

use App\Services\Navigation\NavigationService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShellComposer
{
    public function __construct(
        protected NavigationService $navigation,
        protected TenantContext $tenant,
    ) {}

    public function compose(View $view): void
    {
        $user = Auth::user();
        $organization = $this->tenant->get();

        if (! $user || ! $organization) {
            $view->with('shellNav', $this->navigation->emptyShell());

            return;
        }

        $view->with('shellNav', $this->navigation->forShell($user, $organization));
    }
}
