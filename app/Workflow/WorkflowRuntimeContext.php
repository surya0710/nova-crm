<?php

namespace App\Workflow;

final class WorkflowRuntimeContext
{
    public function __construct(
        public ?string $causationId = null,
        public int $depth = 0,
    ) {}

    public function enter(string $causationId, int $depth): void
    {
        $this->causationId = $causationId;
        $this->depth = $depth;
    }

    public function clear(): void
    {
        $this->causationId = null;
        $this->depth = 0;
    }
}
