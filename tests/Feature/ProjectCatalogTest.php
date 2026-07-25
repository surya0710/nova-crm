<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_organization_owner_can_access_category_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/project-categories');

        $response->assertOk();
        $response->assertSee('Project Categories');
    }

    public function test_organization_owner_can_create_category(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post('/project-categories', [
                'name' => 'Custom Category',
                'color' => '#112233',
                'is_active' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_categories', [
            'organization_id' => $organization->id,
            'name' => 'Custom Category',
            'slug' => 'custom-category',
        ]);
    }

    public function test_organization_owner_can_access_type_index_and_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/project-types')
            ->assertOk()
            ->assertSee('Project Types');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post('/project-types', [
                'name' => 'Retainer',
                'default_duration' => 30,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_types', [
            'organization_id' => $organization->id,
            'name' => 'Retainer',
            'slug' => 'retainer',
        ]);
    }

    public function test_organization_owner_can_access_status_index_and_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/project-statuses')
            ->assertOk()
            ->assertSee('Project Statuses');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post('/project-statuses', [
                'name' => 'Awaiting Approval',
                'color' => '#abcdef',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_statuses', [
            'organization_id' => $organization->id,
            'name' => 'Awaiting Approval',
            'slug' => 'awaiting-approval',
        ]);
    }

    public function test_organization_owner_can_access_lifecycle_index_and_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/project-lifecycle-stages')
            ->assertOk()
            ->assertSee('Lifecycle Stages');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post('/project-lifecycle-stages', [
                'name' => 'Discovery',
                'sequence' => 99,
                'color' => '#fedcba',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_lifecycle_stages', [
            'organization_id' => $organization->id,
            'name' => 'Discovery',
            'slug' => 'discovery',
        ]);
    }

    public function test_organization_owner_can_update_and_delete_catalog_entries(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $category = ProjectCategory::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Temporary',
            'slug' => 'temporary',
            'is_active' => true,
            'sort_order' => 999,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put('/project-categories/'.$category->id, [
                'name' => 'Temporary Updated',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_categories', [
            'id' => $category->id,
            'name' => 'Temporary Updated',
        ]);

        $type = ProjectType::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Temp Type',
            'slug' => 'temp-type',
            'is_active' => true,
            'sort_order' => 999,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete('/project-types/'.$type->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('project_types', ['id' => $type->id]);

        $status = ProjectStatus::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Temp Status',
            'slug' => 'temp-status',
            'sort_order' => 999,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete('/project-statuses/'.$status->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('project_statuses', ['id' => $status->id]);

        $stage = ProjectLifecycleStage::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Temp Stage',
            'slug' => 'temp-stage',
            'sequence' => 999,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete('/project-lifecycle-stages/'.$stage->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('project_lifecycle_stages', ['id' => $stage->id]);
    }
}
