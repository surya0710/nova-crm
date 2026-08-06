<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_create_and_list_templates(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-templates.store'), [
                'name' => 'Delivery Blueprint',
                'description' => 'Standard delivery plan',
                'category' => 'delivery',
            ])
            ->assertRedirect();

        $template = ProjectTemplate::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Delivery Blueprint')
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('project-templates.index'))
            ->assertOk()
            ->assertSee('Delivery Blueprint');

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'category' => 'delivery',
        ]);
    }
}
