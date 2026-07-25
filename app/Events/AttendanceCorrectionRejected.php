<?php

namespace App\Events;

final class AttendanceCorrectionRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.correction_rejected';
    }
}
