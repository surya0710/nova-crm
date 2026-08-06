<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;

class PendingExpensesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'pending_expenses';
    }

    public function subscriptionModule(): ?string
    {
        return 'finance';
    }

    public function permissionSlug(): ?string
    {
        return 'finance.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $pending = Invoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->count();

        $outstanding = Invoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total');

        return [
            'pending_count' => $pending,
            'outstanding_amount' => $outstanding,
        ];
    }
}
