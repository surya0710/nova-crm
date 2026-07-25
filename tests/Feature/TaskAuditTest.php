<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Audit Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_audit_log_created_on_task_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Audited Task',
                'project_id' => $project->id,
                'priority' => 'medium',
            ]);

        $task = Task::query()->where('organization_id', $organization->id)->where('title', 'Audited Task')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $task->getMorphClass(),
            'auditable_id' => $task->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $audit = AuditLog::query()
            ->where('auditable_id', $task->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Audited Task', $audit->subject);
    }
}
