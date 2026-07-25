<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, PlatformAuditService $audit): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.audit.view');

        return view('platform.audit.index', [
            'logs' => $audit->paginate($request->only([
                'event', 'organization_id', 'search', 'category', 'from', 'to',
            ])),
            'filters' => $request->only([
                'event', 'organization_id', 'search', 'category', 'from', 'to',
            ]),
            'organizations' => \App\Models\Organization::query()->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }
}
