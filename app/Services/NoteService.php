<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class NoteService
{
    public function add(Model $subject, string $body, User $actor): Model
    {
        if (trim($body) === '') {
            throw ValidationException::withMessages(['body' => 'A note body is required.']);
        }
        if (! $subject->organization->users()->whereKey($actor->id)->exists()) {
            throw ValidationException::withMessages(['actor' => 'The actor is not an organization member.']);
        }

        [$class, $foreignKey] = match (true) {
            $subject instanceof Lead => [LeadNote::class, 'lead_id'],
            $subject instanceof Customer => [CustomerNote::class, 'customer_id'],
            $subject instanceof Opportunity => [OpportunityNote::class, 'opportunity_id'],
            default => throw ValidationException::withMessages(['subject' => 'Notes are not supported for this entity.']),
        };

        return $class::query()->create([
            'organization_id' => $subject->organization_id,
            $foreignKey => $subject->getKey(),
            'user_id' => $actor->id,
            'body' => $body,
        ]);
    }
}
