<?php

namespace App\Services\Assignment;

use App\Models\AssignmentPool;
use App\Models\AssignmentRule;
use App\Models\User;

/**
 * Immutable outcome of assignment resolution. Does not persist.
 */
final class AssignmentResult
{
    public function __construct(
        public readonly ?User $assignee,
        public readonly ?string $strategy = null,
        public readonly ?AssignmentRule $rule = null,
        public readonly ?AssignmentPool $pool = null,
        public readonly bool $matched = false,
        public readonly ?string $message = null,
    ) {}

    public static function unassigned(?string $message = null, ?AssignmentRule $rule = null, ?AssignmentPool $pool = null, ?string $strategy = null): self
    {
        return new self(
            assignee: null,
            strategy: $strategy,
            rule: $rule,
            pool: $pool,
            matched: $rule !== null,
            message: $message,
        );
    }

    public function assigneeId(): ?int
    {
        return $this->assignee?->id;
    }
}
