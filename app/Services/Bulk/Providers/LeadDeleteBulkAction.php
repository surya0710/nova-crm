<?php

namespace App\Services\Bulk\Providers;

use App\Models\Lead;
use App\Services\Bulk\Providers\Concerns\AppliesLeadListingFilters;

/**
 * Lead hard-delete with listing-filter support for "select all filtered".
 */
class LeadDeleteBulkAction extends CrmDeleteBulkAction
{
    use AppliesLeadListingFilters;

    public function __construct()
    {
        parent::__construct('lead', Lead::class, 'leads.delete');
    }
}
