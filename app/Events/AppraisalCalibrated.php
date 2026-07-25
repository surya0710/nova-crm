<?php

namespace App\Events;

final class AppraisalCalibrated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'appraisal.calibrated';
    }
}
