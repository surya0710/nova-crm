<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_user_with_tasks_view_can_access_tasks_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Tasks');
    }

    public function test_hr_user_without_tasks_permission_cannot_access_tasks(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.index'));

        $response->assertForbidden();
    }

    public function test_user_can_create_task_linked_to_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Call prospect',
                'status' => 'pending',
                'priority' => 'high',
                'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'taskable_type' => 'lead',
                'taskable_id' => $lead->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'title' => 'Call prospect',
            'taskable_type' => Lead::class,
            'taskable_id' => $lead->id,
        ]);
    }

    public function test_user_can_complete_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $task = Task::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tasks.complete', $task));

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_assigning_task_notifies_assignee(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');

        $task = Task::factory()->pending()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'status' => 'pending',
                'priority' => 'medium',
                'assigned_to' => $assignee->id,
            ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);
    }

    public function test_dashboard_shows_task_stats(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Task::factory()->overdue()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Open Tasks');
        $response->assertSee('Overdue');
    }
}
