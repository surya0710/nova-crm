<?php

namespace App\Events;

final class AdjustmentNoteCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'adjustment_note.created';
    }
}
