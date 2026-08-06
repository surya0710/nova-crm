<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class CrmCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $commands = collect();

        if (Route::has('crm.home') && $user->hasAnyPermission([
            'leads.view', 'customers.view', 'opportunities.view', 'products.view',
            'quotations.view', 'invoices.view', 'payments.view',
        ])) {
            $commands->push([
                'id' => 'crm.home',
                'label' => __('Open CRM Home'),
                'group' => __('CRM'),
                'href' => route('crm.home'),
                'keywords' => ['crm', 'home', 'sales'],
            ]);
        }

        if ($user->hasPermission('leads.create') && Route::has('leads.create')) {
            $commands->push([
                'id' => 'crm.create-lead',
                'label' => __('Create Lead'),
                'group' => __('CRM'),
                'href' => route('leads.create'),
                'keywords' => ['lead', 'new', 'create'],
            ]);
        }

        if ($user->hasPermission('leads.view') && Route::has('leads.index')) {
            $commands->push([
                'id' => 'crm.search-leads',
                'label' => __('Search Leads'),
                'group' => __('CRM'),
                'href' => route('leads.index'),
                'keywords' => ['lead', 'search', 'find'],
            ]);
        }

        if ($user->hasPermission('customers.view') && Route::has('customers.index')) {
            $commands->push([
                'id' => 'crm.open-customers',
                'label' => __('Open Customers'),
                'group' => __('CRM'),
                'href' => route('customers.index'),
                'keywords' => ['customer', 'accounts'],
            ]);
        }

        if ($user->hasPermission('customers.create') && Route::has('customers.create')) {
            $commands->push([
                'id' => 'crm.create-customer',
                'label' => __('Create Customer'),
                'group' => __('CRM'),
                'href' => route('customers.create'),
                'keywords' => ['customer', 'new', 'create'],
            ]);
        }

        if ($user->hasPermission('opportunities.view') && Route::has('pipeline.index')) {
            $commands->push([
                'id' => 'crm.open-opportunities',
                'label' => __('Open Opportunities'),
                'group' => __('CRM'),
                'href' => route('pipeline.index'),
                'keywords' => ['pipeline', 'opportunity', 'deals'],
            ]);
            $commands->push([
                'id' => 'crm.open-pipeline',
                'label' => __('Open Pipeline'),
                'group' => __('CRM'),
                'href' => route('pipeline.index', ['view' => 'board']),
                'keywords' => ['pipeline', 'board', 'kanban'],
            ]);
        }

        if (Route::has('crm.revenue') && $user->hasAnyPermission(['quotations.view', 'invoices.view', 'payments.view'])) {
            $commands->push([
                'id' => 'crm.open-revenue',
                'label' => __('Open Revenue'),
                'group' => __('CRM'),
                'href' => route('crm.revenue'),
                'keywords' => ['revenue', 'quotes', 'invoices', 'payments'],
            ]);
        }

        if ($user->hasPermission('leads.view') && Route::has('crm.activities')) {
            $commands->push([
                'id' => 'crm.open-activities',
                'label' => __('Open Activities'),
                'group' => __('CRM'),
                'href' => route('crm.activities'),
                'keywords' => ['activities', 'follow-ups', 'notes'],
            ]);
        }

        if ($user->hasAnyPermission(['leads.view', 'customers.view', 'opportunities.view']) && Route::has('crm.saved-views')) {
            $commands->push([
                'id' => 'crm.open-saved-views',
                'label' => __('Open Saved Views'),
                'group' => __('CRM'),
                'href' => route('crm.saved-views'),
                'keywords' => ['saved', 'views', 'filters'],
            ]);
        }

        if ($user->hasPermission('reports.view')) {
            $href = Route::has('crm.reports')
                ? route('crm.reports')
                : (Route::has('reports.finance') ? route('reports.finance') : null);
            if ($href) {
                $commands->push([
                    'id' => 'crm.open-reports',
                    'label' => __('Open Reports'),
                    'group' => __('CRM'),
                    'href' => $href,
                    'keywords' => ['reports', 'finance', 'revenue'],
                ]);
            }
        }

        return $commands;
    }
}
