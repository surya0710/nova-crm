<?php

namespace App\Enums;

enum UserAccountStatus: string
{
    case PendingInvitation = 'pending_invitation';
    case Active = 'active';
    case Disabled = 'disabled';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::PendingInvitation => __('Pending Invitation'),
            self::Active => __('Active'),
            self::Disabled => __('Disabled'),
            self::Locked => __('Locked'),
        };
    }

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
