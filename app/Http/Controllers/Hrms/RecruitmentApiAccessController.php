<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\View\View;

class RecruitmentApiAccessController extends Controller
{
    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.api.manage', $organization), 403);

        return view('hrms.recruitment.integrations.api-access', [
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api/v1/recruitment/jobs'],
                ['method' => 'GET', 'path' => '/api/v1/recruitment/applications'],
                ['method' => 'GET', 'path' => '/api/v1/recruitment/candidates'],
                ['method' => 'GET', 'path' => '/api/v1/recruitment/offers'],
                ['method' => 'GET', 'path' => '/api/v1/recruitment/reports'],
            ],
        ]);
    }
}
