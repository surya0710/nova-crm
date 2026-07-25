<?php

namespace App\Events;

final class ProjectTemplateUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.template.updated';
    }
}
