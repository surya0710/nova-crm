<?php

namespace App\Services\Administration;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowExecution;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AdministrationWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected TenantContext $tenant,
        protected ModuleSubscriptionService $modules,
        protected MarketingProviderService $providers,
        protected OrganizationSecurityService $security,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user): array
    {
        return $this->rememberHome('administration', $user, fn () => $this->buildUncached($user));
    }

    /** @return array<string, mixed> */
    protected function buildUncached(User $user): array
    {
        $organization = $this->tenant->get();

        return [
            'organization' => $organization,
            'summary' => $this->organizationSummary($organization),
            'kpis' => $this->kpis($user, $organization),
            'attention' => $this->attention($user, $organization),
            'modules' => $this->modulesSummary($organization),
            'license' => $this->licenseSummary($organization),
            'integrations' => $this->integrationsSummary($user, $organization),
            'securityStatus' => $this->security->overview($organization),
            'pendingInvitations' => $this->pendingInvitations($user, $organization),
            'recentActivity' => $this->recentActivity($user),
            'quickActions' => $this->quickActions($user),
            'structure' => $this->structureSummary($user, $organization),
            'storage' => $this->storageSummary($organization),
            'apiUsage' => $this->apiUsage($user, $organization),
        ];
    }

    protected function organizationSummary($organization): array
    {
        return [
            'name' => $organization?->name ?? __('Organization'),
            'plan' => ucfirst((string) ($organization?->plan ?? 'starter')),
            'status' => $organization?->status?->value ?? ($organization?->is_active ? 'active' : 'inactive'),
            'timezone' => $organization?->timezone,
            'currency' => $organization?->currency,
        ];
    }

    protected function kpis(User $user, $organization): array
    {
        $kpis = [];

        if ($user->hasPermission('users.view') && $organization) {
            $kpis[] = [
                'label' => __('Active users'),
                'value' => $organization->users()->count(),
                'hint' => __('Organization members'),
            ];
            $kpis[] = [
                'label' => __('Pending invitations'),
                'value' => $this->pendingInvitationCount($organization),
                'hint' => __('Awaiting acceptance'),
            ];
        }

        if ($user->hasAnyPermission(['hrms.view', 'organization.branches.view']) && Schema::hasTable('hrms_departments')) {
            $kpis[] = ['label' => __('Departments'), 'value' => Department::query()->count()];
        }

        if ($user->hasAnyPermission(['hrms.view', 'organization.branches.view']) && Schema::hasTable('hrms_branches')) {
            $kpis[] = ['label' => __('Branches'), 'value' => Branch::query()->count()];
        }

        if ($user->hasPermission('rbac.view') && $organization) {
            $kpis[] = [
                'label' => __('Roles'),
                'value' => Role::query()->where('organization_id', $organization->id)->where('is_active', true)->count(),
            ];
        }

        $modules = $this->modules->availableModules($organization);
        $kpis[] = [
            'label' => __('Enabled modules'),
            'value' => count($modules),
            'hint' => __('On current plan'),
        ];

        return array_slice($kpis, 0, 6);
    }

    protected function attention(User $user, $organization): Collection
    {
        $items = collect();

        if ($user->hasPermission('users.view') && $organization) {
            $pending = $this->pendingInvitationCount($organization);
            if ($pending > 0 && Route::has('team.index')) {
                $items->push([
                    'title' => __(':count pending invitations', ['count' => $pending]),
                    'subtitle' => __('Members awaiting invitation acceptance'),
                    'href' => route('team.index'),
                    'badge' => __('Invite'),
                ]);
            }
        }

        if ($user->hasPermission('workflows.view') && Schema::hasTable('workflow_executions')) {
            $failed = WorkflowExecution::query()
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
            if ($failed > 0 && Route::has('workflows.index')) {
                $items->push([
                    'title' => __(':count failed workflows', ['count' => $failed]),
                    'subtitle' => __('Last 7 days'),
                    'href' => route('workflows.index'),
                    'badge' => __('Alert'),
                ]);
            }
        }

        $security = $this->security->overview($organization);
        if (! ($security['mfa_required'] ?? false) && $user->hasPermission('settings.manage') && Route::has('administration.security.index')) {
            $items->push([
                'title' => __('MFA not required'),
                'subtitle' => __('Consider requiring multi-factor authentication'),
                'href' => route('administration.security.index'),
                'badge' => __('Security'),
            ]);
        }

        return $items->take(8);
    }

    protected function modulesSummary($organization): array
    {
        $enabled = $this->modules->availableModules($organization);

        return [
            'enabled' => $enabled,
            'plan' => (string) ($organization?->plan ?? 'starter'),
            'count' => count($enabled),
            'href' => Route::has('administration.modules.index') ? route('administration.modules.index') : null,
        ];
    }

    protected function licenseSummary($organization): array
    {
        $settings = is_array($organization?->settings) ? $organization->settings : [];
        $subscription = $settings['subscription'] ?? [];

        return [
            'plan' => ucfirst((string) ($organization?->plan ?? 'starter')),
            'status' => (string) ($subscription['status'] ?? ($organization?->is_active ? 'active' : 'inactive')),
            'seats' => isset($subscription['seats']) ? (int) $subscription['seats'] : null,
            'href' => Route::has('organization.settings.subscription') ? route('organization.settings.subscription') : null,
        ];
    }

    protected function integrationsSummary(User $user, $organization): ?array
    {
        if (! $user->hasAnyPermission(['integrations.view', 'integrations.manage']) || ! $organization) {
            return null;
        }

        $cards = collect($this->providers->integrationCardsForOrganization($organization));
        $connected = $cards->filter(fn ($card) => ($card['connected'] ?? false) || ($card['status'] ?? null) === 'connected')->count();
        $degraded = $cards->filter(fn ($card) => in_array($card['health'] ?? $card['status'] ?? null, ['unhealthy', 'degraded', 'error'], true))->count();

        return [
            'connected' => $connected,
            'degraded' => $degraded,
            'cards' => $cards->take(6),
            'href' => Route::has('integrations.index') ? route('integrations.index') : null,
        ];
    }

    protected function pendingInvitations(User $user, $organization): Collection
    {
        if (! $user->hasPermission('users.view') || ! $organization) {
            return collect();
        }

        return $organization->users()
            ->where('account_status', \App\Enums\UserAccountStatus::PendingInvitation->value)
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    protected function pendingInvitationCount($organization): int
    {
        if (! $organization) {
            return 0;
        }

        return $organization->users()
            ->where('account_status', \App\Enums\UserAccountStatus::PendingInvitation->value)
            ->count();
    }

    protected function recentActivity(User $user): Collection
    {
        if (! $user->hasPermission('audit.view') || ! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'title' => $log->subject ?: $log->event_label,
                'subtitle' => trim(($log->user?->name ?? __('System')).' · '.$log->event_label),
                'href' => Route::has('audit-logs.index') ? route('audit-logs.index') : null,
                'when' => $log->created_at?->diffForHumans(),
            ]);
    }

    protected function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('users.create') && Route::has('team.index')) {
            $actions[] = ['label' => __('Invite User'), 'href' => route('team.index').'#invite', 'variant' => 'primary'];
        }
        if ($user->hasPermission('rbac.manage') && Route::has('rbac.roles.create')) {
            $actions[] = ['label' => __('Create Role'), 'href' => route('rbac.roles.create')];
        } elseif ($user->hasPermission('rbac.view') && Route::has('rbac.roles.index')) {
            $actions[] = ['label' => __('Open Roles'), 'href' => route('rbac.roles.index')];
        }
        if ($user->hasPermission('settings.manage') && Route::has('organization.settings.hub')) {
            $actions[] = ['label' => __('Configuration Hub'), 'href' => route('organization.settings.hub')];
        }
        if ($user->hasPermission('api.tokens') && Route::has('api-tokens.index')) {
            $actions[] = ['label' => __('API Tokens'), 'href' => route('api-tokens.index')];
        }
        if ($user->hasAnyPermission(['organization.branches.view', 'hrms.view']) && Route::has('organization.settings.departments.index')) {
            $actions[] = ['label' => __('Create Department'), 'href' => route('organization.settings.departments.index')];
        }
        if ($user->hasAnyPermission(['organization.branches.view', 'hrms.view']) && Route::has('organization.settings.branches.index')) {
            $actions[] = ['label' => __('Create Branch'), 'href' => route('organization.settings.branches.index')];
        }

        return $actions;
    }

    protected function structureSummary(User $user, $organization): ?array
    {
        if (! $user->hasAnyPermission(['hrms.view', 'organization.branches.view', 'settings.manage'])) {
            return null;
        }

        return [
            'departments' => Schema::hasTable('hrms_departments') ? Department::query()->count() : 0,
            'branches' => Schema::hasTable('hrms_branches') ? Branch::query()->count() : 0,
            'designations' => Schema::hasTable('hrms_designations') ? \App\Models\Designation::query()->count() : 0,
        ];
    }

    protected function storageSummary($organization): array
    {
        $bytes = (int) ($organization?->storage_used_bytes ?? 0);

        return [
            'used_bytes' => $bytes,
            'used_label' => $this->formatBytes($bytes),
            'href' => Route::has('organization.settings.subscription') ? route('organization.settings.subscription') : null,
        ];
    }

    protected function apiUsage(User $user, $organization): ?array
    {
        if (! $user->hasPermission('api.tokens') || ! $organization) {
            return null;
        }

        $tokens = 0;
        if (Schema::hasTable('personal_access_tokens')) {
            $userIds = $organization->users()->pluck('users.id');
            $tokens = \Laravel\Sanctum\PersonalAccessToken::query()
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->count();
        }

        return [
            'tokens' => $tokens,
            'href' => Route::has('administration.developer.index')
                ? route('administration.developer.index')
                : (Route::has('api-tokens.index') ? route('api-tokens.index') : null),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1073741824) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1073741824, 2).' GB';
    }
}
