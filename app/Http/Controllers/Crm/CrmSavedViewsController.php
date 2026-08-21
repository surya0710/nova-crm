<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmSavedViewsController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, SavedFilterService $savedFilters): View
    {
        abort_unless(
            $request->user()->hasAnyPermission(['leads.view', 'customers.view', 'opportunities.view']),
            403
        );

        $organization = $tenant->get();
        $user = $request->user();

        $views = collect();
        foreach (['lead', 'customer', 'opportunity'] as $entity) {
            $permission = match ($entity) {
                'lead' => 'leads.view',
                'customer' => 'customers.view',
                default => 'opportunities.view',
            };
            if (! $user->hasPermission($permission)) {
                continue;
            }
            $views = $views->merge(
                $savedFilters->availableFor($user, $organization->id, $entity)->map(fn ($filter) => [
                    'filter' => $filter,
                    'entity' => $entity,
                    'is_default' => $savedFilters->isDefaultFor($user, $organization->id, $filter),
                    'href' => match ($entity) {
                        'lead' => route('leads.index', ['saved_filter' => $filter->id]),
                        'customer' => route('customers.index', ['saved_filter' => $filter->id]),
                        default => route('pipeline.index', ['saved_filter' => $filter->id]),
                    },
                ])
            );
        }

        return view('crm.saved-views', [
            'views' => $views->values(),
        ]);
    }
}
