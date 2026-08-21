<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CrmEmailMetricsService;
use App\Services\TenantContext;

class CrmEmailMetricsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'crm_email_metrics';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'crm_email.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $days = (int) ($configuration['days'] ?? 30);

        return app(CrmEmailMetricsService::class)->summary(
            $organization,
            now()->subDays(max(1, $days)),
            now(),
        );
    }
}
