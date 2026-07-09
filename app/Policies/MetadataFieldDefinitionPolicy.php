<?php

namespace App\Policies;

use App\Models\MetadataFieldDefinition;
use App\Models\User;

class MetadataFieldDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('metadata.view') || $user->hasPermission('metadata.manage');
    }

    public function view(User $user, MetadataFieldDefinition $field): bool
    {
        return $user->hasPermission('metadata.view', $field->organization)
            || $user->hasPermission('metadata.manage', $field->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('metadata.create') || $user->hasPermission('metadata.manage');
    }

    public function update(User $user, MetadataFieldDefinition $field): bool
    {
        return $user->hasPermission('metadata.update', $field->organization)
            || $user->hasPermission('metadata.manage', $field->organization);
    }

    public function delete(User $user, MetadataFieldDefinition $field): bool
    {
        return $user->hasPermission('metadata.delete', $field->organization)
            || $user->hasPermission('metadata.manage', $field->organization);
    }
}
