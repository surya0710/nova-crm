<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Optional provider capability for runtime-managed synchronization.
 *
 * Adapters execute provider API work and return normalized totals. They never
 * create or update synchronization runs; MarketingProviderService owns that
 * lifecycle and all persistence.
 */
interface MarketingProviderSynchronizationInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function synchronize(MarketingProvider $provider, array $options = []): array;
}
