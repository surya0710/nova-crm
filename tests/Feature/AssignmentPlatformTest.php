<?php

namespace Tests\Feature;

use App\Models\AssignmentHistory;
use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\AssignmentRule;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentService;
use App\Services\Assignment\Strategies\LeastLoadedStrategy;
use App\Services\Assignment\Strategies\ManualQueueStrategy;
use App\Services\Assignment\Strategies\RoundRobinStrategy;
use App\Services\Assignment\Strategies\WeightedRoundRobinStrategy;
use App\Services\LeadService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AssignmentPlatformTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization, 2: User, 3: User, 4: User}
     */
    protected function setupOrgWithMembers(): array
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $organization = Organization::factory()->create();
        $organization->addMember($owner, 'organization-owner');

        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $charlie = User::factory()->create(['name' => 'Charlie']);

        $organization->addMember($alice, 'sales-executive');
        $organization->addMember($bob, 'sales-executive');
        $organization->addMember($charlie, 'sales-executive');

        app(TenantContext::class)->set($organization);

        return [$owner, $organization, $alice, $bob, $charlie];
    }

    protected function makePool(Organization $organization, string $strategy, array $memberWeights): AssignmentPool
    {
        $pool = AssignmentPool::factory()->forOrganization($organization)->strategy($strategy)->create();

        foreach ($memberWeights as $userId => $weight) {
            AssignmentPoolMember::factory()->forPool($pool)->forUser(User::query()->findOrFail($userId))->weight($weight)->create();
        }

        return $pool->fresh(['members']);
    }

    protected function makeDefaultRule(Organization $organization, AssignmentPool $pool, array $overrides = []): AssignmentRule
    {
        return AssignmentRule::factory()
            ->forOrganization($organization)
            ->forPool($pool)
            ->defaultRule()
            ->create($overrides);
    }

    public function test_automatic_assignment_preserves_existing_owner_when_no_rule_assigns(): void
    {
        [$owner, $organization, $existingOwner] = $this->setupOrgWithMembers();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
            'assigned_to' => $existingOwner->id,
        ]);

        $result = app(AssignmentService::class)->assignOwner($lead, null, $owner, automatic: true);

        $this->assertNull($result->assigneeId());
        $this->assertSame($existingOwner->id, (int) $lead->fresh()->assigned_to);
        $this->assertDatabaseMissing('assignment_histories', [
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'new_owner_id' => null,
        ]);
    }

    public function test_round_robin_assigns_sequentially(): void
    {
        [, $organization, $alice, $bob, $charlie] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [
            $alice->id => 1,
            $bob->id => 1,
            $charlie->id => 1,
        ]);
        $this->makeDefaultRule($organization, $pool);

        $service = app(AssignmentService::class);
        $context = AssignmentContext::forLead($organization->id, ['source' => 'website', 'status' => 'new']);

        $first = $service->resolve($context);
        $second = $service->resolve($context);
        $third = $service->resolve($context);
        $fourth = $service->resolve($context);

        $this->assertSame($alice->id, $first->assigneeId());
        $this->assertSame($bob->id, $second->assigneeId());
        $this->assertSame($charlie->id, $third->assigneeId());
        $this->assertSame($alice->id, $fourth->assigneeId());
        $this->assertSame(4, $pool->fresh()->rotation_position);
    }

    public function test_weighted_round_robin_converges_to_weights(): void
    {
        [, $organization, $alice, $bob, $charlie] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'weighted_round_robin', [
            $alice->id => 5,
            $bob->id => 3,
            $charlie->id => 2,
        ]);
        $this->makeDefaultRule($organization, $pool);

        $service = app(AssignmentService::class);
        $context = AssignmentContext::forLead($organization->id);

        $counts = [$alice->id => 0, $bob->id => 0, $charlie->id => 0];
        for ($i = 0; $i < 10; $i++) {
            $counts[$service->resolve($context)->assigneeId()]++;
        }

        $this->assertSame(5, $counts[$alice->id]);
        $this->assertSame(3, $counts[$bob->id]);
        $this->assertSame(2, $counts[$charlie->id]);
    }

    public function test_least_loaded_prefers_lowest_open_lead_count(): void
    {
        [$owner, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'least_loaded', [
            $alice->id => 1,
            $bob->id => 1,
        ]);
        $this->makeDefaultRule($organization, $pool);

        Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'assigned_to' => $alice->id,
            'status' => 'new',
            'created_by' => $owner->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $bob->id,
            'status' => 'new',
            'created_by' => $owner->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $bob->id,
            'status' => 'converted',
            'created_by' => $owner->id,
        ]);

        $result = app(LeastLoadedStrategy::class)->assign($pool, AssignmentContext::forLead($organization->id));

        $this->assertSame($bob->id, $result->assigneeId());
    }

    public function test_manual_queue_returns_unassigned(): void
    {
        [, $organization, $alice] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'manual_queue', [$alice->id => 1]);

        $result = app(ManualQueueStrategy::class)->assign($pool, AssignmentContext::forLead($organization->id));

        $this->assertNull($result->assigneeId());
        $this->assertSame('manual_queue', $result->strategy);
    }

    public function test_inactive_members_never_receive_assignments(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = AssignmentPool::factory()->forOrganization($organization)->strategy('round_robin')->create();
        AssignmentPoolMember::factory()->forPool($pool)->forUser($alice)->inactive()->create();
        AssignmentPoolMember::factory()->forPool($pool)->forUser($bob)->create();
        $this->makeDefaultRule($organization, $pool);

        $service = app(AssignmentService::class);
        $context = AssignmentContext::forLead($organization->id);

        $this->assertSame($bob->id, $service->resolve($context)->assigneeId());
        $this->assertSame($bob->id, $service->resolve($context)->assigneeId());
    }

    public function test_inactive_pool_does_not_assign(): void
    {
        [, $organization, $alice] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $pool->update(['is_active' => false]);
        $this->makeDefaultRule($organization, $pool);

        $result = app(AssignmentService::class)->resolve(AssignmentContext::forLead($organization->id));

        $this->assertNull($result->assigneeId());
        $this->assertTrue($result->matched);
    }

    public function test_rule_priority_first_match_wins(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $poolA = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $poolB = $this->makePool($organization, 'round_robin', [$bob->id => 1]);

        AssignmentRule::factory()->forOrganization($organization)->forPool($poolB)->priority(20)->conditions([
            'source' => 'website',
        ])->create(['name' => 'Lower priority']);

        AssignmentRule::factory()->forOrganization($organization)->forPool($poolA)->priority(10)->conditions([
            'source' => 'website',
        ])->create(['name' => 'Higher priority']);

        $result = app(AssignmentService::class)->resolve(
            AssignmentContext::forLead($organization->id, ['source' => 'website'])
        );

        $this->assertSame($alice->id, $result->assigneeId());
        $this->assertSame('Higher priority', $result->rule->name);
    }

    public function test_inactive_rule_is_skipped(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $poolA = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $poolB = $this->makePool($organization, 'round_robin', [$bob->id => 1]);

        AssignmentRule::factory()->forOrganization($organization)->forPool($poolA)->priority(1)->inactive()->conditions([
            'source' => 'website',
        ])->create();

        AssignmentRule::factory()->forOrganization($organization)->forPool($poolB)->priority(50)->conditions([
            'source' => 'website',
        ])->create();

        $result = app(AssignmentService::class)->resolve(
            AssignmentContext::forLead($organization->id, ['source' => 'website'])
        );

        $this->assertSame($bob->id, $result->assigneeId());
    }

    public function test_default_rule_used_when_no_conditions_match(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $poolA = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $poolB = $this->makePool($organization, 'round_robin', [$bob->id => 1]);

        AssignmentRule::factory()->forOrganization($organization)->forPool($poolA)->priority(1)->conditions([
            'source' => 'facebook',
        ])->create();

        $this->makeDefaultRule($organization, $poolB, ['name' => 'Default']);

        $result = app(AssignmentService::class)->resolve(
            AssignmentContext::forLead($organization->id, ['source' => 'website'])
        );

        $this->assertSame($bob->id, $result->assigneeId());
        $this->assertTrue($result->rule->is_default);
    }

    public function test_no_rule_returns_unassigned(): void
    {
        [, $organization] = $this->setupOrgWithMembers();

        $result = app(AssignmentService::class)->resolve(AssignmentContext::forLead($organization->id));

        $this->assertNull($result->assigneeId());
        $this->assertFalse($result->matched);
    }

    public function test_metadata_condition_matching(): void
    {
        [, $organization, $alice] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [$alice->id => 1]);

        AssignmentRule::factory()->forOrganization($organization)->forPool($pool)->priority(1)->conditions([
            'metadata' => ['region' => 'emea'],
        ])->create();

        $match = app(AssignmentService::class)->resolve(
            AssignmentContext::forLead($organization->id, ['custom_fields' => ['region' => 'EMEA']])
        );
        $miss = app(AssignmentService::class)->resolve(
            AssignmentContext::forLead($organization->id, ['custom_fields' => ['region' => 'apac']])
        );

        $this->assertSame($alice->id, $match->assigneeId());
        $this->assertNull($miss->assigneeId());
    }

    public function test_lead_creation_uses_assignment_platform(): void
    {
        [$owner, $organization, $alice] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $this->makeDefaultRule($organization, $pool);

        $lead = app(LeadService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Auto Assigned',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
        ], $owner);

        $this->assertSame($alice->id, $lead->assigned_to);
        $this->assertDatabaseHas('assignment_histories', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'new_owner_id' => $alice->id,
            'reason' => AssignmentHistory::REASON_AUTOMATIC,
        ]);
    }

    public function test_explicit_owner_bypasses_assignment_platform(): void
    {
        [$owner, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $this->makeDefaultRule($organization, $pool);

        $lead = app(LeadService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Manual Owner',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'assigned_to' => $bob->id,
        ], $owner);

        $this->assertSame($bob->id, $lead->assigned_to);
        $this->assertDatabaseMissing('assignment_histories', [
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
        ]);
        $this->assertSame(0, $pool->fresh()->rotation_position);
    }

    public function test_assign_owner_notifies_the_new_assignee(): void
    {
        [$owner, $organization, $assignee] = $this->setupOrgWithMembers();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => null,
            'created_by' => $owner->id,
        ]);

        Notification::fake();
        $this->actingAs($owner);

        app(AssignmentService::class)->assignOwner($lead, $assignee->id, $owner);

        Notification::assertSentTo(
            $assignee,
            CrmNotification::class,
            fn (CrmNotification $notification) => $notification->title === 'New assignment'
                && $notification->organizationId === $organization->id
        );
        Notification::assertSentToTimes($assignee, CrmNotification::class, 1);
    }

    public function test_assign_owner_does_not_notify_when_the_owner_is_unchanged(): void
    {
        [$owner, $organization, $assignee] = $this->setupOrgWithMembers();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $assignee->id,
            'created_by' => $owner->id,
        ]);

        Notification::fake();
        $this->actingAs($owner);

        app(AssignmentService::class)->assignOwner($lead, $assignee->id, $owner);

        Notification::assertNothingSent();
        $this->assertSame(0, AuditLog::query()
            ->where('auditable_type', $lead->getMorphClass())
            ->where('auditable_id', $lead->id)
            ->where('event', 'assigned')
            ->count());
    }

    public function test_assign_owner_records_assignment_platform_audit_context(): void
    {
        [$owner, $organization, $previousOwner, $newOwner] = $this->setupOrgWithMembers();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $previousOwner->id,
            'created_by' => $owner->id,
        ]);

        Notification::fake();
        $this->actingAs($owner);

        app(AssignmentService::class)->assignOwner($lead, $newOwner->id, $owner);

        $audit = AuditLog::query()
            ->where('auditable_type', $lead->getMorphClass())
            ->where('auditable_id', $lead->id)
            ->where('event', 'assigned')
            ->sole();

        $this->assertSame($owner->id, $audit->user_id);
        $this->assertSame([
            'from' => $previousOwner->id,
            'to' => $newOwner->id,
            'via' => 'assignment_platform',
            'strategy' => 'manual',
            'rule_id' => null,
            'reason' => AssignmentHistory::REASON_REASSIGNED,
        ], $audit->properties);
    }

    public function test_api_lead_uses_assignment_when_owner_blank(): void
    {
        [$owner, $organization, $alice] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [$alice->id => 1]);
        $this->makeDefaultRule($organization, $pool);

        $lead = app(LeadService::class)->createFromApi([
            'name' => 'API Lead',
            'email' => 'api-assign@example.com',
            'source' => 'api',
        ], $owner, $organization);

        $this->assertSame($alice->id, $lead->assigned_to);
        $this->assertDatabaseHas('assignment_histories', [
            'entity_id' => $lead->id,
            'reason' => AssignmentHistory::REASON_API,
        ]);
    }

    public function test_round_robin_concurrency_does_not_duplicate_rotation_slots(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [
            $alice->id => 1,
            $bob->id => 1,
        ]);
        $this->makeDefaultRule($organization, $pool);

        $strategy = app(RoundRobinStrategy::class);
        $context = AssignmentContext::forLead($organization->id);
        $assignees = [];

        // Serialize concurrent callers through row locks: each assign() opens its own
        // transaction with lockForUpdate. Rapid sequential calls must never reuse a slot.
        for ($i = 0; $i < 20; $i++) {
            $assignees[] = $strategy->assign($pool, $context)->assigneeId();
        }

        $this->assertSame(20, $pool->fresh()->rotation_position);
        $this->assertCount(20, $assignees);

        // Exact alternating sequence — proves no skipped or duplicated positions.
        for ($i = 0; $i < 20; $i++) {
            $expected = ($i % 2 === 0) ? $alice->id : $bob->id;
            $this->assertSame($expected, $assignees[$i], "Rotation slot {$i} corrupted");
        }
    }

    public function test_round_robin_lock_prevents_same_position_in_nested_contention(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'round_robin', [
            $alice->id => 1,
            $bob->id => 1,
        ]);

        $strategy = app(RoundRobinStrategy::class);
        $context = AssignmentContext::forLead($organization->id);

        $results = DB::transaction(function () use ($strategy, $pool, $context) {
            // Outer transaction still allows inner strategy transactions (savepoints)
            // to advance rotation under lock without sharing the same position.
            return [
                $strategy->assign($pool, $context)->assigneeId(),
                $strategy->assign($pool, $context)->assigneeId(),
            ];
        });

        $this->assertSame([$alice->id, $bob->id], $results);
        $this->assertSame(2, $pool->fresh()->rotation_position);
    }

    public function test_tenant_isolation_for_pools_rules_and_history(): void
    {
        [$ownerA, $orgA, $aliceA] = $this->setupOrgWithMembers();
        $poolA = $this->makePool($orgA, 'round_robin', [$aliceA->id => 1]);
        $this->makeDefaultRule($orgA, $poolA);

        $ownerB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->addMember($ownerB, 'organization-owner');
        $bobB = User::factory()->create();
        $orgB->addMember($bobB, 'sales-executive');
        app(TenantContext::class)->set($orgB);
        $poolB = $this->makePool($orgB, 'round_robin', [$bobB->id => 1]);
        $this->makeDefaultRule($orgB, $poolB);

        app(TenantContext::class)->set($orgA);
        $leadA = app(LeadService::class)->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A',
            'status' => 'new',
            'source' => 'website',
            'priority' => 'medium',
        ], $ownerA);

        app(TenantContext::class)->set($orgB);
        $leadB = app(LeadService::class)->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B',
            'status' => 'new',
            'source' => 'website',
            'priority' => 'medium',
        ], $ownerB);

        $this->assertSame($aliceA->id, $leadA->assigned_to);
        $this->assertSame($bobB->id, $leadB->assigned_to);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, AssignmentPool::query()->count());
        $this->assertSame(1, AssignmentRule::query()->count());
        $this->assertSame(1, AssignmentHistory::query()->count());
        $this->assertTrue(AssignmentHistory::query()->where('entity_id', $leadA->id)->exists());
        $this->assertFalse(AssignmentHistory::query()->where('entity_id', $leadB->id)->exists());
    }

    public function test_weighted_strategy_is_deterministic_not_random(): void
    {
        [, $organization, $alice, $bob] = $this->setupOrgWithMembers();
        $pool = $this->makePool($organization, 'weighted_round_robin', [
            $alice->id => 2,
            $bob->id => 1,
        ]);

        $strategy = app(WeightedRoundRobinStrategy::class);
        $context = AssignmentContext::forLead($organization->id);

        $sequence = [];
        for ($i = 0; $i < 6; $i++) {
            $sequence[] = $strategy->assign($pool, $context)->assigneeId();
        }

        $this->assertSame([
            $alice->id, $alice->id, $bob->id,
            $alice->id, $alice->id, $bob->id,
        ], $sequence);
    }

    public function test_assignment_settings_page_requires_permission(): void
    {
        [$user, $organization] = (function () {
            $user = User::factory()->create();
            $organization = Organization::factory()->create();
            $organization->addMember($user, 'employee');

            return [$user, $organization];
        })();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('assignments.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_and_create_pool_via_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $member = User::factory()->create();
        $organization->addMember($member, 'sales-executive');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertSee('Assignment Settings');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('assignments.pools.store'), [
                'name' => 'Sales Pool',
                'strategy' => 'round_robin',
                'is_active' => '1',
                'members' => [
                    ['user_id' => $member->id, 'weight' => 1, 'is_active' => '1'],
                ],
            ])
            ->assertRedirect(route('assignments.index'));

        $this->assertDatabaseHas('assignment_pools', [
            'organization_id' => $organization->id,
            'name' => 'Sales Pool',
            'strategy' => 'round_robin',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'pool_created',
            'organization_id' => $organization->id,
        ]);
    }
}
