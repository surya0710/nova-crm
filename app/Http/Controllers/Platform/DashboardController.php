<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformDashboardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(PlatformDashboardService $dashboard): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.dashboard');

        return view('platform.dashboard', [
            'metrics' => $dashboard->metrics(),
        ]);
    }
}
