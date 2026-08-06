<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformBillingRecord;
use App\Models\PlatformCoupon;
use App\Models\PlatformUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PlatformSubscriptionService
{
    public function __construct(
        protected PlatformAuditService $audit,
        protected PlatformDashboardService $dashboard,
    ) {}

    public function overview(): array
    {
        $byPlan = Organization::query()
            ->selectRaw('plan, count(*) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->all();

        return [
            'by_plan' => $byPlan,
            'active' => Organization::query()->where('status', 'active')->count(),
            'trials' => $this->trialQuery()->count(),
            'renewals_due' => $this->renewalsQuery()->count(),
            'coupons_active' => PlatformCoupon::query()->where('is_active', true)->count(),
            'invoices_open' => PlatformBillingRecord::query()->where('type', 'invoice')->whereIn('status', ['open', 'pending'])->count(),
        ];
    }

    public function activeSubscriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Organization::query()
            ->withCount('users')
            ->where('status', 'active')
            ->orderBy('name');

        if (! empty($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function trials(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->trialQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function renewals(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->renewalsQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function assignPlan(Organization $organization, string $plan, PlatformUser $actor, array $subscription = []): Organization
    {
        $previous = $organization->plan;
        $settings = $organization->settings ?? [];
        $settings['subscription'] = array_merge($settings['subscription'] ?? [], $subscription, [
            'status' => $subscription['status'] ?? 'active',
            'plan' => $plan,
            'updated_at' => now()->toIso8601String(),
        ]);

        $organization->update([
            'plan' => $plan,
            'settings' => $settings,
        ]);

        $this->audit->log('organization.plan_assigned', $actor, $organization, [
            'previous_plan' => $previous,
            'new_plan' => $plan,
            'subscription' => $settings['subscription'],
        ]);

        $this->dashboard->clearCache();

        return $organization->fresh();
    }

    public function changePlan(Organization $organization, string $plan, PlatformUser $actor, string $direction = 'change'): Organization
    {
        $organization = $this->assignPlan($organization, $plan, $actor, [
            'status' => 'active',
            'last_change' => $direction,
            'changed_at' => now()->toIso8601String(),
        ]);

        PlatformBillingRecord::create([
            'organization_id' => $organization->id,
            'type' => 'transaction',
            'number' => 'TXN-'.Str::upper(Str::random(8)),
            'status' => 'succeeded',
            'plan' => $plan,
            'amount' => 0,
            'currency' => $organization->currency ?? 'USD',
            'description' => ucfirst($direction)." to {$plan}",
            'occurred_at' => now(),
            'meta' => ['direction' => $direction],
        ]);

        return $organization;
    }

    public function startTrial(Organization $organization, PlatformUser $actor, int $days = 14): Organization
    {
        $ends = now()->addDays($days);

        return $this->assignPlan($organization, $organization->plan ?? 'starter', $actor, [
            'status' => 'trial',
            'trial_started_at' => now()->toIso8601String(),
            'trial_ends_at' => $ends->toIso8601String(),
        ]);
    }

    public function endTrial(Organization $organization, PlatformUser $actor, bool $convert = true): Organization
    {
        return $this->assignPlan($organization, $organization->plan ?? 'starter', $actor, [
            'status' => $convert ? 'active' : 'expired',
            'trial_ended_at' => now()->toIso8601String(),
        ]);
    }

    public function coupons(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PlatformCoupon::query()->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function createCoupon(array $data, PlatformUser $actor): PlatformCoupon
    {
        $coupon = PlatformCoupon::create([
            'code' => Str::upper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'] ?? 'percent',
            'value' => $data['value'],
            'applies_to_plan' => $data['applies_to_plan'] ?? null,
            'max_redemptions' => $data['max_redemptions'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->log('coupon.created', $actor, null, [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
        ]);

        return $coupon;
    }

    public function invoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->billingQuery('invoice', $filters)->paginate($perPage)->withQueryString();
    }

    public function transactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->billingQuery('transaction', $filters)->paginate($perPage)->withQueryString();
    }

    protected function billingQuery(string $type, array $filters = [])
    {
        $query = PlatformBillingRecord::query()
            ->with('organization:id,name')
            ->where('type', $type)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('organization', fn ($oq) => $oq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function trialQuery(array $filters = [])
    {
        $query = Organization::query()
            ->withCount('users')
            ->where(function ($q) {
                $q->where('settings->subscription->status', 'trial')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('settings->subscription->status')
                            ->where('created_at', '>=', now()->subDays(14))
                            ->where('status', 'active');
                    });
            })
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    protected function renewalsQuery(array $filters = [])
    {
        $query = Organization::query()
            ->withCount('users')
            ->whereNotNull('settings->subscription->renews_at')
            ->where('settings->subscription->renews_at', '<=', now()->addDays(30)->toDateTimeString())
            ->orderBy('settings->subscription->renews_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function planCatalog(): array
    {
        $definitions = config('platform.plan_definitions', []);
        $overrides = app(PlatformConfigurationService::class)->get('licensing', 'plan_overrides', []);

        foreach ($overrides as $slug => $override) {
            if (isset($definitions[$slug])) {
                $definitions[$slug] = array_replace_recursive($definitions[$slug], $override);
            }
        }

        return $definitions;
    }
}
