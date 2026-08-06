<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request, PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.coupons.index', [
            'coupons' => $service->coupons($request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        return view('platform.coupons.create', [
            'plans' => config('platform.plans'),
        ]);
    }

    public function store(Request $request, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:platform_coupons,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'applies_to_plan' => ['nullable', Rule::in(array_keys(config('platform.plans')))],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->createCoupon($validated, auth('platform')->user());

        return redirect()
            ->route('platform.coupons.index')
            ->with('status', __('Coupon created.'));
    }
}
