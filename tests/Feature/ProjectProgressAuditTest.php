<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\User;
use App\Services\ProgressTrackingService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressAuditTest extends TestCase
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
            'name' => 'Audit Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_audit_log_created_on_progress_update_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);

        app(TenantContext::class)->set($organization);

        $update = app(ProgressTrackingService::class)->create($project, [
            'progress_percentage' => 12,
            'summary' => 'Audited progress entry',
        ], $user);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $update->getMorphClass(),
            'auditable_id' => $update->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $audit = AuditLog::query()
            ->where('auditable_id', $update->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertInstanceOf(ProgressUpdate::class, $update);
    }
}
