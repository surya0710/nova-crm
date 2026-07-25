<?php

namespace App\Events;

final class ForecastGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'forecast.generated';
    }
}
