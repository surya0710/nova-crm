<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class MarketingCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $perms = ['marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage'];
        if (! $user->hasAnyPermission($perms)) {
            return collect();
        }

        $commands = collect();
        $group = __('Marketing');

        if (Route::has('marketing.home')) {
            $commands->push([
                'id' => 'marketing.home',
                'label' => __('Open Marketing'),
                'group' => $group,
                'href' => route('marketing.home'),
                'keywords' => ['marketing', 'home', 'campaigns'],
            ]);
        }

        if ($user->hasAnyPermission(['marketing.manage', 'integrations.manage']) && Route::has('marketing.campaigns.create')) {
            $commands->push([
                'id' => 'marketing.create-campaign',
                'label' => __('Create Campaign'),
                'group' => $group,
                'href' => route('marketing.campaigns.create'),
                'keywords' => ['campaign', 'create', 'marketing'],
            ]);
        }

        if (Route::has('marketing.campaigns.index')) {
            $commands->push([
                'id' => 'marketing.search-campaigns',
                'label' => __('Search Campaigns'),
                'group' => $group,
                'href' => route('marketing.campaigns.index'),
                'keywords' => ['campaigns', 'search', 'marketing'],
            ]);
        }

        if (Route::has('marketing.attribution.index')) {
            $commands->push([
                'id' => 'marketing.attribution',
                'label' => __('Open Attribution'),
                'group' => $group,
                'href' => route('marketing.attribution.index'),
                'keywords' => ['attribution', 'touches', 'sources'],
            ]);
        }

        if (Route::has('marketing.providers.index')) {
            $commands->push([
                'id' => 'marketing.providers',
                'label' => __('Open Marketing Providers'),
                'group' => $group,
                'href' => route('marketing.providers.index'),
                'keywords' => ['providers', 'google', 'meta', 'ads'],
            ]);
        }

        return $commands;
    }
}
