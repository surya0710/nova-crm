<?php

namespace App\Events;

final class AttendanceOvertimeRecorded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'attendance.overtime_recorded';
    }
}
