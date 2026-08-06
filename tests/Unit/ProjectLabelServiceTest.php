<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\ProjectLabel;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\User;
use App\Services\ProjectLabelService;
use App\Services\TaskDefaultsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLabelServiceTest extends TestCase
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
            'title' => 'Label Task',
            'slug' => 'label-task',
            'task_number' => 'TASK-0001',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $creator->id,
            'is_archived' => false,
            'completion_percentage' => 0,
        ]);
    }

    public function test_create_label(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(ProjectLabelService::class);

        $label = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Urgent-ish',
            'color' => '#ff0000',
            'description' => 'Needs attention',
        ], $user);

        $this->assertInstanceOf(ProjectLabel::class, $label);
        $this->assertSame('Urgent-ish', $label->name);
        $this->assertSame($organization->id, $label->organization_id);
        $this->assertFalse($label->is_system);
    }

    public function test_attach_and_detach_label_on_task(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(ProjectLabelService::class);
        $task = $this->makeTask($organization, $user);

        $label = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Backend',
            'color' => '#2563eb',
        ], $user);

        $pivot = $service->attach($task, $label, $user);
        $this->assertInstanceOf(TaskLabel::class, $pivot);
        $this->assertDatabaseHas('task_labels', [
            'task_id' => $task->id,
            'label_id' => $label->id,
        ]);

        $service->detach($task, $label, $user);
        $this->assertDatabaseMissing('task_labels', [
            'task_id' => $task->id,
            'label_id' => $label->id,
        ]);
    }

    public function test_seed_defaults_creates_system_labels(): void
    {
        [, $organization] = $this->setupOrg();
        $service = app(ProjectLabelService::class);

        $service->seedDefaults($organization);

        $this->assertGreaterThanOrEqual(count(ProjectLabelService::DEFAULT_LABELS), ProjectLabel::query()
            ->where('organization_id', $organization->id)
            ->where('is_system', true)
            ->count());

        $this->assertDatabaseHas('project_labels', [
            'organization_id' => $organization->id,
            'name' => 'Urgent',
            'is_system' => true,
        ]);
    }
}
