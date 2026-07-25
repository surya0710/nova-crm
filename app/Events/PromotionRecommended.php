<?php

namespace App\Events;

final class PromotionRecommended extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'promotion.recommended';
    }
}
