<?php

namespace App\Events;

final class AttendanceClockedIn extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.clocked_in';
    }
}
