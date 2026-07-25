<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class WidgetRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $widgetId,
        public readonly string $widgetKey,
        public readonly ?int $organizationId = null,
    ) {}
}
