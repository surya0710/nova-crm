<?php

namespace Tests\Concerns;

use App\Models\Organization;
use Illuminate\Support\Facades\Crypt;

trait ConfiguresOrganizationMail
{
    protected function configureOrganizationMail(Organization $organization, string $driver = 'log'): Organization
    {
        $settings = $organization->settings ?? [];
        $settings['mail'] = [
            'enabled' => true,
            'provider' => $driver,
            'driver' => $driver,
            'host' => $driver === 'smtp' ? 'smtp.example.com' : '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $driver === 'smtp' ? 'user@example.com' : '',
            'password' => $driver === 'smtp' ? Crypt::encryptString('secret') : '',
            'from_address' => 'billing@'.$organization->slug.'.test',
            'from_name' => $organization->name,
        ];

        $organization->update(['settings' => $settings]);

        return $organization->fresh();
    }
}
