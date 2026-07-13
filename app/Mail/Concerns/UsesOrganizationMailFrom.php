<?php

namespace App\Mail\Concerns;

use App\Models\Organization;
use App\Services\OrganizationMailConfig;
use Illuminate\Mail\Mailables\Address;

trait UsesOrganizationMailFrom
{
    protected function organizationFrom(Organization $organization): ?Address
    {
        return app(OrganizationMailConfig::class)->for($organization)->fromAddress();
    }
}
