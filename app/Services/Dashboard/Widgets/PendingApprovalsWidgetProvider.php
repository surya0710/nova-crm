<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkflowExecution;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Schema;

class PendingApprovalsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'pending_approvals';
    }

    public function subscriptionModule(): ?string
    {
        return 'workflow';
    }

    public function permissionSlug(): ?string
    {
        return 'workflows.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        if (! Schema::hasTable('workflow_executions')) {
            return ['pending_count' => 0, 'executions' => []];
        }

        app(TenantContext::class)->set($organization);

        $pending = WorkflowExecution::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get(['id', 'status', 'created_at']);

        return [
            'pending_count' => WorkflowExecution::query()->where('status', 'pending')->count(),
            'executions' => $pending,
        ];
    }
}
