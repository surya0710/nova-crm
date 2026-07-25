<?php

namespace App\Events;

final class AttendanceCorrectionApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.correction_approved';
    }
}
