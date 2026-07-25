<?php

namespace App\Events;

final class AttendanceClockedOut extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.clocked_out';
    }
}
