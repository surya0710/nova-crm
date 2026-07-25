<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class QuickActionUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /** @param array<string, mixed> $changes */
    public function __construct(
        public readonly int $organizationId,
        public readonly int $quickActionId,
        public readonly array $changes = [],
        public readonly ?int $actorId = null,
    ) {}
}
