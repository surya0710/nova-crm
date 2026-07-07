<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformDashboardService
{
    public function metrics(): array
    {
        $ttl = config('platform.dashboard_cache_ttl', 300);

        return Cache::remember('platform.dashboard.metrics', $ttl, function () {
            $today = today();
            $monthStart = now()->startOfMonth();

            $orgCounts = Organization::query()
                ->selectRaw("count(*) as total")
                ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as active")
                ->selectRaw("sum(case when created_at >= ? then 1 else 0 end) as new_this_month", [$monthStart])
                ->first();

            $userCounts = DB::table('organization_user')
                ->join('organizations', 'organizations.id', '=', 'organization_user.organization_id')
                ->where('organizations.status', '!=', 'archived')
                ->selectRaw('count(distinct organization_user.user_id) as total')
                ->first();

            $activeUsersToday = AuditLog::withoutGlobalScopes()
                ->whereDate('created_at', $today)
                ->distinct('user_id')
                ->count('user_id');

            return [
                'organizations' => [
                    'total' => (int) ($orgCounts->total ?? 0),
                    'active' => (int) ($orgCounts->active ?? 0),
                    'new_this_month' => (int) ($orgCounts->new_this_month ?? 0),
                ],
                'users' => [
                    'total' => (int) ($userCounts->total ?? 0),
                    'active_today' => $activeUsersToday,
                ],
                'activity_today' => [
                    'leads' => Lead::withoutGlobalScopes()->whereDate('created_at', $today)->count(),
                    'customers' => Customer::withoutGlobalScopes()->whereDate('created_at', $today)->count(),
                    'invoices' => Invoice::withoutGlobalScopes()->whereDate('created_at', $today)->count(),
                    'payments' => Payment::withoutGlobalScopes()->whereDate('created_at', $today)->count(),
                ],
                'placeholders' => [
                    'api_requests' => null,
                    'storage_usage' => Organization::query()->sum('storage_used_bytes'),
                    'queue_health' => 'healthy',
                ],
                'recent_activity' => $this->recentOrganizationActivity(),
            ];
        });
    }

    public function clearCache(): void
    {
        Cache::forget('platform.dashboard.metrics');
    }

    protected function recentOrganizationActivity(): array
    {
        return AuditLog::withoutGlobalScopes()
            ->with(['organization:id,name', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'organization' => $log->organization?->name,
                'user' => $log->user?->name,
                'event' => $log->event,
                'subject' => $log->subject,
                'created_at' => $log->created_at,
            ])
            ->all();
    }
}
