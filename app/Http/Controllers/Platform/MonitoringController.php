<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformMonitoringService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(PlatformMonitoringService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.monitoring.view');

        return view('platform.monitoring.index', [
            'snapshot' => $service->snapshot(),
        ]);
    }
}
