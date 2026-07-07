<?php

namespace App\Mail\Concerns;

use App\Models\Organization;
use App\Services\OrganizationMailConfig;
use Illuminate\Mail\Mailables\Address;

trait UsesOrganizationMailFrom
{
    /**
     * @return array<int, Address>
     */
    protected function organizationFrom(Organization $organization): array
    {
        $from = app(OrganizationMailConfig::class)->for($organization)->fromAddress();

        return $from ? [$from] : [];
    }
}
