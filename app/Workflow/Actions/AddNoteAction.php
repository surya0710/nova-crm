<?php

namespace App\Workflow\Actions;

use App\Services\NoteService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class AddNoteAction implements WorkflowActionHandler
{
    public function __construct(protected NoteService $notes) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $note = $this->notes->add($context->subject, (string) $configuration['body'], $context->actor);

        return ['note_id' => $note->id];
    }
}
