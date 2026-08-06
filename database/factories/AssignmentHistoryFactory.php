<?php

namespace Database\Factories;

use App\Models\AssignmentHistory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentHistory>
 */
class AssignmentHistoryFactory extends Factory
{
    protected $model = AssignmentHistory::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'entity_type' => 'lead',
            'entity_id' => 1,
            'previous_owner_id' => null,
            'new_owner_id' => null,
            'strategy' => 'round_robin',
            'assignment_rule_id' => null,
            'assignment_pool_id' => null,
            'assigned_by' => null,
            'reason' => AssignmentHistory::REASON_AUTOMATIC,
            'assigned_at' => now(),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
}
