<?php

namespace App\Events;

final class FeedbackCampaignCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'feedback.campaign.created';
    }
}
