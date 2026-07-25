<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Services\TaskDefaultsService;
use App\Services\TaskRecurrenceService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRecurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    protected function makeTask(Organization $organization, User $creator): Task
    {
        app(TaskDefaultsService::class)->seedAll($organization);

        return Task::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Recurring Seed',
            'slug' => 'recurring-seed',
            'task_number' => 'TASK-0001',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->toDateString(),
            'created_by' => $creator->id,
            'is_archived' => false,
            'completion_percentage' => 0,
        ]);
    }

    public function test_create_recurrence_sets_next_run_at(): void
    {
        [$user, $organization] = $this->setupOrg();
        $task = $this->makeTask($organization, $user);
        $service = app(TaskRecurrenceService::class);

        $recurrence = $service->create($task, [
            'recurrence_type' => 'daily',
            'interval' => 1,
            'end_type' => 'never',
        ], $user);

        $this->assertInstanceOf(TaskRecurrence::class, $recurrence);
        $this->assertTrue($recurrence->is_active);
        $this->assertNotNull($recurrence->next_run_at);
        $this->assertSame('daily', $recurrence->recurrence_type);
    }

    public function test_calculate_next_run_at_for_daily_interval(): void
    {
        $service = app(TaskRecurrenceService::class);
        $from = Carbon::parse('2026-07-24 09:00:00');

        $next = $service->calculateNextRunAtFromValues([
            'recurrence_type' => 'daily',
            'interval' => 2,
            'end_type' => 'never',
        ], $from);

        $this->assertNotNull($next);
        $this->assertSame('2026-07-26', $next->toDateString());
    }
}
