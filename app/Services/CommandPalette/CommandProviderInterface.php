<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

interface CommandProviderInterface
{
    /**
     * @return Collection<int, array{id: string, label: string, group: string, href?: string|null, action?: string|null, keywords?: array, permission?: string|null}>
     */
    public function commands(User $user, ?Organization $organization): Collection;
}
