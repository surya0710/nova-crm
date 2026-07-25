<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\CandidateAccount;
use App\Services\TenantContext;
use Illuminate\View\View;

class CandidateAccountAdminController extends Controller
{
    public function __construct(protected TenantContext $tenant)
    {
        $this->middleware('permission:recruitment.portal.manage');
    }

    public function index(): View
    {
        $accounts = CandidateAccount::query()
            ->with('candidate')
            ->where('organization_id', $this->tenant->id())
            ->latest()
            ->paginate(20);

        return view('hrms.recruitment.portal.accounts', [
            'accounts' => $accounts,
        ]);
    }
}
