<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

interface SearchProviderInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @return Collection<int, array{type: string, label: string, title: string, subtitle: string|null, url: string, workspace?: string|null}>
     */
    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection;
}
