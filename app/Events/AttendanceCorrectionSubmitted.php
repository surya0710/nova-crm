<?php

namespace App\Events;

final class AttendanceCorrectionSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.correction_submitted';
    }
}
