<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCollaborationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_auditable_creates_activity_on_label_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-labels.store'), [
                'name' => 'Audited Label',
                'color' => '#2563eb',
            ])
            ->assertRedirect();

        $label = ProjectLabel::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Audited Label')
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
