<?php

namespace App\Services\Import;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shared owner resolution for entity import adapters.
 *
 * Matching rules (frozen for Lead and Customer imports):
 * 1. Organization member email (case-insensitive)
 * 2. Organization member full name (case-insensitive, exactly one match)
 *
 * listMembers() is the single source of users eligible for Owner matching
 * (same membership set Assignment / Lead assignment UIs use for org members).
 */
class ImportOwnerResolver
{
    /**
     * Organization members that can be referenced in an Owner column.
     *
     * @return Collection<int, User>
     */
    public function listMembers(Organization $organization): Collection
    {
        return $organization->users()->orderBy('name')->get();
    }

    public function resolve(Organization $organization, string $value): ?User
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $byEmail = $organization->users()
            ->whereRaw('LOWER(email) = ?', [strtolower($value)])
            ->first();

        if ($byEmail) {
            return $byEmail;
        }

        $byName = $organization->users()
            ->whereRaw('LOWER(name) = ?', [strtolower($value)])
            ->get();

        if ($byName->count() === 1) {
            return $byName->first();
        }

        return null;
    }
}
