<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\PlatformCoupon;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class PlatformSearchService
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function scopes(PlatformUser $user): array
    {
        $scopes = [['key' => 'all', 'label' => __('All')]];

        if ($user->hasPermission('platform.organizations.view')) {
            $scopes[] = ['key' => 'organizations', 'label' => __('Organizations')];
        }

        if ($user->hasPermission('platform.global_users.view')) {
            $scopes[] = ['key' => 'users', 'label' => __('Users')];
        }

        if ($user->hasPermission('platform.support.view')) {
            $scopes[] = ['key' => 'tickets', 'label' => __('Support Tickets')];
        }

        if ($user->hasPermission('platform.audit.view')) {
            $scopes[] = ['key' => 'audit', 'label' => __('Audit')];
        }

        if ($user->hasPermission('platform.subscriptions.view')) {
            $scopes[] = ['key' => 'subscriptions', 'label' => __('Subscriptions')];
            $scopes[] = ['key' => 'plans', 'label' => __('Plans')];
            $scopes[] = ['key' => 'coupons', 'label' => __('Coupons')];
        }

        if ($user->hasPermission('platform.providers.view')) {
            $scopes[] = ['key' => 'providers', 'label' => __('Providers')];
        }

        return $scopes;
    }

    /**
     * @return array<int, array{type: string, label: string, title: string, subtitle: string|null, url: string}>
     */
    public function search(PlatformUser $user, string $query, string $scope = 'all', int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $results = collect();

        if ($this->includesScope($scope, 'organizations') && $user->hasPermission('platform.organizations.view')) {
            $results = $results->merge($this->searchOrganizations($query, $limit));
        }

        if ($this->includesScope($scope, 'users') && $user->hasPermission('platform.global_users.view')) {
            $results = $results->merge($this->searchUsers($query, $limit));
        }

        if ($this->includesScope($scope, 'tickets') && $user->hasPermission('platform.support.view')) {
            $results = $results->merge($this->searchTickets($query, $limit));
        }

        if ($this->includesScope($scope, 'audit') && $user->hasPermission('platform.audit.view')) {
            $results = $results->merge($this->searchAuditLogs($query, $limit));
        }

        if ($this->includesScope($scope, 'coupons') && $user->hasPermission('platform.subscriptions.view')) {
            $results = $results->merge($this->searchCoupons($query, $limit));
        }

        if ($this->includesScope($scope, 'subscriptions') && $user->hasPermission('platform.subscriptions.view')) {
            $results = $results->merge($this->searchSubscriptions($query, $limit));
        }

        if ($this->includesScope($scope, 'plans') && $user->hasPermission('platform.subscriptions.view')) {
            $results = $results->merge($this->searchPlans($query, $limit));
        }

        if ($this->includesScope($scope, 'providers') && $user->hasPermission('platform.providers.view')) {
            $results = $results->merge($this->searchProviders($query, $limit));
        }

        return $results->take($limit)->values()->all();
    }

    public function recent(PlatformUser $user): array
    {
        return collect($user->preferences['recent_searches'] ?? [])
            ->take(8)
            ->values()
            ->all();
    }

    protected function includesScope(string $scope, string $key): bool
    {
        return $scope === 'all' || $scope === $key;
    }

    protected function searchOrganizations(string $query, int $limit): Collection
    {
        if (! Route::has('platform.organizations.show')) {
            return collect();
        }

        return Organization::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Organization $org) => [
                'type' => 'organization',
                'label' => __('Organization'),
                'title' => $org->name,
                'subtitle' => $org->email ?? $org->slug,
                'url' => route('platform.organizations.show', $org),
            ]);
    }

    protected function searchUsers(string $query, int $limit): Collection
    {
        $tenantUsers = \App\Models\User::query()
            ->with(['organizations' => fn ($q) => $q->select('organizations.id', 'organizations.name')])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (\App\Models\User $user) => [
                'type' => 'user',
                'label' => __('Platform User'),
                'title' => $user->name,
                'subtitle' => $user->email.' · '.$user->organizations->pluck('name')->join(', '),
                'url' => Route::has('platform.global-users.index')
                    ? route('platform.global-users.index', ['search' => $user->email])
                    : '#',
            ]);

        $staff = PlatformUser::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (PlatformUser $platformUser) => [
                'type' => 'platform_staff',
                'label' => __('Platform Staff'),
                'title' => $platformUser->name,
                'subtitle' => $platformUser->email,
                'url' => Route::has('platform.users.index')
                    ? route('platform.users.index')
                    : '#',
            ]);

        return $tenantUsers->concat($staff);
    }

    protected function searchTickets(string $query, int $limit): Collection
    {
        return PlatformSupportTicket::query()
            ->with('organization:id,name')
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('requester_email', 'like', "%{$query}%")
                    ->orWhere('requester_name', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (PlatformSupportTicket $ticket) {
                $url = Route::has('platform.support.tickets.show')
                    ? route('platform.support.tickets.show', $ticket)
                    : (Route::has('platform.support.tickets') ? route('platform.support.tickets') : '#');

                return [
                    'type' => 'ticket',
                    'label' => __('Support Ticket'),
                    'title' => $ticket->subject,
                    'subtitle' => $ticket->organization?->name ?? $ticket->requester_email,
                    'url' => $url,
                ];
            });
    }

    protected function searchAuditLogs(string $query, int $limit): Collection
    {
        return PlatformAuditLog::query()
            ->with(['platformUser:id,name', 'organization:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('event', 'like', "%{$query}%")
                    ->orWhere('subject', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PlatformAuditLog $log) => [
                'type' => 'audit',
                'label' => __('Audit'),
                'title' => $log->event,
                'subtitle' => trim(($log->platformUser?->name ?? '').' · '.($log->organization?->name ?? '')),
                'url' => Route::has('platform.audit.index') ? route('platform.audit.index', ['event' => $log->event]) : '#',
            ]);
    }

    protected function searchCoupons(string $query, int $limit): Collection
    {
        return PlatformCoupon::query()
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit($limit)
            ->get()
            ->map(fn (PlatformCoupon $coupon) => [
                'type' => 'coupon',
                'label' => __('Coupon'),
                'title' => $coupon->code,
                'subtitle' => $coupon->name,
                'url' => Route::has('platform.coupons.index') ? route('platform.coupons.index', ['search' => $coupon->code]) : '#',
            ]);
    }

    protected function searchSubscriptions(string $query, int $limit): Collection
    {
        return Organization::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('plan', 'like', "%{$query}%");
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Organization $org) => [
                'type' => 'subscription',
                'label' => __('Subscription'),
                'title' => $org->name,
                'subtitle' => $org->planLabel(),
                'url' => Route::has('platform.subscriptions.active')
                    ? route('platform.subscriptions.active', ['search' => $org->name])
                    : route('platform.organizations.show', $org),
            ]);
    }

    protected function searchPlans(string $query, int $limit): Collection
    {
        $needle = mb_strtolower($query);

        return collect(config('platform.plan_definitions', []))
            ->filter(function (array $plan, string $slug) use ($needle) {
                return str_contains(mb_strtolower($slug), $needle)
                    || str_contains(mb_strtolower($plan['name'] ?? ''), $needle);
            })
            ->take($limit)
            ->map(fn (array $plan, string $slug) => [
                'type' => 'plan',
                'label' => __('Plan'),
                'title' => $plan['name'] ?? $slug,
                'subtitle' => $plan['description'] ?? $slug,
                'url' => Route::has('platform.plans.index') ? route('platform.plans.index') : '#',
            ])
            ->values();
    }

    protected function searchProviders(string $query, int $limit): Collection
    {
        $needle = mb_strtolower($query);

        return collect(config('platform.providers', []))
            ->filter(function (array $provider, string $key) use ($needle) {
                return str_contains(mb_strtolower($key), $needle)
                    || str_contains(mb_strtolower($provider['label'] ?? ''), $needle);
            })
            ->take($limit)
            ->map(fn (array $provider, string $key) => [
                'type' => 'provider',
                'label' => __('Provider'),
                'title' => $provider['label'] ?? $key,
                'subtitle' => $provider['category'] ?? null,
                'url' => Route::has('platform.providers.show')
                    ? route('platform.providers.show', $key)
                    : route('platform.providers.index'),
            ])
            ->values();
    }
}
