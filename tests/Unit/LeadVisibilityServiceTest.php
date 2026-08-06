<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\TestCase;

class LeadVisibilityServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_view_all_delegates_to_leads_manage_permission(): void
    {
        $organization = new Organization(['id' => 1]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->once()
            ->with('leads.manage', $organization)
            ->andReturn(true);

        $service = new LeadVisibilityService;

        $this->assertTrue($service->canViewAll($user, $organization));
    }

    public function test_resolve_assigned_to_filter_forces_self_for_restricted_users(): void
    {
        $organization = new Organization(['id' => 1]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('hasPermission')
            ->with('leads.manage', $organization)
            ->andReturn(false);

        $service = new LeadVisibilityService;

        $this->assertSame(42, $service->resolveAssignedToFilter($user, $organization, 99));
        $this->assertSame(42, $service->resolveAssignedToFilter($user, $organization, null));
    }

    public function test_resolve_assigned_to_filter_honours_request_for_managers(): void
    {
        $organization = new Organization(['id' => 1]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 42;
        $user->shouldReceive('hasPermission')
            ->with('leads.manage', $organization)
            ->andReturn(true);

        $service = new LeadVisibilityService;

        $this->assertSame(99, $service->resolveAssignedToFilter($user, $organization, 99));
        $this->assertNull($service->resolveAssignedToFilter($user, $organization, null));
        $this->assertNull($service->resolveAssignedToFilter($user, $organization, 0));
    }

    public function test_can_access_requires_assignee_match_when_not_manager(): void
    {
        $organization = new Organization(['id' => 7]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 5;
        $user->shouldReceive('hasPermission')
            ->with('leads.view', $organization)
            ->andReturn(true);
        $user->shouldReceive('hasPermission')
            ->with('leads.manage', $organization)
            ->andReturn(false);

        $own = new Lead(['assigned_to' => 5]);
        $own->setRelation('organization', $organization);

        $other = new Lead(['assigned_to' => 9]);
        $other->setRelation('organization', $organization);

        $unassigned = new Lead(['assigned_to' => null]);
        $unassigned->setRelation('organization', $organization);

        $service = new LeadVisibilityService;

        $this->assertTrue($service->canAccess($user, $own));
        $this->assertFalse($service->canAccess($user, $other));
        $this->assertFalse($service->canAccess($user, $unassigned));
    }

    public function test_apply_adds_assigned_to_constraint_for_restricted_users(): void
    {
        $organization = new Organization(['id' => 1]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 12;
        $user->shouldReceive('hasPermission')
            ->with('leads.manage', $organization)
            ->andReturn(false);

        $query = Mockery::mock(Builder::class);
        $model = new Lead;
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('where')
            ->once()
            ->with('leads.assigned_to', 12)
            ->andReturnSelf();

        $service = new LeadVisibilityService;
        $this->assertSame($query, $service->apply($query, $user, $organization));
    }

    public function test_apply_is_noop_for_managers(): void
    {
        $organization = new Organization(['id' => 1]);
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('leads.manage', $organization)
            ->andReturn(true);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->never();

        $service = new LeadVisibilityService;
        $this->assertSame($query, $service->apply($query, $user, $organization));
    }
}
