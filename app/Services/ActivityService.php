<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ActivityService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Model $subject, string $event, array $properties, User $actor): AuditLog
    {
        if (trim($event) === '') {
            throw ValidationException::withMessages(['event' => 'An activity event is required.']);
        }

        return $this->auditLogger->log($subject, $event, $properties, $actor);
    }
}
