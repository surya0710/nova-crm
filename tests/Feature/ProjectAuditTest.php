<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_audit_log_created_on_project_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Audited Project',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'priority' => 'medium',
            ]);

        $project = Project::query()->where('organization_id', $organization->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $project->getMorphClass(),
            'auditable_id' => $project->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $audit = AuditLog::query()
            ->where('auditable_id', $project->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Audited Project', $audit->subject);
    }

    public function test_audit_log_created_on_project_label_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-labels.store'), [
                'name' => 'Audit Label From ProjectAudit',
                'color' => '#64748b',
            ])
            ->assertRedirect();

        $label = \App\Models\ProjectLabel::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Audit Label From ProjectAudit')
            ->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $label->getMorphClass(),
            'auditable_id' => $label->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }
}
