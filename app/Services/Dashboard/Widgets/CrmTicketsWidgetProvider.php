<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CustomerTicketService;
use App\Services\TenantContext;

class CrmTicketsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'crm_tickets';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'customers.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(CustomerTicketService::class)->metrics();
    }
}
