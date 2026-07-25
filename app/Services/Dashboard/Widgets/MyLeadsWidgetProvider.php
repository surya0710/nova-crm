<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;

class MyLeadsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'my_leads';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'leads.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $leads = Lead::query()
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['won', 'lost'])
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'created_at']);

        return [
            'total' => Lead::query()->where('assigned_to', $user->id)->whereNotIn('status', ['won', 'lost'])->count(),
            'leads' => $leads,
        ];
    }
}
