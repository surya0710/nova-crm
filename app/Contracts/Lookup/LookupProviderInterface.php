<?php

namespace App\Contracts\Lookup;

use App\Models\Organization;
use App\Models\User;
use App\Services\Lookup\LookupPaginatedResult;

interface LookupProviderInterface
{
    /**
     * Stable entity key, e.g. "users".
     */
    public function key(): string;

    /**
     * Organization-scoped search with pagination.
     */
    public function search(
        Organization $organization,
        User $actor,
        string $query,
        int $page,
        int $perPage,
    ): LookupPaginatedResult;
}
