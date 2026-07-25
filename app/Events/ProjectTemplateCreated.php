<?php

namespace App\Events;

final class ProjectTemplateCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.template.created';
    }
}
