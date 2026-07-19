<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Str;

abstract class WorkflowDomainEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public readonly string $eventId;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly int $organizationId,
        public readonly string $subjectType,
        public readonly int|string $subjectId,
        public readonly array $subjectSnapshot,
        public readonly array $payload = [],
        ?string $eventId = null,
        public readonly ?string $causationId = null,
        public readonly int $depth = 0,
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }

    abstract public function trigger(): string;

    /** @param array<string, mixed> $payload */
    public static function forModel(
        Model $subject,
        array $payload = [],
        ?string $eventId = null,
        ?string $causationId = null,
        int $depth = 0,
    ): static {
        // Capture the state that will be visible after commit, rather than a
        // caller's potentially stale in-memory attributes.
        $subject = $subject->fresh() ?? $subject;

        return new static(
            organizationId: (int) $subject->getAttribute('organization_id'),
            subjectType: $subject->getMorphClass(),
            subjectId: $subject->getKey(),
            subjectSnapshot: $subject->attributesToArray(),
            payload: $payload,
            eventId: $eventId,
            causationId: $causationId,
            depth: $depth,
        );
    }
}
