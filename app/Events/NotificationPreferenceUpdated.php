<?php

namespace App\Events;

final class NotificationPreferenceUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'notification.preference.updated';
    }
}
