<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Platform\PlatformSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.subscriptions.index', [
            'overview' => $service->overview(),
            'plans' => config('platform.plans'),
        ]);
    }

    public function active(Request $request, PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.subscriptions.active', [
            'organizations' => $service->activeSubscriptions($request->only(['search', 'plan'])),
            'plans' => config('platform.plans'),
            'filters' => $request->only(['search', 'plan']),
        ]);
    }

    public function trials(Request $request, PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.subscriptions.trials', [
            'organizations' => $service->trials($request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function renewals(Request $request, PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.subscriptions.renewals', [
            'organizations' => $service->renewals($request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function assignPlan(Request $request, Organization $organization, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('platform.plans')))],
        ]);

        $service->assignPlan($organization, $validated['plan'], auth('platform')->user());

        return back()->with('status', __('Plan assigned.'));
    }

    public function upgrade(Request $request, Organization $organization, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('platform.plans')))],
        ]);

        $service->changePlan($organization, $validated['plan'], auth('platform')->user(), 'upgrade');

        return back()->with('status', __('Subscription upgraded.'));
    }

    public function downgrade(Request $request, Organization $organization, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('platform.plans')))],
        ]);

        $service->changePlan($organization, $validated['plan'], auth('platform')->user(), 'downgrade');

        return back()->with('status', __('Subscription downgraded.'));
    }

    public function startTrial(Request $request, Organization $organization, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $service->startTrial($organization, auth('platform')->user(), $validated['days'] ?? 14);

        return back()->with('status', __('Trial started.'));
    }

    public function endTrial(Request $request, Organization $organization, PlatformSubscriptionService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.manage');

        $validated = $request->validate([
            'convert' => ['nullable', 'boolean'],
        ]);

        $service->endTrial($organization, auth('platform')->user(), $validated['convert'] ?? true);

        return back()->with('status', __('Trial ended.'));
    }
}
