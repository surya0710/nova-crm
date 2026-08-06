<?php

namespace App\Events;

final class ProjectTemplateDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.template.deleted';
    }
}
