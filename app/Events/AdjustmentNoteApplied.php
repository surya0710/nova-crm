<?php

namespace App\Events;

final class AdjustmentNoteApplied extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'adjustment_note.applied';
    }
}
