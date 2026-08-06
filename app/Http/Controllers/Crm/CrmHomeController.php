<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, CrmWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'leads.view',
                'customers.view',
                'opportunities.view',
                'products.view',
                'quotations.view',
                'invoices.view',
                'payments.view',
            ]),
            403
        );

        $data = $home->build($request->user());

        return view('crm.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
