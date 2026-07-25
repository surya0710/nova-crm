<?php

namespace App\Events;

final class AssetReturned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'asset.returned';
    }
}
