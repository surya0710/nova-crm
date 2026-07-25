<?php

namespace App\Events;

final class AssetLost extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'asset.lost';
    }
}
