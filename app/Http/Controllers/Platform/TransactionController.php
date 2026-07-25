<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request, PlatformSubscriptionService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.subscriptions.view');

        return view('platform.transactions.index', [
            'transactions' => $service->transactions($request->only(['search', 'status'])),
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
