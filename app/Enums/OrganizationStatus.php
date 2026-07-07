<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return config("platform.organization_statuses.{$this->value}", $this->value);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}
