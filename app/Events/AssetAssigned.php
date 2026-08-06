<?php

namespace App\Events;

final class AssetAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'asset.assigned';
    }
}
