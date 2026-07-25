<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformAuditLog;
use App\Models\PlatformBillingRecord;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformDashboardService
{
    public function metrics(): array
    {
        $ttl = config('platform.dashboard_cache_ttl', 300);

        return Cache::remember('platform.dashboard.metrics', $ttl, function () {
            return $this->buildMetrics();
        });
    }

    public function buildMetrics(): array
    {
        $today = today();
        $monthStart = now()->startOfMonth();
        $mauStart = now()->subDays(30);

        $orgCounts = Organization::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as active")
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as new_this_month', [$monthStart])
            ->first();

        $trialCount = Organization::query()
            ->where(function ($q) {
                $q->where('settings->subscription->status', 'trial')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('settings->subscription->status')
                            ->where('created_at', '>=', now()->subDays(14))
                            ->where('status', 'active');
                    });
            })
            ->count();

        $expiredCount = Organization::query()
            ->where(function ($q) {
                $q->where('settings->subscription->status', 'expired')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('settings->subscription->trial_ends_at')
                            ->where('settings->subscription->trial_ends_at', '<', now()->toDateTimeString())
                            ->where('settings->subscription->status', 'trial');
                    });
            })
            ->count();

        $userCounts = DB::table('organization_user')
            ->join('organizations', 'organizations.id', '=', 'organization_user.organization_id')
            ->where('organizations.status', '!=', 'archived')
            ->selectRaw('count(distinct organization_user.user_id) as total')
            ->first();

        $activeUsersToday = AuditLog::withoutGlobalScopes()
            ->whereDate('created_at', $today)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $mau = AuditLog::withoutGlobalScopes()
            ->where('created_at', '>=', $mauStart)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $planBreakdown = Organization::query()
            ->selectRaw('plan, count(*) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->all();

        $revenueMonth = PlatformBillingRecord::query()
            ->where('type', 'transaction')
            ->where('status', 'succeeded')
            ->where('occurred_at', '>=', $monthStart)
            ->sum('amount');

        $failedJobs = Schema::hasTable('failed_jobs')
            ? (int) DB::table('failed_jobs')->count()
            : 0;

        $pendingJobs = Schema::hasTable('jobs')
            ? (int) DB::table('jobs')->count()
            : 0;

        $openTickets = PlatformSupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count();
        $activeMaintenance = PlatformAnnouncement::query()
            ->where('type', 'maintenance')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->count();

        $providerHealth = app(PlatformProviderService::class)->healthSummary();

        return [
            'organizations' => [
                'total' => (int) ($orgCounts->total ?? 0),
                'active' => (int) ($orgCounts->active ?? 0),
                'trial' => $trialCount,
                'expired' => $expiredCount,
                'new_this_month' => (int) ($orgCounts->new_this_month ?? 0),
            ],
            'users' => [
                'total' => (int) ($userCounts->total ?? 0),
                'active_today' => $activeUsersToday,
                'mau' => $mau,
            ],
            'subscriptions' => [
                'by_plan' => $planBreakdown,
                'active' => (int) ($orgCounts->active ?? 0),
                'trial' => $trialCount,
                'expired' => $expiredCount,
            ],
            'revenue' => [
                'month' => (float) $revenueMonth,
                'currency' => 'USD',
            ],
            'storage' => [
                'bytes' => (int) Organization::query()->sum('storage_used_bytes'),
            ],
            'queue' => [
                'health' => $failedJobs > 0 ? 'degraded' : 'healthy',
                'pending' => $pendingJobs,
                'failed' => $failedJobs,
            ],
            'api_requests' => [
                'today' => null,
                'label' => __('Instrumentation pending'),
            ],
            'email_delivery' => [
                'status' => config('mail.default') ? 'configured' : 'unconfigured',
                'mailer' => config('mail.default'),
            ],
            'providers' => $providerHealth,
            'alerts' => [
                'failed_jobs' => $failedJobs,
                'open_tickets' => $openTickets,
                'maintenance' => $activeMaintenance,
                'expired_orgs' => $expiredCount,
            ],
            'recent_activity' => $this->recentOrganizationActivity(),
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('platform.dashboard.metrics');
    }

    protected function recentOrganizationActivity(): array
    {
        $platform = PlatformAuditLog::query()
            ->with(['platformUser:id,name', 'organization:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (PlatformAuditLog $log) => [
                'source' => 'platform',
                'organization' => $log->organization?->name,
                'user' => $log->platformUser?->name,
                'event' => $log->event,
                'subject' => $log->subject,
                'created_at' => $log->created_at,
            ]);

        $tenant = AuditLog::withoutGlobalScopes()
            ->with(['organization:id,name', 'user:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($log) => [
                'source' => 'organization',
                'organization' => $log->organization?->name,
                'user' => $log->user?->name,
                'event' => $log->event,
                'subject' => $log->subject,
                'created_at' => $log->created_at,
            ]);

        return $platform->concat($tenant)
            ->sortByDesc('created_at')
            ->take(12)
            ->values()
            ->all();
    }
}
