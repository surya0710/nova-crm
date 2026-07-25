<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformSubscriptionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.plans.index', [
            'plans' => $service->planCatalog(),
        ]);
    }
}
