<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceCalendar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_calendars_index_is_accessible_with_permission(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('resources.calendars.index'))
            ->assertOk();
    }

    public function test_user_can_create_update_and_delete_calendar(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('resources.calendars.store'), [
                'employee_id' => $employee->id,
                'working_hours_per_day' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'timezone' => 'UTC',
                'effective_from' => '2026-07-01',
                'effective_to' => '',
            ])
            ->assertRedirect(route('resources.calendars.index'));

        $calendar = ResourceCalendar::query()
            ->where('organization_id', $organization->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertDatabaseHas('resource_calendars', [
            'id' => $calendar->id,
            'working_hours_per_day' => 8,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('resources.calendars.update', $calendar), [
                'employee_id' => $employee->id,
                'working_hours_per_day' => 6,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday'],
                'timezone' => 'UTC',
                'effective_from' => '2026-07-01',
                'effective_to' => '2026-12-31',
            ])
            ->assertRedirect(route('resources.calendars.index'));

        $this->assertDatabaseHas('resource_calendars', [
            'id' => $calendar->id,
            'working_hours_per_day' => 6,
            'effective_to' => '2026-12-31',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('resources.calendars.destroy', $calendar))
            ->assertRedirect(route('resources.calendars.index'));

        $this->assertDatabaseMissing('resource_calendars', ['id' => $calendar->id]);
    }
}
