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

    /**
     * @return list<Address>
     */
    protected function organizationReplyTo(Organization $organization, ?string $fallbackAddress = null): array
    {
        $configured = app(OrganizationMailConfig::class)->for($organization)->replyToAddress();

        if ($configured) {
            return [$configured];
        }

        if (filled($fallbackAddress) && filter_var($fallbackAddress, FILTER_VALIDATE_EMAIL)) {
            return [new Address($fallbackAddress, $organization->name)];
        }

        return [];
    }
}
