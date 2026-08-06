<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ClientPortalFacadeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __construct(protected ClientPortalFacadeService $facade) {}

    public function index(Request $request, Organization $organization): View
    {
        $dashboard = $this->facade->dashboard($request->user('client'));

        return view('portal.dashboard', [
            'dashboard' => $dashboard,
        ]);
    }
}
