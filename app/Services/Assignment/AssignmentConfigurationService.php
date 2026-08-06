<?php

namespace App\Services\Assignment;

use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\AssignmentRule;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Configuration write authority for pools and rules (UI / settings).
 */
class AssignmentConfigurationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected AssignmentStrategyRegistry $strategies,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     strategy: string,
     *     is_active?: bool,
     *     members?: list<array{user_id: int, weight?: int, is_active?: bool}>
     * }  $data
     */
    public function createPool(Organization $organization, array $data, User $actor): AssignmentPool
    {
        $this->assertValidStrategy($data['strategy']);

        return DB::transaction(function () use ($organization, $data, $actor) {
            $pool = AssignmentPool::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'strategy' => $data['strategy'],
                'is_active' => $data['is_active'] ?? true,
                'rotation_position' => 0,
            ]);

            $this->syncMembers($pool, $data['members'] ?? []);

            $this->auditLogger->log($pool, 'pool_created', [
                'name' => $pool->name,
                'strategy' => $pool->strategy,
            ], $actor);

            return $pool->fresh(['members']);
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     description?: string|null,
     *     strategy?: string,
     *     is_active?: bool,
     *     members?: list<array{user_id: int, weight?: int, is_active?: bool}>
     * }  $data
     */
    public function updatePool(AssignmentPool $pool, array $data, User $actor): AssignmentPool
    {
        if (isset($data['strategy'])) {
            $this->assertValidStrategy($data['strategy']);
        }

        return DB::transaction(function () use ($pool, $data, $actor) {
            $before = $pool->only(['name', 'description', 'strategy', 'is_active']);

            $pool->fill([
                'name' => $data['name'] ?? $pool->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $pool->description,
                'strategy' => $data['strategy'] ?? $pool->strategy,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $pool->is_active,
            ]);
            $pool->save();

            if (array_key_exists('members', $data)) {
                $this->syncMembers($pool, $data['members']);
            }

            $event = ($before['is_active'] && ! $pool->is_active) ? 'pool_disabled' : 'pool_updated';

            $this->auditLogger->log($pool, $event, [
                'before' => $before,
                'after' => $pool->only(['name', 'description', 'strategy', 'is_active']),
            ], $actor);

            return $pool->fresh(['members']);
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     entity_type: string,
     *     priority?: int,
     *     is_active?: bool,
     *     is_default?: bool,
     *     strategy?: string|null,
     *     assignment_pool_id?: int|null,
     *     conditions?: array<string, mixed>
     * }  $data
     */
    public function createRule(Organization $organization, array $data, User $actor): AssignmentRule
    {
        if (! empty($data['strategy'])) {
            $this->assertValidStrategy($data['strategy']);
        }

        $this->assertPoolBelongsToOrganization($organization, $data['assignment_pool_id'] ?? null);

        return DB::transaction(function () use ($organization, $data, $actor) {
            if (! empty($data['is_default'])) {
                $this->clearDefaultFlags($organization->id, $data['entity_type']);
            }

            $rule = AssignmentRule::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'entity_type' => $data['entity_type'],
                'priority' => $data['priority'] ?? 100,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'strategy' => $data['strategy'] ?? null,
                'assignment_pool_id' => $data['assignment_pool_id'] ?? null,
                'conditions' => $data['conditions'] ?? [],
            ]);

            $this->auditLogger->log($rule, 'rule_created', [
                'name' => $rule->name,
                'entity_type' => $rule->entity_type,
                'priority' => $rule->priority,
            ], $actor);

            return $rule->fresh(['pool']);
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     entity_type?: string,
     *     priority?: int,
     *     is_active?: bool,
     *     is_default?: bool,
     *     strategy?: string|null,
     *     assignment_pool_id?: int|null,
     *     conditions?: array<string, mixed>
     * }  $data
     */
    public function updateRule(AssignmentRule $rule, array $data, User $actor): AssignmentRule
    {
        if (! empty($data['strategy'])) {
            $this->assertValidStrategy($data['strategy']);
        }

        $organizationId = $rule->organization_id;
        $this->assertPoolBelongsToOrganization(
            Organization::query()->findOrFail($organizationId),
            array_key_exists('assignment_pool_id', $data) ? $data['assignment_pool_id'] : $rule->assignment_pool_id,
        );

        return DB::transaction(function () use ($rule, $data, $actor, $organizationId) {
            $before = $rule->only(['name', 'entity_type', 'priority', 'is_active', 'is_default', 'strategy', 'assignment_pool_id', 'conditions']);

            $entityType = $data['entity_type'] ?? $rule->entity_type;
            $becomingDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $rule->is_default;

            if ($becomingDefault) {
                $this->clearDefaultFlags($organizationId, $entityType, $rule->id);
            }

            $rule->fill([
                'name' => $data['name'] ?? $rule->name,
                'entity_type' => $entityType,
                'priority' => $data['priority'] ?? $rule->priority,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $rule->is_active,
                'is_default' => $becomingDefault,
                'strategy' => array_key_exists('strategy', $data) ? $data['strategy'] : $rule->strategy,
                'assignment_pool_id' => array_key_exists('assignment_pool_id', $data) ? $data['assignment_pool_id'] : $rule->assignment_pool_id,
                'conditions' => array_key_exists('conditions', $data) ? $data['conditions'] : $rule->conditions,
            ]);
            $rule->save();

            $event = ($before['is_active'] && ! $rule->is_active) ? 'rule_disabled' : 'rule_updated';

            $this->auditLogger->log($rule, $event, [
                'before' => $before,
                'after' => $rule->only(['name', 'entity_type', 'priority', 'is_active', 'is_default', 'strategy', 'assignment_pool_id', 'conditions']),
            ], $actor);

            return $rule->fresh(['pool']);
        });
    }

    /**
     * @param  list<array{user_id: int, weight?: int, is_active?: bool}>  $members
     */
    protected function syncMembers(AssignmentPool $pool, array $members): void
    {
        $keepIds = [];

        foreach ($members as $memberData) {
            $userId = (int) $memberData['user_id'];
            $this->assertUserInOrganization($pool->organization_id, $userId);

            $member = AssignmentPoolMember::query()->updateOrCreate(
                [
                    'assignment_pool_id' => $pool->id,
                    'user_id' => $userId,
                ],
                [
                    'organization_id' => $pool->organization_id,
                    'weight' => max(1, (int) ($memberData['weight'] ?? 1)),
                    'is_active' => $memberData['is_active'] ?? true,
                ],
            );

            $keepIds[] = $member->id;
        }

        AssignmentPoolMember::query()
            ->where('assignment_pool_id', $pool->id)
            ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
            ->when($keepIds === [], fn ($q) => $q)
            ->delete();
    }

    protected function clearDefaultFlags(int $organizationId, string $entityType, ?int $exceptRuleId = null): void
    {
        AssignmentRule::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('is_default', true)
            ->when($exceptRuleId, fn ($q) => $q->where('id', '!=', $exceptRuleId))
            ->update(['is_default' => false]);
    }

    protected function assertValidStrategy(string $strategy): void
    {
        if (! $this->strategies->has($strategy)) {
            throw ValidationException::withMessages([
                'strategy' => "Unknown assignment strategy [{$strategy}].",
            ]);
        }
    }

    protected function assertPoolBelongsToOrganization(Organization $organization, mixed $poolId): void
    {
        if ($poolId === null || $poolId === '') {
            return;
        }

        $exists = AssignmentPool::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereKey((int) $poolId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'assignment_pool_id' => 'Assignment pool does not belong to this organization.',
            ]);
        }
    }

    protected function assertUserInOrganization(int $organizationId, int $userId): void
    {
        $exists = DB::table('organization_user')
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'members' => 'Pool members must belong to the organization.',
            ]);
        }
    }
}
